<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile/{userName}', name: 'app_profile', methods: ['GET'])]
    public function index(string $userName, UserRepository $userRepository, OrderRepository $orderRepository): Response
    {
        $user = $userRepository->findOneBy(['username' => $userName]);

        if (!$user)
        {
            throw $this->createNotFoundException('User not found');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser()->getUsername() !== $userName)
        {
            throw $this->createNotFoundException('User not found');
        }

        $orders = $orderRepository->findBy(['User' => $user]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }
}
