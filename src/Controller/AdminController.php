<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $entityManager, Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer les rôles si plusieurs sont sélectionnés, prendre le premier comme principal pour setRole si besoin
            $roles = $form->get('roles')->getData();
            if (!empty($roles)) {
                $user->setRoles($roles);
            } else {
                // Si aucun rôle n'est sélectionné, attribuer le rôle étudiant par défaut
                $user->setRoles([User::ROLE_STUDENT]);
            }
            
            // Hacher le mot de passe s'il est fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été ajouté avec succès.');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'userForm' => $form->createView(),
        ]);
    }

    #[Route('/user/{id}/promote', name: 'app_admin_promote_user')]
    public function promoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur cible est déjà admin
        if ($user->isAdmin()) {
            $this->addFlash('error', 'Impossible de promouvoir un administrateur.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Si l'utilisateur est un instructeur, le promouvoir en admin
        if ($user->isInstructor()) {
            $user->setRoles([User::ROLE_ADMIN]);
            $this->addFlash('success', 'L\'utilisateur a été promu administrateur.');
        } else {
            // Si l'utilisateur est un étudiant, le promouvoir en instructeur
            $user->setRoles([User::ROLE_INSTRUCTOR]);
            $this->addFlash('success', 'L\'utilisateur a été promu instructeur.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/demote', name: 'app_admin_demote_user')]
    public function demoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur cible est admin
        if ($user->isAdmin()) {
            $this->addFlash('error', 'Impossible de rétrograder un administrateur.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Si l'utilisateur est un instructeur, le rétrograder en étudiant
        if ($user->isInstructor()) {
            $user->setRoles([User::ROLE_STUDENT]);
            $this->addFlash('success', 'L\'instructeur a été rétrogradé en étudiant.');
        } else {
            // Cette partie ne devrait normalement pas être atteinte si l'utilisateur est un étudiant, mais pour la complétude
            $this->addFlash('error', 'L\'utilisateur est déjà un étudiant.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_users');
    }
} 