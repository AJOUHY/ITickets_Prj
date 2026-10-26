<?php

namespace App\Tests\Controller;

use App\Entity\Categorie;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketLieu;
use App\Enum\TicketStatus;
use App\Enum\TicketType;
use App\Enum\TicketUrgence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TicketAccessControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();

        parent::tearDown();
    }

    private function createUser(array $roles): User
    {
        $user = new User();
        $user->setEmail(sprintf('user_%s@example.com', uniqid('', true)));
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setPassword('secret');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createCategory(): Categorie
    {
        $categorie = new Categorie();
        $categorie->setTitre(sprintf('Catégorie test %s', uniqid('', true)));

        $this->entityManager->persist($categorie);
        $this->entityManager->flush();

        return $categorie;
    }

    private function createTicket(User $owner, ?User $assigned = null): Ticket
    {
        $categorie = $this->createCategory();

        $ticket = new Ticket();
        $ticket->setUser($owner);
        $ticket->setAffecteA($assigned);
        $ticket->setType(TicketType::DEMANDE);
        $ticket->setCategorie($categorie);
        $ticket->setUrgence(TicketUrgence::MOYENNE);
        $ticket->setLieu(TicketLieu::RABAT);
        $ticket->setTitre(sprintf('Ticket test %s', uniqid('', true)));
        $ticket->setDescription('Description de test');
        $ticket->setStatus(TicketStatus::OPENED);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $ticket;
    }

    private function loginAs(User $user): void
    {
        $this->client->loginUser($user);
    }

    private function generateDeleteToken(Ticket $ticket): string
    {
        return 'invalid-token';
    }

    public function testClientCanViewOwnTicket(): void
    {
        $user = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($user);

        $this->client->loginUser($user);
        $this->client->request('GET', '/ticket/' . $ticket->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testClientCannotViewAnotherUsersTicket(): void
    {
        $owner = $this->createUser(['ROLE_CLIENT']);
        $otherClient = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($owner);

        $this->client->loginUser($otherClient);
        $this->client->request('GET', '/ticket/' . $ticket->getId());

        $this->assertResponseRedirects('/access-denied');
    }

    public function testTechnicianCanViewAssignedTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $technician = $this->createUser(['ROLE_TECHNICIEN']);
        $ticket = $this->createTicket($client, $technician);

        $this->client->loginUser($technician);
        $this->client->request('GET', '/technicien/tickets/' . $ticket->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testTechnicianCannotViewUnassignedTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $technician = $this->createUser(['ROLE_TECHNICIEN']);
        $ticket = $this->createTicket($client);

        $this->client->loginUser($technician);
        $this->client->request('GET', '/technicien/tickets/' . $ticket->getId());

        $this->assertResponseRedirects('/access-denied');
    }

    public function testTechnicianCannotAccessStatusPageOfUnassignedTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $technician = $this->createUser(['ROLE_TECHNICIEN']);
        $ticket = $this->createTicket($client);

        $this->client->loginUser($technician);
        $this->client->request('GET', '/technicien/tickets/' . $ticket->getId() . '/status');

        $this->assertResponseRedirects('/access-denied');
    }

    public function testAdminCanDeleteTicket(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $client = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($client);

        $this->client->loginUser($admin);
        $this->client->request('POST', '/admin/tickets/' . $ticket->getId() . '/delete', [
            '_token' => $this->generateDeleteToken($ticket),
        ]);

        $this->assertResponseRedirects('/admin/tickets');
    }

    public function testClientCannotDeleteTicket(): void
    {
        $owner = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($owner);

        $this->client->loginUser($owner);
        $this->client->request('POST', '/admin/tickets/' . $ticket->getId() . '/delete', [
            '_token' => $this->generateDeleteToken($ticket),
        ]);

        $this->assertResponseRedirects('/access-denied');
    }

    public function testClientCanCommentOnOwnTicket(): void
    {
        $user = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($user);

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/ticket/' . $ticket->getId());
        $form = $crawler->selectButton('Envoyer')->form();
        $form['comment_form[message]'] = 'Commentaire client valide';

        $this->client->submit($form);

        $this->assertResponseRedirects('/ticket/' . $ticket->getId());
    }

    public function testTechnicianCanCommentOnAssignedTicket(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);
        $technician = $this->createUser(['ROLE_TECHNICIEN']);
        $ticket = $this->createTicket($client, $technician);

        $this->client->loginUser($technician);
        $crawler = $this->client->request('GET', '/technicien/tickets/' . $ticket->getId());
        $form = $crawler->selectButton('Envoyer')->form();
        $form['comment_form[message]'] = 'Commentaire technicien valide';

        $this->client->submit($form);

        $this->assertResponseRedirects('/technicien/tickets/' . $ticket->getId());
    }

    public function testAnotherUserCannotCommentOnTicket(): void
    {
        $owner = $this->createUser(['ROLE_CLIENT']);
        $otherClient = $this->createUser(['ROLE_CLIENT']);
        $ticket = $this->createTicket($owner);

        $this->client->loginUser($otherClient);
        $this->client->request('POST', '/ticket/' . $ticket->getId(), [
            'comment_form' => ['message' => 'Commentaire interdit'],
        ]);

        $this->assertResponseRedirects('/access-denied');
    }

    public function testCommentAuthorIsDisplayed(): void
    {
        $user = $this->createUser(['ROLE_CLIENT']);
        $user->setFirstName('Alice');
        $user->setLastName('Dupont');
        $this->entityManager->flush();

        $ticket = $this->createTicket($user);

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/ticket/' . $ticket->getId());
        $form = $crawler->selectButton('Envoyer')->form();
        $form['comment_form[message]'] = 'Commentaire avec auteur affiché';

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('body', 'Commentaire avec auteur affiché');
        $this->assertSelectorTextContains('body', 'Alice Dupont');
    }

    public function testTicketFormRejectsMissingRequiredFields(): void
    {
        $user = $this->createUser(['ROLE_CLIENT']);
        $beforeCount = $this->entityManager->getRepository(Ticket::class)->count([]);

        $this->client->loginUser($user);
        $this->client->request('POST', '/ticket/new', [
            'ticket' => [
                'type' => '',
                'categorie' => '',
                'urgence' => '',
                'lieu' => '',
                'titre' => '',
                'description' => '',
            ],
        ]);

        $afterCount = $this->entityManager->getRepository(Ticket::class)->count([]);

        $this->assertResponseIsSuccessful();
        $this->assertSame($beforeCount, $afterCount);
    }
}
