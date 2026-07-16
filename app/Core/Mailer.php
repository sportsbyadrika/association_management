<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Email sender. Uses PHPMailer over SMTP when the package and credentials are
 * available; otherwise falls back to logging the message so local development
 * (and the password-reset flow) can be exercised without a live SMTP server.
 */
final class Mailer
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (require dirname(__DIR__, 2) . '/config/config.php')['mail'];
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $hasSmtp = class_exists(PHPMailer::class)
            && $this->config['host'] !== ''
            && $this->config['username'] !== '';

        if (!$hasSmtp) {
            return $this->logFallback($toEmail, $subject, $textBody !== '' ? $textBody : strip_tags($htmlBody));
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = (int) $this->config['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];

            $encryption = strtolower((string) $this->config['encryption']);
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom($this->config['from_address'], $this->config['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            Logger::error('Mail send failed: ' . $e->getMessage());
            // Fall back to logging so the flow is not silently lost in dev.
            return $this->logFallback($toEmail, $subject, strip_tags($htmlBody));
        }
    }

    private function logFallback(string $to, string $subject, string $body): bool
    {
        Logger::info('EMAIL (not sent — no SMTP configured)', [
            'to'      => $to,
            'subject' => $subject,
            'body'    => $body,
        ]);
        return true;
    }
}
