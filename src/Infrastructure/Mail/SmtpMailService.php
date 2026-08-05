<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailLogInterface;
use App\Contracts\Mail\MailServiceInterface;

final readonly class SmtpMailService implements MailLogInterface, MailServiceInterface
{
    public function __construct(
        private \PDO $pdo,
        private ConfigInterface $config,
    ) {
    }

    public function sendTemplate(string $recipient, string $subject, string $template, array $data): bool|string
    {
        if (\in_array(\trim($recipient), ['', '0'], true)) {
            $this->logEmail('System', $subject, $template, 'Übersprungen: Kein Empfänger', $data);

            return true;
        }

        $mailConfig = $this->config->getMailSettings();
        $body       = $this->render($template, $data);

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
        return 0; // Wird vom MailQueueService übernommen
    }

    private function render(string $templatePath, array $data): string
    {
        $root     = $this->config->get('root_path');
        $fullPath = $root . "/templates/emails/{$templatePath}.phtml";

        if (! \file_exists($fullPath)) {
            throw new \RuntimeException("Mail-Template nicht gefunden: {$fullPath}");
        }

        \extract($data, \EXTR_SKIP);
        \ob_start();
        include $fullPath;

        return (string) \ob_get_clean();
    }

    private function dispatch(string $recipient, string $subject, string $body, array $smtpConfig): bool|string
    {
        $host       = $smtpConfig['host'] ?? '';
        $port       = (int) ($smtpConfig['port'] ?? 465);
        $encryption = \strtolower($smtpConfig['encryption'] ?? '');
        $user       = \str_replace(["\r", "\n"], '', $smtpConfig['user'] ?? '');
        $pass       = \str_replace(["\r", "\n"], '', $smtpConfig['pass'] ?? '');
        $from       = \str_replace(["\r", "\n"], '', $smtpConfig['from'] ?? '');
        $recipient  = \str_replace(["\r", "\n"], '', $recipient);

        // Port 465 bedeutet meist reines SSL direkt beim Aufbau
        $protocol = ($port === 465 || $encryption === 'ssl') ? 'ssl://' : '';
        $socket   = @\fsockopen($protocol . $host, $port, $errno, $errstr, 15);

        if (! $socket) {
            return "Verbindung fehlgeschlagen: $errstr ($errno)";
        }
        if (! $this->checkResponse($socket, '220')) {
            return 'Server meldet sich nicht (Timeout)';
        }

        $smtpEhloHost = \parse_url($this->config->getBaseUrl())['host'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        \fwrite($socket, 'EHLO ' . $smtpEhloHost . "\r\n");
        if (! $this->checkResponse($socket, '250')) {
            return 'EHLO abgelehnt';
        }

        // HIER IST DER FIX: STARTTLS bei TLS oder Port 587
        if ($encryption === 'tls' || $encryption === 'starttls' || $port === 587) {
            \fwrite($socket, "STARTTLS\r\n");
            if (! $this->checkResponse($socket, '220')) {
                return 'STARTTLS abgelehnt (Wird vom Server nicht unterstützt?)';
            }

            // Kryptografie aktivieren (Schützt PHP vor alten SSL Versionen, nutzt TLS 1.2)
            $cryptoMethod = \STREAM_CRYPTO_METHOD_TLS_CLIENT | \STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (! \stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                return 'Konnte Verschlüsselung (STARTTLS) nicht aktivieren';
            }

            // Nach dem Verschlüsseln muss sich das Skript laut SMTP-Protokoll neu vorstellen
            \fwrite($socket, 'EHLO ' . $smtpEhloHost . "\r\n");
            if (! $this->checkResponse($socket, '250')) {
                return 'EHLO nach STARTTLS abgelehnt';
            }
        }

        \fwrite($socket, "AUTH LOGIN\r\n");
        $this->getServerResponse($socket); // Sollte 334 VXNlcm5hbWU6 liefern
        \fwrite($socket, \base64_encode($user) . "\r\n");
        $this->getServerResponse($socket); // Sollte 334 UGFzc3dvcmQ6 liefern
        \fwrite($socket, \base64_encode($pass) . "\r\n");
        if (! $this->checkResponse($socket, '235')) {
            return 'SMTP Login fehlgeschlagen (Falsches Passwort / User)';
        }

        \fwrite($socket, "MAIL FROM: <$from>\r\n");
        $this->getServerResponse($socket);
        \fwrite($socket, "RCPT TO: <$recipient>\r\n");
        if (! $this->checkResponse($socket, '250')) {
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
        if (! $this->checkResponse($socket, '250')) {
            return 'E-Mail Daten abgelehnt';
        }

        \fwrite($socket, "QUIT\r\n");
        \fclose($socket);

        return true;
    }

    private function checkResponse(mixed $socket, string $expectedCode): bool
    {
        $response = $this->getServerResponse($socket);

        return \str_starts_with($response, $expectedCode);
    }

    private function getServerResponse(mixed $socket): string
    {
        $response = '';
        while ($str = \fgets($socket, 515)) {
            $response .= $str;
            if (\preg_match('/^\d{3} /', $str)) {
                break;
            }
        }

        return $response;
    }

    private function logEmail(string $recipient, string $subject, string $template, bool|string $status, array $data = []): void
    {
        $statusStr = $status === true ? 'Erfolg' : 'Fehler: ' . $status;
        $id        = \uniqid('ml_');
        $now       = \date('Y-m-d H:i:s');
        $json      = \json_encode($data, \JSON_UNESCAPED_UNICODE);

        $stmt = $this->pdo->prepare('INSERT INTO `mail_logs` (id, timestamp, recipient, subject, template, status, data) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$id, $now, $recipient, $subject, $template, $statusStr, $json]);

        // Max 200 Logs behalten
        $this->pdo->exec('DELETE FROM `mail_logs` WHERE id NOT IN (SELECT id FROM (SELECT id FROM `mail_logs` ORDER BY timestamp DESC LIMIT 200) tmp)');
    }

    public function loadLogs(): array
    {
        return $this->pdo->query('SELECT * FROM `mail_logs` ORDER BY timestamp DESC')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function saveLogs(array $logs, bool $forceSql = false): void
    {
    }

    public function importLogs(array $data, bool $forceSql = false): void
    {
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `mail_logs` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
