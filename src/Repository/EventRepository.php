<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Retourne les événements à venir (date >= aujourd'hui), triés par date ASC
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.date >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne tous les événements triés par date DESC
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par titre ou lieu
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.title LIKE :q OR e.location LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}