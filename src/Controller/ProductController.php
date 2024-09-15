<?php

namespace App\Controller;

use App\Entity\AddProductHistory;
use App\Entity\Product;
use App\Form\AddProductHistoryType;
use App\Form\ProductTypeNewForm;
use App\Form\ProductTypeEditForm;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_admin_product', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductTypeNewForm::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $imageErrors = $form->get('image')->getErrors();
            if ($imageErrors->count() > 0)
            {
                return $this->redirectToRoute('app_admin_product_new');
            }

            $image = $form->get('image')->getData();

            if ($image)
            {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalName)->lower();
                if ($safeFileName->isEmpty())
                {
                    $safeFileName = "product-id-{$product->getId()}";
                }
                $newFileName = $safeFileName . '-' . date('d-m-Y-H-i-s') . '.' . $image->guessExtension();

                try
                {
                    $image->move($this->getParameter('products_image_dir'), $newFileName);
                }
                catch (FileException $exception)
                {
                    $this->addFlash('danger', 'Ошибка загрузки файла: ' . $exception->getMessage());
                    return $this->redirectToRoute('app_admin_product');
                }

                $product->setImage('/uploads/images/products' . '/' . $newFileName);
            }

            else
            {
                $product->setImage($this->getParameter('no_image_path'));
            }

            $stockHistory = new AddProductHistory();
            $stockHistory->setProduct($product)->setQuantity($product->getStock())->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($product);
            $entityManager->persist($stockHistory);

            $entityManager->flush();

            $this->addFlash('success', "Товар {$product->getName()} успешно добавлен");

            return $this->redirectToRoute('app_admin_product', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductTypeEditForm::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager->flush();

            $this->addFlash('success', "Товар {$product->getName()} успешно изменен");

            return $this->redirectToRoute('app_admin_product', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/remove/{id}', name: 'app_admin_product_delete', methods: ['POST'])]
    public function remove(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token')))
        {
            foreach ( $product->getAddProductHistories() as $item)
            {
                $entityManager->remove($item);
            }

            $this->addFlash('danger', "Товар {$product->getName()} успешно удален");

            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_product', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/add/stock', name: 'app_admin_product_stock_add', methods: ['POST', 'GET'])]
    public function addStock(Product $product, EntityManagerInterface $entityManager, Request $request): Response
    {
        $addStock = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryType::class, $addStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            if ($addStock->getQuantity() <= 0)
            {
                $this->addFlash('danger', "Количество должно быть больше 0");
                return $this->redirectToRoute('app_admin_product_stock_add', ['id' => $product->getId()]);
            }

            $addStock->setProduct($product)->setCreatedAt(new \DateTimeImmutable());
            $product->setStock($product->getStock() + $addStock->getQuantity());
            $entityManager->persist($addStock);
            $entityManager->flush();

            $this->addFlash('success', "На склад добавлено {$addStock->getQuantity()} {$product->getName()}");

            return $this->redirectToRoute('app_admin_product', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/addStock.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }
}
