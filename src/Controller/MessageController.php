<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\FriendshipRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MessageController extends AbstractController
{
    #[Route('/messages', name: 'app_messages', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(MessageRepository $messageRepository): Response
    {
        $user = $this->getUser();
        $conversations = $messageRepository->findConversationPartners($user);
        $unreadCount = count($messageRepository->findUnreadMessages($user));

        return $this->render('message/index.html.twig', [
            'conversations' => $conversations,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/messages/conversation/{id}', name: 'app_messages_conversation', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function conversation(
        User $recipient,
        Request $request,
        MessageRepository $messageRepository,
        FriendshipRepository $friendshipRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Vérifier qu'ils sont amis
        if ($user !== $recipient) {
            $friendship = $friendshipRepository->findFriendship($user, $recipient);
            if (!$friendship || !$friendship->isAccepted()) {
                $this->addFlash('error', 'Vous devez être ami avec cet utilisateur pour lui envoyer des messages.');
                return $this->redirectToRoute('app_messages');
            }
        }

        $messages = $messageRepository->findConversation($user, $recipient);

        // Marquer les messages comme lus
        foreach ($messages as $message) {
            if ($message->getReceiver() === $user && !$message->isRead()) {
                $message->setIsRead(true);
            }
        }
        $entityManager->flush();

        $message = new Message();
        $message->setSender($user);
        $message->setReceiver($recipient);

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            $this->addFlash('success', 'Message envoyé !');
            return $this->redirectToRoute('app_messages_conversation', ['id' => $recipient->getId()]);
        }

        return $this->render('message/conversation.html.twig', [
            'recipient' => $recipient,
            'messages' => $messages,
            'form' => $form,
        ]);
    }

    #[Route('/messages/send/{id}', name: 'app_messages_send', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function send(
        User $recipient,
        Request $request,
        FriendshipRepository $friendshipRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Vérifier qu'ils sont amis
        if ($user !== $recipient) {
            $friendship = $friendshipRepository->findFriendship($user, $recipient);
            if (!$friendship || !$friendship->isAccepted()) {
                $this->addFlash('error', 'Vous devez être ami avec cet utilisateur pour lui envoyer des messages.');
                return $this->redirectToRoute('app_messages');
            }
        }

        $content = $request->request->get('content');
        if ($content && trim($content)) {
            $message = new Message();
            $message->setSender($user);
            $message->setReceiver($recipient);
            $message->setContent(trim($content));

            $entityManager->persist($message);
            $entityManager->flush();

            $this->addFlash('success', 'Message envoyé !');
        }

        return $this->redirectToRoute('app_messages_conversation', ['id' => $recipient->getId()]);
    }
}
