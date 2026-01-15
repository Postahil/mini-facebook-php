<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\FriendshipRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PostRepository $postRepository, FriendshipRepository $friendshipRepository): Response
    {
        $user = $this->getUser();
        $posts = [];

        if ($user) {
            // Récupérer les amis de l'utilisateur
            $friends = $friendshipRepository->findFriends($user);
            $friendsIds = array_map(fn($friend) => $friend->getId(), $friends);
            
            // Ajouter l'utilisateur lui-même pour voir ses propres posts
            $friendsIds[] = $user->getId();

            // Récupérer les posts des amis (y compris les siens)
            if (!empty($friendsIds)) {
                $posts = $postRepository->createQueryBuilder('p')
                    ->where('p.author IN (:friends)')
                    ->setParameter('friends', $friendsIds)
                    ->orderBy('p.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            }
        } else {
            // Si non connecté, afficher tous les posts
            $posts = $postRepository->findAllOrderedByDate();
        }
        
        // Create comment forms for each post (only if user is logged in)
        $commentForms = [];
        if ($user) {
            foreach ($posts as $post) {
                $comment = new Comment();
                $comment->setPost($post);
                $commentForms[$post->getId()] = $this->createForm(CommentType::class, $comment)->createView();
            }
        }

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'commentForms' => $commentForms,
        ]);
    }

    #[Route('/post/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $post->setAuthor($this->getUser());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post publié avec succès !');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/post/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        $commentForms = [];
        if ($this->getUser()) {
            $comment = new Comment();
            $comment->setPost($post);
            $commentForms[$post->getId()] = $this->createForm(CommentType::class, $comment)->createView();
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForms' => $commentForms,
        ]);
    }

    #[Route('/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($post->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce post.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Post supprimé avec succès !');
        }

        return $this->redirectToRoute('app_home');
    }
}
