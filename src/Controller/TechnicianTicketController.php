<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Form\CommentFormType;
use App\Form\TicketStatusType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/technicien/tickets')]
#[IsGranted('ROLE_TECHNICIEN')]
final class TechnicianTicketController extends AbstractController
{
    #[Route('/{id}', name: 'app_ticket_show_technicien', requirements: ['id' => '\\d+'])]
    public function show(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $ticket);

        $comment = new Comment();
        $commentForm = $this->createForm(CommentFormType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $this->denyAccessUnlessGranted('CHANGE_STATUS', $ticket);

            $comment->setTicket($ticket);
            $comment->setUser($this->getUser());
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a bien été ajouté.');

            return $this->redirectToRoute('app_ticket_show_technicien', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/show_technicien.html.twig', [
            'ticket' => $ticket,
            'commentForm' => $commentForm->createView(),
            'comments' => $ticket->getComments(),
        ]);
    }

    #[Route('/{id}/status', name: 'app_ticket_edit_status', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function editStatus(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('CHANGE_STATUS', $ticket);

        $form = $this->createForm(TicketStatusType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setDateModification(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le statut du ticket a bien été mis à jour.');

            return $this->redirectToRoute('app_ticket_show_technicien', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/edit_status.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }
}
