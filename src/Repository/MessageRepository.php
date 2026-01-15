<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[]
     */
    public function findConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Message[]
     */
    public function findUnreadMessages(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.receiver = :user')
            ->andWhere('m.isRead = :read')
            ->setParameter('user', $user)
            ->setParameter('read', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findConversationPartners(User $user): array
    {
        // Messages envoyés
        $sent = $this->createQueryBuilder('m')
            ->select('DISTINCT IDENTITY(m.receiver) as partnerId')
            ->where('m.sender = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        // Messages reçus
        $received = $this->createQueryBuilder('m')
            ->select('DISTINCT IDENTITY(m.sender) as partnerId')
            ->where('m.receiver = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $partnerIds = array_unique(array_merge(
            array_column($sent, 'partnerId'),
            array_column($received, 'partnerId')
        ));

        if (empty($partnerIds)) {
            return [];
        }

        return $this->getEntityManager()
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $partnerIds)
            ->getQuery()
            ->getResult();
    }
}
