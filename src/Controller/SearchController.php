<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, PaginatorInterface $paginator): Response
    {
        if ($request->isMethod('GET')){
            $query = $request->query->get('q');
            $data = $productRepository->searchByName($query);
            $products = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                6
            );
            return $this->render('search/index.html.twig', [
                'products' => $products,
                'query' => $query
            ]);
        }

        return $this->redirectToRoute("app_home_index");
    }
}
