<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketFormType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tickets')]
#[IsGranted('ROLE_ADMIN')]
final class AdminTicketController extends AbstractController
{
    #[Route('', name: 'app_admin_tickets_index', methods: ['GET'])]
    public function index(Request $request, TicketRepository $ticketRepository): Response
    {
        $status = $request->query->get('status');
        $createdBy = $request->query->get('createdBy');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $page = max(1, (int) $request->query->get('page', 1));

        $pagination = $ticketRepository->paginateForAdmin($status, $createdBy, $dateFrom, $dateTo, $page, 10);

        return $this->render('admin/tickets/index.html.twig', [
            'tickets' => $pagination['items'],
            'selectedStatus' => $status,
            'createdBy' => $createdBy,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'currentPage' => $pagination['currentPage'],
            'totalPages' => $pagination['totalPages'],
            'totalItems' => $pagination['totalItems'],
            'statuses' => [
                'OPENED' => 'Ouvert',
                'IN_PROGRESS' => 'En cours',
                'PENDING' => 'En attente',
                'RESOLVED' => 'Résolu',
                'CLOSED' => 'Fermé',
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_admin_tickets_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        return $this->render('admin/tickets/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_tickets_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TicketFormType::class, $ticket, [
            'include_status' => true,
            'include_affecte_a' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setDateModification(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le ticket a bien été modifié.');

            return $this->redirectToRoute('app_admin_tickets_index');
        }

        return $this->render('admin/tickets/edit.html.twig', [
            'form' => $form->createView(),
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_tickets_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $ticket);

        if ($this->isCsrfTokenValid('delete_ticket_' . $ticket->getId(), $request->request->get('_token'))) {
            $entityManager->remove($ticket);
            $entityManager->flush();

            $this->addFlash('success', 'Le ticket a bien été supprimé.');
        }

        return $this->redirectToRoute('app_admin_tickets_index');
    }
}
