<?php

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class OpenAIClient
{
    private $client;
    private $apiKey;
    private $assistantId;
    private $logger;

    // Injection du logger via le constructeur
    public function __construct(string $apiKey, string $assistantId, LoggerInterface $logger)
    {
        $this->client = new Client();
        $this->apiKey = $apiKey;
        $this->assistantId = $assistantId;
        $this->logger = $logger;  // On conserve le logger
    }

    public function createThread()
    {
        $response = $this->client->post('https://api.openai.com/v1/threads', [
            'headers' => $this->getHeaders()
        ]);

        // Log de la réponse complète de l'API OpenAI
        $threadData = json_decode($response->getBody(), true);
        $this->logger->info('Réponse de la création de thread : ' . json_encode($threadData));

        return $threadData;
    }

    public function addMessage($threadId, $content)
    {
        $response = $this->client->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'headers' => $this->getHeaders(),
            'json' => [
                'role' => 'user',
                'content' => $content
            ]
        ]);

        // Log de la réponse après avoir ajouté le message
        $messageData = json_decode($response->getBody(), true);
        $this->logger->info("Message ajouté au thread {$threadId} : " . json_encode($messageData));

        return $messageData;
    }

    public function runAssistant($threadId)
    {
        $response = $this->client->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
            'headers' => $this->getHeaders(),
            'json' => [
                'assistant_id' => $this->assistantId
            ]
        ]);

        // Log de la réponse après l'exécution de l'assistant
        $runData = json_decode($response->getBody(), true);
        $this->logger->info("Exécution de l'assistant pour le thread {$threadId} : " . json_encode($runData));

        return $runData;
    }

    public function getRunStatus($threadId, $runId)
    {
        $response = $this->client->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}", [
            'headers' => $this->getHeaders(),
        ]);

        // Log de la réponse du statut du run
        $statusData = json_decode($response->getBody(), true);
        $this->logger->info("Statut du run {$runId} pour le thread {$threadId} : " . json_encode($statusData));

        return $statusData;
    }

    public function getAllMessages($threadId)
    {
        $response = $this->client->get("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'headers' => $this->getHeaders(),
        ]);

        // Log de la réponse contenant tous les messages
        $messagesData = json_decode($response->getBody(), true);
        $this->logger->info("Messages pour le thread {$threadId} : " . json_encode($messagesData));

        return $messagesData;
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2'
        ];
    }
}
