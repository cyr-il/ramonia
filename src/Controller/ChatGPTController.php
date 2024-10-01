<?php

namespace App\Controller;

use App\Form\ChatFormType;
use App\Service\OpenAIClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;  // Ajoute cette ligne pour le logger


class ChatGPTController extends AbstractController
{
    private $openAIClient;
    private $logger;

    public function __construct(OpenAIClient $openAIClient, LoggerInterface $logger)
    {
        $this->openAIClient = $openAIClient;
        $this->logger = $logger;
    }

    // Route pour afficher la page de chat
    #[Route('/chat', name: 'chat_index', methods: ['GET'])]
    public function index(): Response
    {
        $form = $this->createForm(ChatFormType::class);
        return $this->render('chat_gpt/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Route pour envoyer la question et recevoir la réponse
    #[Route('/chat/send', name: 'ask_assistant', methods: ['POST'])]
    public function ask(Request $request): Response
    {
        $form = $this->createForm(ChatFormType::class);
        $form->handleRequest($request);

        $question = null;
        $finalResponse = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $question = $data['message'];

            if ($question) {
                // Créer le thread et ajouter la question
                $thread = $this->openAIClient->createThread();
                $this->openAIClient->addMessage($thread['id'], $question);
                
                // Log l'ID du thread créé
                $this->logger->info('Thread créé : ' . $thread['id']);

                // Exécuter l'assistant
                $run = $this->openAIClient->runAssistant($thread['id']);

                // Attendre que l'assistant ait terminé de traiter
                while ($run['status'] === 'queued') {
                    sleep(2);
                    $run = $this->openAIClient->getRunStatus($thread['id'], $run['id']);
                }

                // Log le statut final du run
                $this->logger->info('Statut du run : ' . $run['status']);

                // Si le processus est terminé, récupérer les messages
                if ($run['status'] === 'completed') {
                    $messages = $this->openAIClient->getAllMessages($thread['id']);

                    // Log tous les messages retournés par OpenAI
                    $this->logger->info('Messages OpenAI : ' . json_encode($messages));

                    // Assurer la structure correcte pour récupérer la réponse finale de GPT
                    if (!empty($messages['data'])) {
                        $lastMessage = end($messages['data']);
                        
                        if (isset($lastMessage['content'][0]['text']['value'])) {
                            $finalResponse = $lastMessage['content'][0]['text']['value'];
                        } else {
                            $finalResponse = "Pas de réponse trouvée dans les messages.";
                        }

                        // Log la réponse finale extraite
                        $this->logger->info('Réponse extraite : ' . $finalResponse);
                    } else {
                        $finalResponse = "Aucun message retourné par l'assistant.";
                    }
                }
            }
        }

        // Si la requête est AJAX, retourner le résultat sous forme de JSON
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'question' => $question,
                'response' => $finalResponse,  // Retourne bien la réponse ici
            ]);
        }

        // Sinon, rendre la vue Twig
        return $this->render('chat_gpt/index.html.twig', [
            'question' => $question,
            'response' => $finalResponse,
            'form' => $this->createForm(ChatFormType::class)->createView(),
        ]);
    }
}
