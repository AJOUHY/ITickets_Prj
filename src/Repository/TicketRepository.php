<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function paginateForClient(User $user, ?string $status, int $page, int $limit = 5): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        $totalItems = (int) (clone $qb)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalItems / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $items = $qb
            ->orderBy('t.dateCreation', 'DESC')
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

    public function countByStatus(): array
    {
        $counts = [];

        foreach (TicketStatus::cases() as $status) {
            $counts[$status->name] = (int) $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.status = :status')
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $counts;
    }

    public function paginateForAdmin(?string $status = null, ?string $createdBy = null, ?string $dateFrom = null, ?string $dateTo = null, int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')
            ->orderBy('t.dateCreation', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if ($createdBy !== null && trim($createdBy) !== '') {
            $term = '%' . strtolower(trim($createdBy)) . '%';
            $qb->andWhere('LOWER(u.firstName) LIKE :createdBy OR LOWER(u.lastName) LIKE :createdBy OR LOWER(u.email) LIKE :createdBy')
                ->setParameter('createdBy', $term);
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $qb->andWhere('t.dateCreation >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo !== null && $dateTo !== '') {
            $qb->andWhere('t.dateCreation <= :dateTo')
                ->setParameter('dateTo', (new \DateTime($dateTo))->setTime(23, 59, 59));
        }

        $totalItems = (int) (clone $qb)
            ->select('COUNT(t.id)')
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

    public function searchForAdmin(?string $status = null, ?string $createdBy = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->paginateForAdmin($status, $createdBy, $dateFrom, $dateTo, 1, 100000)['items'];
    }

    public function countByStatusForTechnicien(User $user): array
    {
        $counts = [];

        foreach (TicketStatus::cases() as $status) {
            $counts[$status->name] = (int) $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.affecteA = :user')
                ->andWhere('t.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $counts;
    }

    public function paginateForTechnicien(User $user, int $page = 1, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->createQueryBuilder('t')
            ->where('t.affecteA = :user')
            ->setParameter('user', $user)
            ->orderBy('t.dateCreation', 'DESC');

        $totalItems = (int) (clone $qb)
            ->select('COUNT(t.id)')
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
