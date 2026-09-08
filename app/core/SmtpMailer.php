<?php
declare(strict_types=1);

/**
 * Envoi minimal SMTP — STARTTLS (port 587) ou SSL/TLS direct (port 465).
 */
final class SmtpMailer {
    /**
     * @param array{host:string,port:int,username:string,password:string,from_email:string,from_name:string,verify_peer?:bool} $cfg
     */
    public static function sendHtml(string $to, string $subject, string $htmlBody, array $cfg): bool {
        $host = $cfg['host'];
        $port = (int)($cfg['port'] ?? 587);
        $user = $cfg['username'];
        $pass = $cfg['password'];
        $fromEmail = $cfg['from_email'];
        $fromName = $cfg['from_name'] ?? '';
        $verifyPeer = $cfg['verify_peer'] ?? true;
        $useSsl = ($port === 465);

        $logDir = dirname(__DIR__, 2) . '/logs';
        $logErr = static function (string $msg) use ($logDir): void {
            file_put_contents($logDir . '/mail.log', '[' . date('Y-m-d H:i:s') . "] SMTP: $msg\n", FILE_APPEND);
        };

        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => $verifyPeer,
                'verify_peer_name' => $verifyPeer,
                'allow_self_signed' => !$verifyPeer,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            20,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if ($socket === false) {
            $logErr("connexion impossible ($errno) $errstr");
            return false;
        }

        stream_set_timeout($socket, 20);

        try {
            if ($useSsl) {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $logErr('négociation SSL échouée (port 465)');
                    return false;
                }
            }

            if (!self::expect(self::readLines($socket), [220])) {
                $logErr('pas de salutation 220');
                return false;
            }

            self::write($socket, "EHLO emedia-support.local\r\n");
            if (!self::expect(self::readLines($socket), [250])) {
                $logErr('EHLO initial refusé');
                return false;
            }

            if (!$useSsl) {
                self::write($socket, "STARTTLS\r\n");
                if (!self::expect(self::readLines($socket), [220])) {
                    $logErr('STARTTLS refusé');
                    return false;
                }

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $logErr('négociation TLS échouée');
                    return false;
                }

                self::write($socket, "EHLO emedia-support.local\r\n");
                if (!self::expect(self::readLines($socket), [250])) {
                    $logErr('EHLO après TLS refusé');
                    return false;
                }
            }

            self::write($socket, "AUTH LOGIN\r\n");
            if (!self::expect(self::readLines($socket), [334])) {
                $logErr('AUTH LOGIN refusé');
                return false;
            }

            self::write($socket, base64_encode($user) . "\r\n");
            if (!self::expect(self::readLines($socket), [334])) {
                $logErr('identifiant SMTP refusé');
                return false;
            }

            self::write($socket, base64_encode($pass) . "\r\n");
            if (!self::expect(self::readLines($socket), [235])) {
                $logErr('mot de passe SMTP refusé (vérifiez le mot de passe d\'application Gmail)');
                return false;
            }

            self::write($socket, 'MAIL FROM:<' . $fromEmail . ">\r\n");
            if (!self::expect(self::readLines($socket), [250])) {
                $logErr('MAIL FROM refusé');
                return false;
            }

            self::write($socket, 'RCPT TO:<' . $to . ">\r\n");
            if (!self::expect(self::readLines($socket), [250, 251])) {
                $logErr('RCPT TO refusé');
                return false;
            }

            self::write($socket, "DATA\r\n");
            if (!self::expect(self::readLines($socket), [354])) {
                $logErr('DATA refusé');
                return false;
            }

            $encodedSubject = self::encodeMimeHeader($subject);
            $encodedFromName = self::encodeMimeHeader($fromName ?: 'e-Media Support');
            $boundary = 'b_' . bin2hex(random_bytes(8));

            $headers = [
                'MIME-Version: 1.0',
                'Date: ' . date('r'),
                'From: ' . $encodedFromName . " <{$fromEmail}>",
                "To: <{$to}>",
                "Subject: {$encodedSubject}",
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: quoted-printable',
            ];

            $bodyUnix = str_replace(["\r\n", "\r"], "\n", $htmlBody);
            $qpBody = quoted_printable_encode($bodyUnix);
            $qpBody = str_replace("\n", "\r\n", $qpBody);
            $qpBody = self::smtpDotStuff($qpBody);

            $raw = implode("\r\n", $headers) . "\r\n\r\n" . $qpBody . "\r\n.\r\n";
            self::write($socket, $raw);

            if (!self::expect(self::readLines($socket), [250])) {
                $logErr('message non accepté après DATA');
                return false;
            }

            self::write($socket, "QUIT\r\n");
            self::readLines($socket);

            return true;
        } finally {
            fclose($socket);
        }
    }

    private static function write($socket, string $data): void {
        fwrite($socket, $data);
    }

    /** @return list<string> lignes complètes du serveur pour la réponse courante */
    private static function readLines($socket): array {
        $lines = [];
        while (($line = fgets($socket, 8192)) !== false) {
            $lines[] = rtrim($line, "\r\n");
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $lines;
    }

    /** @param list<int> $okCodes */
    private static function expect(array $lines, array $okCodes): bool {
        if ($lines === []) {
            return false;
        }
        $last = $lines[count($lines) - 1];
        $code = (int)substr($last, 0, 3);
        return in_array($code, $okCodes, true);
    }

    private static function encodeMimeHeader(string $text): string {
        if ($text === '') {
            return '';
        }
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }
        return $text;
    }

    private static function smtpDotStuff(string $s): string {
        return preg_replace('/^\./m', '..', $s) ?? $s;
    }
}
