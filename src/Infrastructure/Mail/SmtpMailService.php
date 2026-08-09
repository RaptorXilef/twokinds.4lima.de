<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\DirectMailServiceInterface;
use App\Contracts\Mail\MailLogInterface;
use App\Contracts\Mail\MailServiceInterface;
use PDO;
use RuntimeException;
use Throwable;

final readonly class SmtpMailService implements MailLogInterface, MailServiceInterface, DirectMailServiceInterface
{
    public function __construct(
        private PDO $pdo,
        private ConfigInterface $config,
    ) {
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * @param array<string, mixed> $data
     */
    public function sendTemplate(string $recipient, string $subject, string $template, array $data): bool|string
    {
        if (\in_array(\trim($recipient), ['', '0'], true)) {
            $this->logEmail('System', $subject, $template, 'Übersprungen: Kein Empfänger', $data);

            return true;
        }

        $mailConfig = $this->config->getMailSettings();
        $body = $this->render($template, $data);

        if ($this->config->isTestMode() && ($mailConfig['test_mail_active'] ?? false) === false) {
            $this->logEmail($recipient, $subject, $template, 'Testmodus (kein Versand)', $data);

            return true;
        }

        $status = $this->dispatch($recipient, $subject, $body, $mailConfig);
        $this->logEmail($recipient, $subject, $template, $status, $data);

        return $status;
    }

    public function processQueue(int $limit = 5, array $allowedTemplates = []): int
    {
        unset($limit, $allowedTemplates); // Wird vom MailQueueService übernommen

        return 0;
    }

    public function loadLogs(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `mail_logs` ORDER BY timestamp DESC');
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!\is_array($rows)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $validRows */
        $validRows = [];
        foreach ($rows as $r) {
            if (!\is_array($r)) {
                continue;
            }

            /** @var array<string, mixed> $validR */
            $validR = $r;
            $validRows[] = $validR;
        }

        return $validRows;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `mail_logs` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $validRow */
        $validRow = $row;

        return $validRow;
    }

    public function saveLogs(array $logs, bool $forceSql = false): void
    {
        unset($logs, $forceSql); // Interface contract
    }

    public function importLogs(array $data, bool $forceSql = false): void
    {
        unset($data, $forceSql); // Interface contract
    }

    // =========================================================================
    // PRIVATE HELPER: MAIL RENDER & DISPATCH ORCHESTRATION
    // =========================================================================

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $templatePath, array $data): string
    {
        $rootRaw = $this->config->get('root_path', '');
        $root = \is_string($rootRaw) ? $rootRaw : '';
        $fullPath = \rtrim($root, '/\\') . "/templates/emails/{$templatePath}.phtml";

        if (!\file_exists($fullPath)) {
            throw new RuntimeException("Mail-Template nicht gefunden: {$fullPath}");
        }

        \extract($data, \EXTR_SKIP);
        \ob_start();
        include $fullPath;

        return (string) \ob_get_clean();
    }

    /**
     * @param array<string, mixed> $smtpConfig
     */
    private function dispatch(string $recipient, string $subject, string $body, array $smtpConfig): bool|string
    {
        $socket = $this->connectToSmtp($smtpConfig);
        if (\is_string($socket)) {
            return $socket; // Connection error string
        }

        $ehloErr = $this->sendEhloAndTls($socket, $smtpConfig);
        if ($ehloErr !== null) {
            return $ehloErr;
        }

        $authErr = $this->authenticate($socket, $smtpConfig);
        if ($authErr !== null) {
            return $authErr;
        }

        $sendErr = $this->transmitEmail($socket, $recipient, $subject, $body, $smtpConfig);
        if ($sendErr !== null) {
            return $sendErr;
        }

        \fwrite($socket, "QUIT\r\n");
        \fclose($socket);

        return true;
    }

    // =========================================================================
    // PRIVATE HELPER: LOW LEVEL SMTP PROTOCOL
    // =========================================================================

    /**
     * @param array<string, mixed> $smtpConfig
     *
     * @return resource|string
     */
    private function connectToSmtp(array $smtpConfig)
    {
        $hostRaw = $smtpConfig['host'] ?? '';
        $host = \is_scalar($hostRaw) ? (string) $hostRaw : '';

        $portRaw = $smtpConfig['port'] ?? 465;
        $port = \is_scalar($portRaw) ? (int) $portRaw : 465;

        $encryptionRaw = $smtpConfig['encryption'] ?? '';
        $encryption = \strtolower(\is_scalar($encryptionRaw) ? (string) $encryptionRaw : '');

        // Port 465 bedeutet meist reines SSL direkt beim Aufbau
        $protocol = $port === 465 || $encryption === 'ssl' ? 'ssl://' : '';

        try {
            $socket = \fsockopen($protocol . $host, $port, $errno, $errstr, 15);
        } catch (Throwable) {
            return "Verbindung fehlgeschlagen: Host unerreichbar ($port)";
        }

        if ($socket === false) {
            return "Verbindung fehlgeschlagen: $errstr ($errno)";
        }

        if (!$this->checkResponse($socket, '220')) {
            return 'Server meldet sich nicht (Timeout)';
        }

        return $socket;
    }

    /**
     * @param resource $socket
     * @param array<string, mixed> $smtpConfig
     */
    private function sendEhloAndTls($socket, array $smtpConfig): ?string
    {
        $baseUrl = $this->config->getBaseUrl();
        $parsed = \parse_url($baseUrl);
        $parsedHost = \is_array($parsed) && isset($parsed['host']) && \is_string($parsed['host']) ? $parsed['host'] : null;

        $fallbackHostRaw = $this->config->get('server_host', 'localhost');
        $fallbackHost = \is_string($fallbackHostRaw) ? $fallbackHostRaw : 'localhost';

        $smtpEhloHost = $parsedHost ?? $fallbackHost;

        \fwrite($socket, 'EHLO ' . $smtpEhloHost . "\r\n");
        if (!$this->checkResponse($socket, '250')) {
            return 'EHLO abgelehnt';
        }

        $portRaw = $smtpConfig['port'] ?? 465;
        $port = \is_scalar($portRaw) ? (int) $portRaw : 465;

        $encryptionRaw = $smtpConfig['encryption'] ?? '';
        $encryption = \strtolower(\is_scalar($encryptionRaw) ? (string) $encryptionRaw : '');

        if ($encryption === 'tls' || $encryption === 'starttls' || $port === 587) {
            \fwrite($socket, "STARTTLS\r\n");
            if (!$this->checkResponse($socket, '220')) {
                return 'STARTTLS abgelehnt (Wird vom Server nicht unterstützt?)';
            }

            // Kryptografie aktivieren (Schützt PHP vor alten SSL Versionen, nutzt TLS 1.2)
            $cryptoMethod = \STREAM_CRYPTO_METHOD_TLS_CLIENT | \STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (\stream_socket_enable_crypto($socket, true, $cryptoMethod) === false) {
                return 'Konnte Verschlüsselung (STARTTLS) nicht aktivieren';
            }

            // Nach dem Verschlüsseln muss sich das Skript laut SMTP-Protokoll neu vorstellen
            \fwrite($socket, 'EHLO ' . $smtpEhloHost . "\r\n");
            if (!$this->checkResponse($socket, '250')) {
                return 'EHLO nach STARTTLS abgelehnt';
            }
        }

        return null;
    }

    /**
     * @param resource $socket
     * @param array<string, mixed> $smtpConfig
     */
    private function authenticate($socket, array $smtpConfig): ?string
    {
        $userRaw = $smtpConfig['user'] ?? '';
        $user = \str_replace(["\r", "\n"], '', \is_scalar($userRaw) ? (string) $userRaw : '');

        $passRaw = $smtpConfig['pass'] ?? '';
        $pass = \str_replace(["\r", "\n"], '', \is_scalar($passRaw) ? (string) $passRaw : '');

        \fwrite($socket, "AUTH LOGIN\r\n");
        $this->getServerResponse($socket); // Sollte 334 VXNlcm5hbWU6 liefern

        \fwrite($socket, \base64_encode($user) . "\r\n");
        $this->getServerResponse($socket); // Sollte 334 UGFzc3dvcmQ6 liefern

        \fwrite($socket, \base64_encode($pass) . "\r\n");
        if (!$this->checkResponse($socket, '235')) {
            return 'SMTP Login fehlgeschlagen (Falsches Passwort / User)';
        }

        return null;
    }

    /**
     * @param resource $socket
     * @param array<string, mixed> $smtpConfig
     */
    private function transmitEmail($socket, string $recipient, string $subject, string $body, array $smtpConfig): ?string
    {
        $fromRaw = $smtpConfig['from'] ?? '';
        $from = \str_replace(["\r", "\n"], '', \is_scalar($fromRaw) ? (string) $fromRaw : '');
        $recipient = \str_replace(["\r", "\n"], '', $recipient);

        \fwrite($socket, "MAIL FROM: <$from>\r\n");
        $this->getServerResponse($socket);

        \fwrite($socket, "RCPT TO: <$recipient>\r\n");
        if (!$this->checkResponse($socket, '250')) {
            return "Empfänger $recipient abgelehnt";
        }

        \fwrite($socket, "DATA\r\n");
        $this->getServerResponse($socket);

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: <$from>\r\n";
        $headers .= "To: <$recipient>\r\n";
        $headers .= 'Subject: =?UTF-8?B?' . \base64_encode($subject) . "?=\r\n\r\n";

        \fwrite($socket, $headers . $body . "\r\n.\r\n");
        if (!$this->checkResponse($socket, '250')) {
            return 'E-Mail Daten abgelehnt';
        }

        return null;
    }

    /**
     * @param resource $socket
     */
    private function checkResponse($socket, string $expectedCode): bool
    {
        $response = $this->getServerResponse($socket);

        return \str_starts_with($response, $expectedCode);
    }

    /**
     * @param resource $socket
     */
    private function getServerResponse($socket): string
    {
        $response = '';
        while (($str = \fgets($socket, 515)) !== false) {
            $response .= $str;
            if (\preg_match('/^\d{3} /', $str) === 1) {
                break;
            }
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function logEmail(string $recipient, string $subject, string $template, bool|string $status, array $data = []): void
    {
        $statusStr = $status === true ? 'Erfolg' : 'Fehler: ' . $status;
        $id = \uniqid('ml_');
        $now = \date('Y-m-d H:i:s');

        $jsonStr = \json_encode($data, \JSON_UNESCAPED_UNICODE);
        $json = \is_string($jsonStr) ? $jsonStr : '{}';

        $sql = 'INSERT INTO `mail_logs` (id, timestamp, recipient, subject, template, status, data) '
             . 'VALUES (?, ?, ?, ?, ?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $now, $recipient, $subject, $template, $statusStr, $json]);

        // Max 200 Logs behalten
        $cleanSql = 'DELETE FROM `mail_logs` WHERE id NOT IN '
                  . '(SELECT id FROM (SELECT id FROM `mail_logs` ORDER BY timestamp DESC LIMIT 200) tmp)';
        $this->pdo->exec($cleanSql);
    }
}
