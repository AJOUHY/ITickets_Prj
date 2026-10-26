<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $firstName = trim((string) $request->request->get('firstName', ''));
            $lastName = trim((string) $request->request->get('lastName', ''));
            $email = trim((string) $request->request->get('email', ''));
            $subject = trim((string) $request->request->get('subject', ''));
            $message = trim((string) $request->request->get('message', ''));

            if (!$firstName || !$lastName || !$email || !$subject || !$message) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->render('contact/index.html.twig');
            }

            $contactMessage = new ContactMessage();
            $contactMessage->setFirstName($firstName);
            $contactMessage->setLastName($lastName);
            $contactMessage->setEmail($email);
            $contactMessage->setSubject($subject);
            $contactMessage->setMessage($message);

            $entityManager->persist($contactMessage);
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons rapidement.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig');
    }
}
