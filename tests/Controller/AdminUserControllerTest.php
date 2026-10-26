<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminUserControllerTest extends WebTestCase
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
        $user->setPassword(static::getContainer()->get('security.user_password_hasher')->hashPassword($user, 'secret'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getDeleteToken(int $userId): string
    {
        return 'invalid-token';
    }

    public function testAdminCanAccessUserIndexPage(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);

        $this->client->loginUser($admin);
        $this->client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
    }

    public function testClientCannotAccessUserAdminRoutes(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($client);
        $this->client->request('GET', '/admin/users');

        $this->assertResponseRedirects('/access-denied');
    }

    public function testNonExistentTicketRedirectsToNotFoundPage(): void
    {
        $client = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($client);
        $this->client->request('GET', '/ticket/999999');

        $this->assertResponseRedirects('/not-found');
    }

    public function testAdminCanCreateUser(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $email = sprintf('alice_%s@example.com', uniqid('', true));

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/new');

        $form = $crawler->selectButton('Créer l\'utilisateur')->form();
        $form['admin_user[firstName]'] = 'Alice';
        $form['admin_user[lastName]'] = 'Martin';
        $form['admin_user[email]'] = $email;
        $form['admin_user[role]'] = 'ROLE_CLIENT';
        $form['admin_user[plainPassword][first]'] = 'Azerty123';
        $form['admin_user[plainPassword][second]'] = 'Azerty123';

        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/users');

        $this->entityManager->clear();
        $created = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($created);
    }

    public function testAdminCanDeleteUser(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $userToDelete = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($admin);
        $this->client->request('POST', '/admin/users/' . $userToDelete->getId() . '/delete', [
            '_token' => $this->getDeleteToken($userToDelete->getId()),
        ]);

        $this->assertResponseRedirects('/admin/users');
    }

    public function testAdminCanEditUser(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $userToEdit = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/' . $userToEdit->getId() . '/edit');

        $form = $crawler->selectButton('Enregistrer les modifications')->form();
        $form['admin_user_edit[firstName]'] = 'Updated';
        $form['admin_user_edit[lastName]'] = 'User';
        $form['admin_user_edit[email]'] = sprintf('updated_%s@example.com', uniqid('', true));
        $form['admin_user_edit[role]'] = 'ROLE_TECHNICIEN';
        $form['admin_user_edit[currentPassword]'] = '';
        $form['admin_user_edit[newPassword][first]'] = '';
        $form['admin_user_edit[newPassword][second]'] = '';

        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/users');

        $this->entityManager->clear();
        $updated = $this->entityManager->getRepository(User::class)->find($userToEdit->getId());
        $this->assertNotNull($updated);
        $this->assertContains('ROLE_TECHNICIEN', $updated->getRoles());
    }

    public function testAdminCanCreateUserWithDifferentRole(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $email = sprintf('tech_%s@example.com', uniqid('', true));

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/new');

        $form = $crawler->selectButton('Créer l\'utilisateur')->form();
        $form['admin_user[firstName]'] = 'Bob';
        $form['admin_user[lastName]'] = 'Tech';
        $form['admin_user[email]'] = $email;
        $form['admin_user[role]'] = 'ROLE_TECHNICIEN';
        $form['admin_user[plainPassword][first]'] = 'Azerty123';
        $form['admin_user[plainPassword][second]'] = 'Azerty123';

        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/users');

        $this->entityManager->clear();
        $created = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($created);
        $this->assertContains('ROLE_TECHNICIEN', $created->getRoles());
    }

    public function testAdminCanEditUserWithoutChangingPassword(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $userToEdit = $this->createUser(['ROLE_CLIENT']);
        $userToEdit->setFirstName('Avant');
        $userToEdit->setLastName('Changement');
        $userToEdit->setEmail(sprintf('before_%s@example.com', uniqid('', true)));
        $this->entityManager->flush();

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/' . $userToEdit->getId() . '/edit');

        $form = $crawler->selectButton('Enregistrer les modifications')->form();
        $form['admin_user_edit[firstName]'] = 'Après';
        $form['admin_user_edit[lastName]'] = 'Edition';
        $form['admin_user_edit[email]'] = sprintf('after_%s@example.com', uniqid('', true));
        $form['admin_user_edit[role]'] = 'ROLE_CLIENT';
        $form['admin_user_edit[currentPassword]'] = '';
        $form['admin_user_edit[newPassword][first]'] = '';
        $form['admin_user_edit[newPassword][second]'] = '';

        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/users');

        $this->entityManager->clear();
        $updated = $this->entityManager->getRepository(User::class)->find($userToEdit->getId());
        $this->assertNotNull($updated);
        $this->assertSame('Après', $updated->getFirstName());
        $this->assertSame('Edition', $updated->getLastName());
    }

    public function testAdminCanEditUserWithValidPassword(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $userToEdit = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/' . $userToEdit->getId() . '/edit');

        $form = $crawler->selectButton('Enregistrer les modifications')->form();
        $form['admin_user_edit[firstName]'] = 'Updated';
        $form['admin_user_edit[lastName]'] = 'User';
        $form['admin_user_edit[email]'] = sprintf('validpw_%s@example.com', uniqid('', true));
        $form['admin_user_edit[role]'] = 'ROLE_TECHNICIEN';
        $form['admin_user_edit[currentPassword]'] = 'secret';
        $form['admin_user_edit[newPassword][first]'] = 'Secret2025';
        $form['admin_user_edit[newPassword][second]'] = 'Secret2025';

        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/users');

        $this->entityManager->clear();
        $updated = $this->entityManager->getRepository(User::class)->find($userToEdit->getId());
        $this->assertNotNull($updated);
        $this->assertContains('ROLE_TECHNICIEN', $updated->getRoles());
        $this->assertTrue(static::getContainer()->get('security.user_password_hasher')->isPasswordValid($updated, 'Secret2025'));
    }

    public function testAdminCannotEditUserWithInvalidPassword(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $userToEdit = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/' . $userToEdit->getId() . '/edit');

        $form = $crawler->selectButton('Enregistrer les modifications')->form();
        $form['admin_user_edit[firstName]'] = 'Bad';
        $form['admin_user_edit[lastName]'] = 'Password';
        $form['admin_user_edit[email]'] = sprintf('invalidpw_%s@example.com', uniqid('', true));
        $form['admin_user_edit[role]'] = 'ROLE_CLIENT';
        $form['admin_user_edit[currentPassword]'] = 'wrong-password';
        $form['admin_user_edit[newPassword][first]'] = 'Secret2025';
        $form['admin_user_edit[newPassword][second]'] = 'Secret2025';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Le mot de passe actuel est obligatoire et incorrect pour modifier le mot de passe.');
    }

    public function testAdminCannotCreateUserWithInvalidEmail(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/new');

        $form = $crawler->selectButton('Créer l\'utilisateur')->form();
        $form['admin_user[firstName]'] = 'Alice';
        $form['admin_user[lastName]'] = 'Martin';
        $form['admin_user[email]'] = 'email-invalide';
        $form['admin_user[role]'] = 'ROLE_CLIENT';
        $form['admin_user[plainPassword][first]'] = 'Azerty123';
        $form['admin_user[plainPassword][second]'] = 'Azerty123';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'L\'email n\'est pas valide.');
    }

    public function testAdminCannotCreateUserWithShortPassword(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/new');

        $form = $crawler->selectButton('Créer l\'utilisateur')->form();
        $form['admin_user[firstName]'] = 'Alice';
        $form['admin_user[lastName]'] = 'Martin';
        $form['admin_user[email]'] = sprintf('shortpw_%s@example.com', uniqid('', true));
        $form['admin_user[role]'] = 'ROLE_CLIENT';
        $form['admin_user[plainPassword][first]'] = '123';
        $form['admin_user[plainPassword][second]'] = '123';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Le mot de passe doit contenir au moins 6 caractères.');
    }

    public function testAdminCannotCreateUserWithExistingEmail(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $existing = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/new');

        $form = $crawler->selectButton('Créer l\'utilisateur')->form();
        $form['admin_user[firstName]'] = 'Dup';
        $form['admin_user[lastName]'] = 'licate';
        $form['admin_user[email]'] = $existing->getEmail();
        $form['admin_user[role]'] = 'ROLE_CLIENT';
        $form['admin_user[plainPassword][first]'] = 'Azerty123';
        $form['admin_user[plainPassword][second]'] = 'Azerty123';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Un utilisateur avec cet email existe déjà.');
    }

    public function testAdminCannotEditUserWithWrongCurrentPassword(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $userToEdit = $this->createUser(['ROLE_CLIENT']);

        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/' . $userToEdit->getId() . '/edit');

        $form = $crawler->selectButton('Enregistrer les modifications')->form();
        $form['admin_user_edit[firstName]'] = 'Updated';
        $form['admin_user_edit[lastName]'] = 'User';
        $form['admin_user_edit[email]'] = sprintf('wrong_current_password_%s@example.com', uniqid('', true));
        $form['admin_user_edit[role]'] = 'ROLE_CLIENT';
        $form['admin_user_edit[currentPassword]'] = 'bad-password';
        $form['admin_user_edit[newPassword][first]'] = 'NewPassword123';
        $form['admin_user_edit[newPassword][second]'] = 'NewPassword123';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Le mot de passe actuel est obligatoire et incorrect pour modifier le mot de passe.');
    }
}
