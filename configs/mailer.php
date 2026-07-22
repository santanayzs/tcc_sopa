<?php
// SMTP mailer para recuperar-senha.php.
// Preencha os valores abaixo com os dados do seu servidor SMTP.

$smtpHost       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtpPort       = getenv('SMTP_PORT') ?: 587;
$smtpUsername   = getenv('SMTP_USERNAME') ?: 'derek.gabrielsdas@gmail.com';
$smtpPassword   = getenv('SMTP_PASSWORD') ?: 'eqje gqni xzyd tqaq';
$smtpEncryption = getenv('SMTP_ENCRYPTION') ?: 'tls'; // tls, ssl ou none
$fromEmail      = getenv('MAIL_FROM') ?: $smtpUsername;
$fromName       = getenv('MAIL_FROM_NAME') ?: 'S.O.P.A.';

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromEmail;
    private string $fromName;
    public string $Subject = '';
    public string $Body = '';
    public string $AltBody = '';
    private array $recipients = [];
    private bool $isHtml = false;

    public function __construct(
        string $host,
        int $port,
        string $username,
        string $password,
        string $encryption,
        string $fromEmail,
        string $fromName
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = strtolower($encryption);
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function addAddress(string $email, string $name = ''): void
    {
        $this->recipients[] = $email;
    }

    public function isHTML(bool $isHtml): void
    {
        $this->isHtml = $isHtml;
    }

    public function send(): bool
    {
        if (empty($this->recipients) || empty($this->Subject) || empty($this->Body)) {
            return false;
        }

        $socket = $this->openSocket();
        if (!is_resource($socket)) {
            return false;
        }

        if (!$this->expectResponse($socket, 220)) {
            fclose($socket);
            return false;
        }

        $hostname = gethostname() ?: 'localhost';
        $this->sendCommand($socket, "EHLO {$hostname}");
        if (!$this->expectResponse($socket, 250)) {
            fclose($socket);
            return false;
        }

        if ($this->encryption === 'tls') {
            $this->sendCommand($socket, 'STARTTLS');
            if (!$this->expectResponse($socket, 220) || !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }
            $this->sendCommand($socket, "EHLO {$hostname}");
            if (!$this->expectResponse($socket, 250)) {
                fclose($socket);
                return false;
            }
        }

        if ($this->username !== '' && $this->password !== '') {
            $this->sendCommand($socket, 'AUTH LOGIN');
            if (!$this->expectResponse($socket, 334)) {
                fclose($socket);
                return false;
            }
            $this->sendCommand($socket, base64_encode($this->username));
            if (!$this->expectResponse($socket, 334)) {
                fclose($socket);
                return false;
            }
            $this->sendCommand($socket, base64_encode($this->password));
            if (!$this->expectResponse($socket, 235)) {
                fclose($socket);
                return false;
            }
        }

        $this->sendCommand($socket, 'MAIL FROM:<' . $this->fromEmail . '>');
        if (!$this->expectResponse($socket, 250)) {
            fclose($socket);
            return false;
        }

        foreach ($this->recipients as $recipient) {
            $this->sendCommand($socket, 'RCPT TO:<' . $recipient . '>');
            if (!$this->expectResponse($socket, 250) && !$this->expectResponse($socket, 251)) {
                fclose($socket);
                return false;
            }
        }

        $this->sendCommand($socket, 'DATA');
        if (!$this->expectResponse($socket, 354)) {
            fclose($socket);
            return false;
        }

        $message = $this->buildMessage();
        fwrite($socket, $message);
        fwrite($socket, "\r\n.\r\n");

        if (!$this->expectResponse($socket, 250)) {
            fclose($socket);
            return false;
        }

        $this->sendCommand($socket, 'QUIT');
        fclose($socket);
        return true;
    }

    private function openSocket()
    {
        $scheme = $this->encryption === 'ssl' ? 'ssl' : 'tcp';
        $address = sprintf('%s://%s:%d', $scheme, $this->host, $this->port);
        $context = stream_context_create([ 'ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false ] ]);
        return @stream_socket_client($address, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    }

    private function sendCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private function expectResponse($socket, int $expectedCode): bool
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return strpos($response, (string) $expectedCode) === 0;
    }

    private function buildMessage(): string
    {
        $boundary = '==boundary_' . md5((string) time());
        $headers = [];
        $headers[] = 'From: ' . $this->formatAddress($this->fromEmail, $this->fromName);
        $headers[] = 'Reply-To: ' . $this->fromEmail;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'Subject: ' . $this->encodeHeader($this->Subject);
        $headers[] = 'Date: ' . date('r');

        $body = [];
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/plain; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: 8bit';
        $body[] = '';
        $body[] = $this->AltBody ?: strip_tags($this->Body);

        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/html; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: 8bit';
        $body[] = '';
        $body[] = $this->Body;
        $body[] = '--' . $boundary . '--';

        return implode("\r\n", array_merge($headers, [''], $body));
    }

    private function formatAddress(string $email, string $name): string
    {
        if ($name === '') {
            return $email;
        }
        return sprintf('"%s" <%s>', $this->encodeHeader($name), $email);
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[\x80-\xFF]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }
}

$mailer = new SmtpMailer(
    $smtpHost,
    (int) $smtpPort,
    $smtpUsername,
    $smtpPassword,
    $smtpEncryption,
    $fromEmail,
    $fromName
);
