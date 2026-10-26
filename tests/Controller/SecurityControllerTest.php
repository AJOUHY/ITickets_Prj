<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
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

    private function createUser(array $roles = ['ROLE_CLIENT']): User
    {
        $user = new User();
        $user->setEmail(sprintf('security_%s@example.com', uniqid('', true)));
        $user->setFirstName('Security');
        $user->setLastName('Tester');
        $user->setRoles($roles);
        $user->setPassword(static::getContainer()->get('security.user_password_hasher')->hashPassword($user, 'secret123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testLoginPageIsAccessibleAnonymously(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testAuthenticatedUserIsRedirectedToDashboardWhenVisitingLoginPage(): void
    {
        $user = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/login');

        $this->assertResponseRedirects('/dashboard');
    }

    public function testLogoutDestroysTheUserSession(): void
    {
        $user = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/logout');
        $this->client->request('GET', '/dashboard');

        $this->assertResponseRedirects('/login');
    }

    public function testProtectedRoutesRedirectToLoginWhenUserIsAnonymous(): void
    {
        foreach (['/dashboard', '/admin/users'] as $route) {
            $this->client->request('GET', $route);
            $this->assertResponseRedirects('/login');
        }
    }
}
