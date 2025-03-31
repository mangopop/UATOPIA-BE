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
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOwner(int $ownerId): array
    {
        return $this->createQueryBuilder('s')
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