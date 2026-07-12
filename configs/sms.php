<?php
// Fallback SMS client for recuperar-senha.php.
// Substitua por Twilio ou outro serviço SMS em produção.

class SimpleSmsClient
{
    public function send(string $to, array $options): bool
    {
        $from = $options['from'] ?? '';
        $body = $options['body'] ?? '';

        if (empty($to) || empty($from) || empty($body)) {
            return false;
        }

        // Aqui você implementaria a chamada real ao provedor de SMS.
        return true;
    }
}

class SimpleSmsMessages
{
    private SimpleSmsClient $client;

    public function __construct(SimpleSmsClient $client)
    {
        $this->client = $client;
    }

    public function create(string $to, array $options): bool
    {
        return $this->client->send($to, $options);
    }
}

$smsClient = new stdClass();
$smsClient->messages = new SimpleSmsMessages(new SimpleSmsClient());
