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
        $code = \strtoupper(\substr(\bin2hex(\random_bytes(4)), 0, 6));

        $links = $this->repository->loadAll();
        $durationRaw = $this->config->get('magic_link_duration', 15);

        $duration = 15; // 15 Min Gültig
        if (\is_int($durationRaw)) {
            $duration = $durationRaw;
        } elseif (\is_string($durationRaw) && \is_numeric($durationRaw)) {
            $duration = (int) $durationRaw;
        }

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
        $now = $this->clock->now();

        $magicLink = $links[$token] ?? null;
        if ($magicLink instanceof MagicLink && !$magicLink->isExpired($now)) {
            $email = $magicLink->email;

            return $email instanceof EmailAddress ? $email->value : (string) $email;
        }

        return null;
    }

    public function verifyAny(string $input): ?string
    {
        $links = $this->repository->loadAll();
        $now = $this->clock->now();
        $trimmed = \trim($input);
        $foundEmail = null;

        foreach ($links as $token => $magicLink) {
            if (!$magicLink instanceof MagicLink) {
                continue;
            }

            if ($magicLink->isExpired($now)) {
                unset($links[$token]);

                continue;
            }

            $strToken = (string) $token;
            $isLongTokenMatch = \strlen($strToken) === \strlen($trimmed)
                                && \hash_equals(\strtolower($strToken), \strtolower($trimmed));

            $strCode = \is_string($magicLink->code) ? $magicLink->code : (string) $magicLink->code;
            $isShortCodeMatch = \strlen($strCode) === \strlen($trimmed)
                                && \hash_equals(\strtoupper($strCode), \strtoupper($trimmed));

            if ($isLongTokenMatch || $isShortCodeMatch) {
                $email = $magicLink->email;
                $foundEmail = $email instanceof EmailAddress ? $email->value : (string) $email;
                unset($links[$token]);

                break;
            }
        }

        $this->repository->saveAll($links);

        return $foundEmail;
    }
}
