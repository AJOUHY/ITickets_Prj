<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Form\AdminUserEditType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUserController extends AbstractController
{
    #[Route('', name: 'app_admin_users_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $role = $request->query->get('role');
        $search = $request->query->get('search');
        $page = max(1, (int) $request->query->get('page', 1));

        $pagination = $userRepository->paginateForAdmin($role, $search, $page, 10);

        return $this->render('admin/users/index.html.twig', [
            'users' => $pagination['items'],
            'selectedRole' => $role,
            'searchTerm' => $search,
            'currentPage' => $pagination['currentPage'],
            'totalPages' => $pagination['totalPages'],
            'totalItems' => $pagination['totalItems'],
            'roles' => [
                'ROLE_ADMIN' => 'Admin',
                'ROLE_TECHNICIEN' => 'Technicien',
                'ROLE_CLIENT' => 'Client',
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_admin_users_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'roles' => [
                'ROLE_ADMIN' => 'Admin',
                'ROLE_TECHNICIEN' => 'Technicien',
                'ROLE_CLIENT' => 'Client',
            ],
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_users_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');
        }

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/new', name: 'app_admin_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user, [
            'default_role' => 'ROLE_CLIENT',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $user->getEmail();
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($existingUser) {
                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                return $this->render('admin/users/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $role = $form->get('role')->getData();
            $user->setRoles([$role]);

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a bien été créé.');

            return $this->redirectToRoute('app_admin_users_index');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_users_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(AdminUserEditType::class, $user, [
            'default_role' => $user->getRoles()[0] ?? 'ROLE_CLIENT',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $currentPassword = $form->get('currentPassword')->getData();

            if ($newPassword !== null && $newPassword !== '') {
                $newPasswordFirst = $form->get('newPassword')->get('first')->getData();
                $newPasswordSecond = $form->get('newPassword')->get('second')->getData();

                if ($newPasswordFirst !== $newPasswordSecond) {
                    $this->addFlash('error', 'Les deux mots de passe doivent être identiques.');
                    return $this->render('admin/users/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                    ]);
                }

                if (empty($currentPassword) || !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est obligatoire et incorrect pour modifier le mot de passe.');
                    return $this->render('admin/users/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                    ]);
                }

                $user->setPassword($passwordHasher->hashPassword($user, $newPasswordFirst));
            }

            $role = $form->get('role')->getData();
            $user->setRoles([$role]);

            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a bien été modifié.');

            return $this->redirectToRoute('app_admin_users_index');
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
