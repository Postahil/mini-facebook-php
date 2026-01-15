<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FriendshipController extends AbstractController
{
    #[Route('/friends', name: 'app_friends', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(FriendshipRepository $friendshipRepository): Response
    {
        $user = $this->getUser();
        $friends = $friendshipRepository->findFriends($user);
        $pendingRequests = $friendshipRepository->findPendingRequests($user);

        return $this->render('friendship/index.html.twig', [
            'friends' => $friends,
            'pendingRequests' => $pendingRequests,
        ]);
    }

    #[Route('/friends/search', name: 'app_friends_search', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function search(Request $request, UserRepository $userRepository): Response
    {
        $query = $request->query->get('q', '');
        $users = [];

        if ($query) {
            $users = $userRepository->createQueryBuilder('u')
                ->where('u.email LIKE :query OR u.firstname LIKE :query OR u.lastname LIKE :query')
                ->andWhere('u != :currentUser')
                ->setParameter('query', '%' . $query . '%')
                ->setParameter('currentUser', $this->getUser())
                ->setMaxResults(20)
                ->getQuery()
                ->getResult();
        }

        return $this->render('friendship/search.html.twig', [
            'users' => $users,
            'query' => $query,
        ]);
    }

    #[Route('/friends/request/{id}', name: 'app_friends_request', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function request(
        User $friend,
        FriendshipRepository $friendshipRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if ($user === $friend) {
            $this->addFlash('error', 'Vous ne pouvez pas vous ajouter vous-même comme ami.');
            return $this->redirectToRoute('app_friends_search');
        }

        // Vérifier si une demande existe déjà
        $existingFriendship = $friendshipRepository->findFriendship($user, $friend);

        if ($existingFriendship) {
            if ($existingFriendship->isAccepted()) {
                $this->addFlash('info', 'Vous êtes déjà ami avec cet utilisateur.');
            } elseif ($existingFriendship->isPending()) {
                if ($existingFriendship->getUser() === $user) {
                    $this->addFlash('info', 'Vous avez déjà envoyé une demande d\'amitié à cet utilisateur.');
                } else {
                    // Accepter automatiquement si c'est l'autre utilisateur qui a envoyé la demande
                    $existingFriendship->setStatus('accepted');
                    $entityManager->flush();
                    $this->addFlash('success', 'Demande d\'amitié acceptée !');
                }
            }
        } else {
            // Créer une nouvelle demande d'amitié
            $friendship = new Friendship();
            $friendship->setUser($user);
            $friendship->setFriend($friend);
            $friendship->setStatus('pending');

            $entityManager->persist($friendship);
            $entityManager->flush();

            $this->addFlash('success', 'Demande d\'amitié envoyée !');
        }

        return $this->redirectToRoute('app_friends_search');
    }

    #[Route('/friends/accept/{id}', name: 'app_friends_accept', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function accept(
        Friendship $friendship,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('accept' . $friendship->getId(), $request->request->get('_token'))) {
            if ($friendship->getFriend() !== $this->getUser()) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas accepter cette demande.');
            }

            $friendship->setStatus('accepted');
            $entityManager->flush();

            $this->addFlash('success', 'Demande d\'amitié acceptée !');
        }

        return $this->redirectToRoute('app_friends');
    }

    #[Route('/friends/reject/{id}', name: 'app_friends_reject', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reject(
        Friendship $friendship,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('reject' . $friendship->getId(), $request->request->get('_token'))) {
            if ($friendship->getFriend() !== $this->getUser()) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas rejeter cette demande.');
            }

            $entityManager->remove($friendship);
            $entityManager->flush();

            $this->addFlash('success', 'Demande d\'amitié rejetée.');
        }

        return $this->redirectToRoute('app_friends');
    }

    #[Route('/friends/remove/{id}', name: 'app_friends_remove', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function remove(
        User $friend,
        FriendshipRepository $friendshipRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('remove' . $friend->getId(), $request->request->get('_token'))) {
            $friendship = $friendshipRepository->findFriendship($this->getUser(), $friend);

            if ($friendship && $friendship->isAccepted()) {
                $entityManager->remove($friendship);
                $entityManager->flush();
                $this->addFlash('success', 'Ami supprimé de votre liste.');
            }
        }

        return $this->redirectToRoute('app_friends');
    }
}
