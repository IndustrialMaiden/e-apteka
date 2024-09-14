<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/admin/users', name: 'app_user')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/admin/users/{id}/to-admin', name: 'app_user_to-admin')]
    public function toAdmin(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles(["ROLE_ADMIN","ROLE_USER"]);
        $entityManager->flush();
        $this->addFlash('success', "Роль пользователя {$user->getUsername()} изменена на \"Админ\"");
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/users/{id}/to-user', name: 'app_user_to-user')]
    public function toUser(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles(["ROLE_USER"]);
        $entityManager->flush();
        $this->addFlash('success', "Роль пользователя {$user->getUsername()} изменена на \"Пользователь\"");
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/users/{id}/delete', name: 'app_user_delete')]
    public function delete(EntityManagerInterface $entityManager, User $user): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', "Пользователь {$user->getUsername()} удален");

        return $this->redirectToRoute('app_user');
    }
}
