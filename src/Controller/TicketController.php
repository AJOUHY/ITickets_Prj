<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Form\CommentFormType;
use App\Form\TicketFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
final class TicketController extends AbstractController
{
    #[Route('/ticket/new', name: 'app_ticket_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketFormType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $ticket->setUser($user);
            $ticket->setStatus(TicketStatus::OPENED);
            $entityManager->persist($ticket);
            $entityManager->flush();

            $this->addFlash('success', 'Votre ticket a bien été créé.');

            return $this->redirectToRoute('app_dashboard_client');
        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/ticket/filter', name: 'app_ticket_filter')]
    public function filter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $status = $request->query->get('status');
        $criteria = ['user' => $this->getUser()];

        if ($status) {
            $criteria['status'] = $status;
        }

        $tickets = $entityManager->getRepository(Ticket::class)->findBy($criteria);

        return $this->render('dashboard/client.html.twig', [
            'tickets' => $tickets,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/ticket/{id}', name: 'app_ticket_show')]
    public function show(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $ticket);

        $comment = new Comment();
        $commentForm = $this->createForm(CommentFormType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $this->denyAccessUnlessGranted('EDIT', $ticket);

            $comment->setTicket($ticket);
            $comment->setUser($this->getUser());
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a bien été ajouté.');

            return $this->redirectToRoute('app_ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'commentForm' => $commentForm->createView(),
            'comments' => $ticket->getComments(),
        ]);
    }
}
