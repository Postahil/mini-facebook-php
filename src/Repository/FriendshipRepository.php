<?php

namespace App\Repository;

use App\Entity\Friendship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Friendship>
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    public function findFriendship(User $user, User $friend): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where('(f.user = :user AND f.friend = :friend) OR (f.user = :friend AND f.friend = :user)')
            ->setParameter('user', $user)
            ->setParameter('friend', $friend)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function findFriends(User $user): array
    {
        $friendships = $this->createQueryBuilder('f')
            ->where('f.status = :status')
            ->andWhere('(f.user = :user OR f.friend = :user)')
            ->setParameter('status', 'accepted')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $friends = [];
        foreach ($friendships as $friendship) {
            if ($friendship->getUser() === $user) {
                $friends[] = $friendship->getFriend();
            } else {
                $friends[] = $friendship->getUser();
            }
        }

        return $friends;
    }

    /**
     * @return Friendship[]
     */
    public function findPendingRequests(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.friend = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getResult();
    }
}
