<?php

namespace App\Service;

use App\Entity\CommandeProduit;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SmsNotifier
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {
    }

    public function sendOrderCreated(string $to, CommandeProduit $commande): void
    {
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? $_SERVER['TWILIO_ACCOUNT_SID'] ?? null;
        $authToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? $_SERVER['TWILIO_AUTH_TOKEN'] ?? null;
        $messagingSid = $_ENV['TWILIO_MESSAGING_SERVICE_SID'] ?? $_SERVER['TWILIO_MESSAGING_SERVICE_SID'] ?? null;
        $fromNumber = $_ENV['TWILIO_FROM_NUMBER'] ?? $_SERVER['TWILIO_FROM_NUMBER'] ?? null;

        if (!$accountSid || !$authToken) {
            $this->logger->warning('Twilio SMS non configuré : SID ou token manquant.');
            return;
        }

        if (!$messagingSid && !$fromNumber) {
            $this->logger->warning('Twilio SMS non configuré : MessagingServiceSid ou From manquant.');
            return;
        }

        $orderId = $commande->getId();
        $amount = number_format((float) $commande->getMontantTotal(), 2, '.', '');
        $body = "MediConnect : Votre commande #{$orderId} est confirmée. Montant : {$amount} TND. Merci !";

        $payload = [
            'To' => $to,
            'Body' => $body,
        ];
        if ($messagingSid) {
            $payload['MessagingServiceSid'] = $messagingSid;
        } else {
            $payload['From'] = $fromNumber;
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json",
                [
                    'auth_basic' => [$accountSid, $authToken],
                    'body' => $payload,
                ]
            );
            if ($response->getStatusCode() >= 300) {
                $this->logger->error('Twilio SMS échec', ['status' => $response->getStatusCode(), 'response' => $response->getContent(false)]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Twilio SMS exception', ['error' => $e->getMessage()]);
        }
    }
}
