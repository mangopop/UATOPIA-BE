<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Dto\CategoryRequest;
use App\Service\ValidationService;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories', name: 'api_categories_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->findAll();
        return $this->json($categories, 200, [], ['groups' => ['category:read']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $categoryRequest = CategoryRequest::fromRequest($data);

        if ($response = $this->validationService->validate($categoryRequest)) {
            return $response;
        }

        $category = new Category();
        $category->setName($categoryRequest->name);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->json($category, 201);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($category);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Category $category, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['error' => 'Name is required'], 400);
        }

        $category->setName($data['name']);
        $entityManager->flush();

        return $this->json($category);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Category $category, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($category);
        $entityManager->flush();

        return $this->json(null, 204);
    }
}