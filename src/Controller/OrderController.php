<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order', methods: ['GET'])]
    public function placeOrder(SessionInterface $session, ProductRepository $productRepository, EntityManagerInterface $entityManager, Security $security): Response
    {
        $cart = $session->get('cart', []);

        if (empty($cart))
        {
            return $this->redirectToRoute('app_cart');
        }

        $order = new Order();
        $totalPrice = 0;

        foreach ($cart as $id => $quantity)
        {
            $product = $productRepository->find($id);

            if (!$product)
            {
                continue;
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setPrice($product->getPrice());

            $totalPrice += $product->getPrice() * $quantity;

            $orderItem->setOrder($order);
            $product->setStock($product->getStock() - $quantity);
            $entityManager->persist($product);
            $entityManager->persist($orderItem);
        }


        $order->setTotalPrice($totalPrice);
        $user = $security->getUser();

        if ($user instanceof User)
        {
            $order->setUser($user);
        }
        else
        {
            return $this->redirectToRoute('app_login');
        }
        $entityManager->persist($order);
        $entityManager->flush();

        $session->set('cart', []);

        return $this->render('order/index.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/admin/orders', name: 'app_admin_orders')]
    public function showInProgressOrders(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy(['status' => 'pending'], ['date' => 'ASC']);

        return $this->render('order/in_progress.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/admin/orders/completed', name: 'app_admin_orders_completed')]
    public function showCompletedOrders(OrderRepository $orderRepository): Response
    {
        $completedOrders = $orderRepository->findBy(['status' => 'completed'], ['date' => 'DESC']);

        $totalAmount = array_reduce($completedOrders, function ($sum, $order)
        {
            return $sum + $order->getTotalPrice();
        }, 0);

        return $this->render('order/completed.html.twig', [
            'orders' => $completedOrders,
            'totalAmount' => $totalAmount
        ]);
    }

    #[Route('/admin/orders/{id}/complete', name: 'app_admin_order_complete')]
    public function completeOrder(Order $order, EntityManagerInterface $entityManager): Response
    {
        $order->setStatus('completed');
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_orders');
    }

    #[Route('/admin/orders/{id}/delete', name: 'app_admin_order_delete', methods: ['POST'])]
    public function deleteOrder(Order $order, EntityManagerInterface $entityManager): Response
    {
        foreach ($order->getOrderItems() as $item)
        {
            $entityManager->remove($item);
        }

        $entityManager->remove($order);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_orders');
    }
}
