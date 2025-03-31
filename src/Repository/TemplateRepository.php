<?php

namespace App\Repository;

use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOwner(int $ownerId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.owner = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}