<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Réservations pour un événement donné
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Réservations par email
     */
    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.email = :email')
            ->setParameter('email', $email)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Les N réservations les plus récentes
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}