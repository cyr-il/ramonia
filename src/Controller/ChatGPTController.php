<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Form\ChatFormType;
use App\Repository\ChatMessageRepository;
use App\Service\OpenAIClient;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/chat/send', name: 'ask_assistant', methods: ['POST'])]
    public function ask(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ChatFormType::class);
        $form->handleRequest($request);
    
        $question = null;
        $finalResponse = null;
    
        // Récupérer le thread ID depuis la session (si disponible)
        $session = $request->getSession();
        $threadId = $request->request->get('threadId') ?? $request->query->get('threadId');

        if (!$threadId) {
            $threadId = $request->getSession()->get('openai_thread_id');
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $question = $data['message'];
    
            if ($question) {
                // Si le thread n'existe pas, en créer un nouveau
                if (!$threadId) {
                    $thread = $this->openAIClient->createThread();
                    $threadId = $thread['id'];
                    $session->set('openai_thread_id', $threadId); // Stocker le thread ID dans la session
                }
    
                // Ajouter le message de l'utilisateur au thread
                $this->openAIClient->addMessage($threadId, $question);
    
                // Sauvegarder le message dans la base de données
                $message = new ChatMessage();
                $message->setThreadId($threadId);
                $message->setContent($question);
                $message->setRole('user');
                $message->setCreatedAt(new \DateTime());
                $em->persist($message);
    
                // Exécuter l'assistant OpenAI dans le même thread
                $run = $this->openAIClient->runAssistant($threadId);
    
                // Boucle pour attendre que l'assistant ait fini de traiter la requête
                while ($run['status'] === 'queued' || $run['status'] === 'in_progress') {
                    sleep(2);
                    $run = $this->openAIClient->getRunStatus($threadId, $run['id']);
                }
    
                // Vérifier si le run est terminé et récupérer les messages
                if ($run['status'] === 'completed') {
                    $messages = $this->openAIClient->getAllMessages($threadId);
                    $this->logger->info('Messages retournés par OpenAI après completion: ' . json_encode($messages));

                    // Parcourir tous les messages pour récupérer la réponse de l'assistant
                    foreach ($messages['data'] as $messageData) {
                        if ($messageData['role'] === 'assistant' && isset($messageData['content'][0]['text']['value'])) {
                            $finalResponse = $messageData['content'][0]['text']['value'];
    
                            // Sauvegarder la réponse dans la base de données
                            $assistantMessage = new ChatMessage();
                            $assistantMessage->setThreadId($threadId);
                            $assistantMessage->setContent($finalResponse);
                            $assistantMessage->setRole('assistant');
                            $assistantMessage->setCreatedAt(new \DateTime());
                            $em->persist($assistantMessage);
    
                            break; // Sortir de la boucle dès que la réponse est trouvée
                        }
                    }
    
                    // Si aucune réponse n'a été trouvée
                    if ($finalResponse === null) {
                        $finalResponse = "Aucune réponse trouvée dans les messages.";
                    }
                }
    
                // Sauvegarder toutes les modifications en base de données
                $em->flush();
            }
        }
    
        // Retourner la réponse au format JSON si la requête est AJAX
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'question' => $question,
                'response' => $finalResponse,
                'threadId' => $threadId,
            ]);
        }
    
        // Sinon, rendre la vue Twig
        return $this->render('chat_gpt/index.html.twig', [
            'question' => $question,
            'response' => $finalResponse,
            'form' => $this->createForm(ChatFormType::class)->createView(),
            'slectedThreadId' => $threadId,
        ]);
    }

    #[Route('/chat/{threadId?}', name: 'chat_index', methods: ['GET'])]
    public function index(Request $request, ?string $threadId, ChatMessageRepository $messageRepository, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();

        // Si un threadId est passé dans l'URL, on l'utilise et on l'enregistre dans la session
        if ($threadId) {
            $session->set('openai_thread_id', $threadId);
        } else {
            // Sinon, on récupère le threadId depuis la session (si disponible)
            $threadId = $session->get('openai_thread_id');
        }

        // Récupérer la liste de tous les threads (chats existants)
        $chatList = $messageRepository->findAllThreadIds();  // Méthode à implémenter

        // Si un thread est sélectionné, récupérer les messages associés
        $messages = [];
        if ($threadId) {
            $messages = $messageRepository->findBy(['threadId' => $threadId], ['createdAt' => 'ASC']);
        }

        // Formulaire pour envoyer un message
        $form = $this->createForm(ChatFormType::class);

        return $this->render('chat_gpt/index.html.twig', [
            'form' => $form->createView(),
            'chatList' => $chatList,  // Liste des threads
            'messages' => $messages,  // Messages du chat sélectionné
            'selectedThreadId' => $threadId  // Thread actuellement sélectionné
        ]);
    }

    // Route pour créer un nouveau chat (thread)
    #[Route('/chat/new', name: 'chat_new', methods: ['GET'])]
    public function newChat(Request $request): Response
    {
        // Création d'un nouveau thread via OpenAIClient
        $thread = $this->openAIClient->createThread();

        // Vérification que l'ID est généré correctement
        if (isset($thread['id']) && $thread['id']) {

            $request->getSession()->set('openai_thread_id', $thread['id']);
            // Redirection vers la page du nouveau thread
            return $this->redirectToRoute('chat_index', [
                'threadId' => $thread['id'],  // Passer le nouvel ID du thread
            ]);
        }

        // En cas d'échec, rediriger vers l'index avec un message d'erreur
        $this->logger->error('Échec de la création du nouveau thread.');
        return $this->redirectToRoute('chat_index');
    }
}
