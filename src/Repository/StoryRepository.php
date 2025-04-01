<?php

namespace App\Repository;

use App\Entity\Story;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Story::class);
    }

    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s, t, o')  // Explicitly select all joined entities
            ->leftJoin('s.templates', 't')
            ->leftJoin('s.owner', 'o')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    // }
        // For debugging, let's see the SQL
        // $query = $qb->getQuery();
        // dump($query->getSQL());

        // return $query->getResult();
    }

    public function findByOwner(int $ownerId): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.templates', 't')
            ->addSelect('t')
            ->leftJoin('s.owner', 'o')
            ->addSelect('o')
            ->andWhere('s.owner = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTemplate(int $templateId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.templates', 't')
            ->andWhere('t.id = :templateId')
            ->setParameter('templateId', $templateId)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}