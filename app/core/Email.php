<?php
declare(strict_types=1);

require_once __DIR__ . '/SmtpMailer.php';

class Email {
    public static function notification(string $to, string $subject, string $message): bool {
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $body = "
            <h2>e-Media Support</h2>
            <p>Bonjour,</p>
            <p>{$message}</p>
            <p>
                Pour plus de détails, connectez-vous à votre espace sur e-Media Support.
            </p>
            <p>Cordialement,<br>L'équipe e-Media Support</p>
            <hr style=\"border: none; border-top: 1px solid #eee; margin: 24px 0;\">
            <p style=\"color: #777; font-size: 12px;\">Ceci est un email automatique de notification. Merci de ne pas y répondre.</p>
        ";
        return self::send($to, $subject, $body);
    }

    public static function send(string $to, string $subject, string $body): bool {
        $mailConfig = include dirname(__DIR__, 2) . '/config/mail.php';
        $appConfig = include dirname(__DIR__, 2) . '/config/app.php';

        $fromEmail = $mailConfig['from_email'] ?? 'noreply@emedia.com';
        $fromName = $mailConfig['from_name'] ?? 'e-Media Support';

        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logContent = '[' . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject\nBODY:\n$body\n" . str_repeat('-', 50) . "\n";
        file_put_contents($logDir . '/mail.log', $logContent, FILE_APPEND);

        $smtpUser = trim((string)($mailConfig['username'] ?? ''));
        $smtpPass = (string)($mailConfig['password'] ?? '');
        $useSmtp = $smtpUser !== '' && $smtpPass !== '';

        if ($useSmtp) {
            $cfg = [
                'host' => (string)($mailConfig['host'] ?? 'smtp.gmail.com'),
                'port' => (int)($mailConfig['port'] ?? 587),
                'username' => $smtpUser,
                'password' => $smtpPass,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'verify_peer' => (bool)($mailConfig['verify_peer'] ?? true),
            ];
            $ok = SmtpMailer::sendHtml($to, $subject, $body, $cfg);
            if (!$ok) {
                file_put_contents($logDir . '/mail.log', '[' . date('Y-m-d H:i:s') . "] Échec envoi SMTP vers $to\n", FILE_APPEND);
            }
            return $ok;
        }

        if (($appConfig['env'] ?? 'development') === 'development') {
            file_put_contents(
                $logDir . '/mail.log',
                '[' . date('Y-m-d H:i:s') . "] Aucune config SMTP (MAIL_USERNAME / MAIL_PASSWORD) : email non envoyée (mode dev).\n",
                FILE_APPEND
            );
            return true;
        }

        try {
            $headerStr =
                "MIME-Version: 1.0\r\n" .
                "Content-type: text/html; charset=utf-8\r\n" .
                'From: ' . $fromName . " <{$fromEmail}>\r\n" .
                "Reply-To: {$fromEmail}\r\n" .
                'X-Mailer: PHP/' . phpversion() . "\r\n";

            return mail($to, $subject, $body, $headerStr);
        } catch (Throwable $e) {
            file_put_contents($logDir . '/mail.log', 'Mail Error: ' . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }
}
