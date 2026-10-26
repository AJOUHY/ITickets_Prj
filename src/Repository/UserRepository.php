<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function searchForAdmin(?string $role = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC');

        if ($role !== null && $role !== '') {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"' . $role . '"%');
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(u.firstName) LIKE :search OR LOWER(u.lastName) LIKE :search')
                ->setParameter('search', $term);
        }

        return $qb->getQuery()->getResult();
    }

    public function paginateForAdmin(?string $role = null, ?string $search = null, int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC');

        if ($role !== null && $role !== '') {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"' . $role . '"%');
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(u.firstName) LIKE :search OR LOWER(u.lastName) LIKE :search OR LOWER(u.email) LIKE :search')
                ->setParameter('search', $term);
        }

        $totalItems = (int) (clone $qb)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalItems / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $items = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
        ];
    }
}
