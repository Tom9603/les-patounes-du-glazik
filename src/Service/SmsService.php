<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twilio\Rest\Client;

class SmsService
{
    private ?Client $twilio = null;

    public function __construct(
        #[Autowire('%env(TWILIO_SID)%')]
        private string $sid,
        #[Autowire('%env(TWILIO_AUTH_TOKEN)%')]
        private string $authToken,
        #[Autowire('%env(TWILIO_FROM)%')]
        private string $from,
    ) {}

    public function send(string $to, string $message): bool
    {
        try {
            if ($this->twilio === null) {
                $this->twilio = new Client($this->sid, $this->authToken);
            }
            $this->twilio->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
