<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\MagicLink;
use App\Core\ValueObject\EmailAddress;

final readonly class MagicLinkService
{
    public function __construct(
        private ClockInterface $clock,
        private ConfigInterface $config,
        private MagicLinkRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array{token: string, code: string}
     */
    public function createToken(string $email): array
    {
        $token = \bin2hex(\random_bytes(32));
        $code  = \strtoupper(\substr(\bin2hex(\random_bytes(4)), 0, 6));

        $links    = $this->repository->loadAll();
        $durationRaw = $this->config->get('magic_link_duration', 15);
        $duration = \is_numeric($durationRaw) ? (int) $durationRaw : 15; // 15 Min Gültig

        $links[$token] = new MagicLink(
            $token,
            new EmailAddress($email),
            $code,
            $this->clock->now()->modify("+{$duration} minutes"),
        );

        $this->repository->saveAll($links);

        return ['token' => $token, 'code' => $code];
    }

    public function peekToken(string $token): ?string
    {
        $links = $this->repository->loadAll();
        $now   = $this->clock->now();

        if (isset($links[$token]) && ! $links[$token]->isExpired($now)) {
            return $links[$token]->email->value;
        }

        return null;
    }

    public function verifyAny(string $input): ?string
    {
        $links      = $this->repository->loadAll();
        $now        = $this->clock->now();
        $trimmed    = \trim($input);
        $foundEmail = null;

        foreach ($links as $token => $magicLink) {
            if ($magicLink->isExpired($now)) {
                unset($links[$token]);

                continue;
            }

            $strToken         = (string) $token;
            $isLongTokenMatch = \strlen($strToken) === \strlen($trimmed)
                                && \hash_equals(\strtolower($strToken), \strtolower($trimmed));

            $isShortCodeMatch = \strlen($magicLink->code) === \strlen($trimmed)
                                && \hash_equals(\strtoupper($magicLink->code), \strtoupper($trimmed));

            if ($isLongTokenMatch || $isShortCodeMatch) {
                $foundEmail = $magicLink->email->value;
                unset($links[$token]);

                break;
            }
        }

        $this->repository->saveAll($links);

        return $foundEmail;
    }
}
