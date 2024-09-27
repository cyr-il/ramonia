<?php

namespace App\Service;

use GuzzleHttp\Client;

class OpenAIClient
{
    private $client;
    private $apiKey;
    private $assistantId;

    public function __construct(string $apiKey, string $assistantId)
    {
        $this->client = new Client();
        $this->apiKey = $apiKey;
        $this->assistantId = $assistantId;
    }


    public function createThread()
    {
        $response = $this->client->post('https://api.openai.com/v1/threads', [
            'headers' => $this->getHeaders()
        ]);

        return json_decode($response->getBody(), true);
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

        return json_decode($response->getBody(), true);
    }

    public function runAssistant($threadId)
    {
        $response = $this->client->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
            'headers' => $this->getHeaders(),
            'json' => [
                'assistant_id' => $this->assistantId
            ]
        ]);
        return json_decode($response->getBody(), true);
    }

    public function getRunStatus($threadId, $runId)
    {
        $response = $this->client->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}", [
            'headers' => $this->getHeaders(),
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getFinalResponse($threadId, $runId)
    {
        $response = $this->client->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}/steps", [
            'headers' => $this->getHeaders(),
        ]);

        return json_decode($response->getBody(), true);
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2'
        ];
    }

    public function getMessageContent($threadId, $messageId)
    {
        $response = $this->client->get("https://api.openai.com/v1/threads/{$threadId}/messages/{$messageId}", [
            'headers' => $this->getHeaders(),
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getAllMessages($threadId)
    {
        $response = $this->client->get("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'headers' => $this->getHeaders(),
        ]);

        return json_decode($response->getBody(), true);
    }

}
