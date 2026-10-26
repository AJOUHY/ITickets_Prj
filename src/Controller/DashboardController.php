<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard/admin', name: 'app_dashboard_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(TicketRepository $ticketRepository): Response
    {
        $stats = $ticketRepository->countByStatus();

        return $this->render('dashboard/admin.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
        ]);
    }

    #[Route('/dashboard/technicien', name: 'app_dashboard_technicien')]
    #[IsGranted('ROLE_TECHNICIEN')]
    public function technicien(TicketRepository $ticketRepository, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $pagination = $ticketRepository->paginateForTechnicien($this->getUser(), $page, 10);

        return $this->render('dashboard/technicien.html.twig', [
            'user' => $this->getUser(),
            'stats' => $ticketRepository->countByStatusForTechnicien($this->getUser()),
            'tickets' => $pagination['items'],
            'currentPage' => $pagination['currentPage'],
            'totalPages' => $pagination['totalPages'],
            'totalItems' => $pagination['totalItems'],
        ]);
    }

    #[Route('/dashboard/client', name: 'app_dashboard_client')]
    #[IsGranted('ROLE_CLIENT')]
    public function client(Request $request, TicketRepository $ticketRepository): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $status = $request->query->get('status');

        $pagination = $ticketRepository->paginateForClient(
            $this->getUser(),
            $status,
            $page,
            5
        );

        return $this->render('dashboard/client.html.twig', [
            'user' => $this->getUser(),
            'tickets' => $pagination['items'],
            'selectedStatus' => $status,
            'currentPage' => $pagination['currentPage'],
            'totalPages' => $pagination['totalPages'],
            'totalItems' => $pagination['totalItems'],
        ]);
    }
}
