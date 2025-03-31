<?php

namespace App\Controller;

use App\Dto\TestRequest;
use App\Entity\Test;
use App\Repository\TestRepository;
use App\Repository\CategoryRepository;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Annotation\Groups;

#[Route('/api/tests', name: 'api_tests_')]
class TestController extends AbstractController
{
    public function __construct(
        private readonly TestRepository $testRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ValidationService $validationService,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $tests = $this->testRepository->findAllSorted();
        return $this->json($tests, 200, [], ['groups' => [Test::GROUP_READ]]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $testRequest = TestRequest::fromRequest($data);

        if ($response = $this->validationService->validate($testRequest)) {
            return $response;
        }

        $owner = $this->getUser();

        if (!$owner) {
            return $this->json(['errors' => ['User not authenticated']], 401);
        }

        $test = new Test();
        $test->setName($testRequest->name)
            ->setOwner($owner)
            ->setNotes($testRequest->notes);

        // Add categories if provided
        foreach ($testRequest->categoryIds as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $test->addCategory($category);
            }
        }

        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $this->json($test, 201, [], ['groups' => [Test::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Test $test): JsonResponse
    {
        return $this->json($test, 200, [], ['groups' => [Test::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Test $test): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $testRequest = TestRequest::fromRequest($data);

        if ($response = $this->validationService->validate($testRequest)) {
            return $response;
        }

        $owner = $this->getUser();
        if (!$owner) {
            return $this->json(['errors' => ['User not authenticated']], 401);
        }

        $test->setName($testRequest->name)
            ->setOwner($owner)
            ->setNotes($testRequest->notes);

        // Clear and re-add categories
        foreach ($test->getCategories() as $category) {
            $test->removeCategory($category);
        }

        foreach ($testRequest->categoryIds as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $test->addCategory($category);
            }
        }

        $this->entityManager->flush();

        return $this->json($test, 200, [], ['groups' => [Test::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Test $test): JsonResponse
    {
        $this->entityManager->remove($test);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
