<?php

namespace App\Controller;

use App\Form\ChatFormType;
use App\Service\OpenAIClient;
use Pusher\Pusher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ChatGPTController extends AbstractController
{
    private $pusher;
    private $openAIClient;

    public function __construct(Pusher $pusher, OpenAIClient $openAIClient)
    {
        $this->pusher = $pusher;
        $this->openAIClient = $openAIClient;
    }

    #[Route('/chat', name: 'send_message')]
    public function sendMessage(Request $request): JsonResponse
    {
        // Handle form submission and OpenAI request
        $form = $this->createForm(ChatFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $question = $data['message'];

            // Step 1: Envoyer la question à OpenAI et récupérer la réponse
            $thread = $this->openAIClient->createThread();
            $this->openAIClient->addMessage($thread['id'], $question);
            $response = $this->openAIClient->runAssistant($thread['id']);
            $assistantResponse = $response['choices'][0]['message']['content'] ?? 'Aucune réponse disponible';

            // Step 2: Publier le message et la réponse via Pusher
            $this->pusher->trigger('chat-channel', 'new-message', [
                'message' => $question,
                'response' => $assistantResponse
            ]);

            return new JsonResponse([
                'status' => 'Message et réponse envoyés',
                'message' => $question,
                'response' => $assistantResponse
            ]);
        }

        return new JsonResponse(['status' => 'Erreur dans le formulaire']);
    }
}
