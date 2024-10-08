<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $data = $productRepository->findBy([],['id' => "DESC"]);

        shuffle($data);

        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('home/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/product/{id}/show', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product, ProductRepository $productRepository): Response
    {
        $lastProducts = $productRepository->findBy([], ['id' => "DESC"]);

        shuffle($lastProducts);

        $lastProducts = array_slice($lastProducts, 0, 6);

        return $this->render('home/show.html.twig', [
            'product' => $product,
            'lastProducts' => $lastProducts,
        ]);
    }

    #[Route('/{type}/{id}/show', name: 'app_product_filter', methods: ['GET'])]
    public function filter(string $type, int $id, CategoryRepository $categoryRepository, SubCategoryRepository $subCategoryRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $data = [];
        $title = "";

        if ($type === 'category') {
            $category = $categoryRepository->find($id);
            if ($category) {
                $subCategories = $category->getSubCategories();
                foreach ($subCategories as $subCategory){
                    $data = array_merge($data, $subCategory->getProducts()->toArray());
                }
                $title = $category->getName();
            }
        } elseif ($type === 'subcategory') {
            $subCategory = $subCategoryRepository->find($id);
            if ($subCategory) {
                $data = $subCategory->getProducts();
                $title = $subCategory->getName();
            }
        }

        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('home/filter.html.twig', [
            'title' => $title,
            'data' => $data,
            'products' => $products,
        ]);
    }
}
