<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function users(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}/promote', name: 'app_admin_promote_user')]
    public function promoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        if ($user->isAdmin()) {
            $this->addFlash('error', 'Impossible de promouvoir un administrateur.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->isInstructor()) {
            $user->setRole(User::ROLE_ADMIN);
            $this->addFlash('success', 'L\'utilisateur a été promu administrateur.');
        } else {
            $user->setRole(User::ROLE_INSTRUCTOR);
            $this->addFlash('success', 'L\'utilisateur a été promu instructeur.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/demote', name: 'app_admin_demote_user')]
    public function demoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        if ($user->isAdmin()) {
            $this->addFlash('error', 'Impossible de rétrograder un administrateur.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->isInstructor()) {
            $user->setRole(User::ROLE_STUDENT);
            $this->addFlash('success', 'L\'instructeur a été rétrogradé en étudiant.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_users');
    }
} 