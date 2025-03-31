<?php

namespace App\Repository;

use App\Entity\Test;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Test::class);
    }

    /**
     * @return Test[]
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Test[]
     */
    public function findByNamePattern(string $pattern): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.name LIKE :pattern')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Test[]
     */
    public function findByOwner(int $ownerId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.owner = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}