<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardControllerTest extends WebTestCase
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
        $user->setEmail(sprintf('dashboard_%s@example.com', uniqid('', true)));
        $user->setFirstName('Dashboard');
        $user->setLastName('Tester');
        $user->setRoles($roles);
        $user->setPassword(static::getContainer()->get('security.user_password_hasher')->hashPassword($user, 'secret123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testAnonymousUserIsRedirectedToLoginFromDashboard(): void
    {
        $this->client->request('GET', '/dashboard');

        $this->assertResponseRedirects('/login');
    }

    public function testAdminUserIsRedirectedToAdminDashboard(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);

        $this->client->loginUser($admin);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseRedirects('/dashboard/admin');
    }

    public function testTechnicianUserIsRedirectedToTechnicianDashboard(): void
    {
        $technician = $this->createUser(['ROLE_TECHNICIEN']);

        $this->client->loginUser($technician);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseRedirects('/dashboard/technicien');
    }

    public function testClientUserIsRedirectedToClientDashboard(): void
    {
        $clientUser = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($clientUser);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseRedirects('/dashboard/client');
    }
}
