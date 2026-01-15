<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Post;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LikeController extends AbstractController
{
    #[Route('/post/{id}/like', name: 'app_post_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleLike(
        Request $request,
        Post $post,
        LikeRepository $likeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('like' . $post->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();

        // Vérifier si l'utilisateur a déjà liké ce post
        $existingLike = $likeRepository->findOneByUserAndPost($user, $post);

        if ($existingLike) {
            // Retirer le like
            $entityManager->remove($existingLike);
            $entityManager->flush();
            $this->addFlash('info', 'Like retiré');
        } else {
            // Ajouter un like
            $like = new Like();
            $like->setUser($user);
            $like->setPost($post);
            $entityManager->persist($like);
            $entityManager->flush();
            $this->addFlash('success', 'Post liké !');
        }

        return $this->redirectToRoute('app_home');
    }
}
