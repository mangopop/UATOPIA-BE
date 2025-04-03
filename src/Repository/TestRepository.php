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

    public function findByTemplateId(int $templateId): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.categories', 'c')
            ->addSelect('c')
            ->leftJoin('t.owner', 'o')
            ->addSelect('o')
            ->innerJoin('t.templates', 'template')
            ->andWhere('template.id = :templateId')
            ->setParameter('templateId', $templateId)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{data: Test[], total: int}
     */
    public function findAllSortedPaginated(int $page = 1, int $limit = 30): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC');

        // Get total count
        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Get paginated results
        $query = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return [
            'data' => $query->getResult(),
            'total' => $total
        ];
    }

    /**
     * @return array{data: Test[], total: int}
     */
    public function searchTests(
        ?string $query = null,
        ?array $categoryIds = null,
        int $page = 1,
        int $limit = 30
    ): array {
        $queryBuilder = $this->createQueryBuilder('t')
            ->leftJoin('t.categories', 'c')
            ->leftJoin('t.owner', 'o')
            ->addSelect('c')
            ->addSelect('o');

        if ($query) {
            $queryBuilder
                ->andWhere('t.name LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($categoryIds) {
            $queryBuilder
                ->andWhere('c.id IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds);
        }

        $queryBuilder->orderBy('t.name', 'ASC');

        // Get total count
        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(DISTINCT t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Get paginated results
        $query = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return [
            'data' => $query->getResult(),
            'total' => $total
        ];
    }
}