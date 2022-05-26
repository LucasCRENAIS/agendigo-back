<?php

namespace App\Controller;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    /**
     * @Route("/api/v1", name="api_v1", methods="GET")
     */
    public function list(CategoryRepository $categoryRepository): Response
    {
        //* j'utilise une requete custom en SQL car j'ai une erreur LazyPropertie en passant par Doctrine (requête DQL)
        $categories = $categoryRepository->findAllordered();
        return $this->json($categories, 200);
    }
}