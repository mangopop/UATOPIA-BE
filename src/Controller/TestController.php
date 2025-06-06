<?php

namespace App\Controller;

use App\Dto\TestRequest;
use App\Dto\TestSectionRequest;
use App\Entity\Test;
use App\Entity\TestSection;
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
    public function index(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        $result = $this->testRepository->findAllSortedPaginated($page, $limit);

        return $this->json([
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($result['total'] / $limit)
        ], 200, [], ['groups' => [Test::GROUP_READ]]);
    }

    private function handleSections(Test $test, array $sectionData): void
    {
        $entityManager = $this->entityManager;
        $existingSections = $test->getSections()->toArray();
        $updatedSections = [];
        $sectionIds = array_filter(array_map(fn($item) => $item['id'] ?? null, $sectionData));

        // First, handle updates and additions
        foreach ($sectionData as $index => $sectionItem) {
            $sectionRequest = TestSectionRequest::fromRequest($sectionItem);

            // Try to find existing section to update
            $section = null;
            if (isset($sectionItem['id'])) {
                foreach ($existingSections as $existingSection) {
                    if ($existingSection->getId() === (int)$sectionItem['id']) {
                        $section = $existingSection;
                        break;
                    }
                }
            }

            // Create new section if not found
            if (!$section) {
                $section = new TestSection();
                $test->addSection($section);
            }

            // Update section data
            $section->setName($sectionRequest->name)
                   ->setDescription($sectionRequest->description)
                   ->setOrderIndex($sectionRequest->orderIndex ?? $index);

            $updatedSections[] = $section;
        }

        // Handle removals - check for references before removing
        foreach ($existingSections as $existingSection) {
            if (!in_array($existingSection->getId(), $sectionIds)) {
                // Check if section is referenced by any StoryTestSectionResult
                $qb = $entityManager->createQueryBuilder();
                $hasReferences = $qb->select('COUNT(stsr.id)')
                    ->from('App\Entity\StoryTestSectionResult', 'stsr')
                    ->where('stsr.section = :section')
                    ->setParameter('section', $existingSection)
                    ->getQuery()
                    ->getSingleScalarResult();

                if ($hasReferences > 0) {
                    // If section is referenced, keep it but mark it as inactive or archived
                    // You might need to add this field to your TestSection entity
                    continue;
                }

                $test->removeSection($existingSection);
                $entityManager->remove($existingSection);
            }
        }
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
            ->setDescription($testRequest->description)
            ->setOwner($owner)
            ->setNotes($testRequest->notes);

        $this->handleSections($test, $testRequest->sections);

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

    #[Route('/search', name: 'search', methods: ['GET'], priority: 1)]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        $categoryIds = $request->query->all('categories');
        $ownerId = $request->query->get('ownerId');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 30;

        $result = $this->testRepository->searchTests(
            query: $query,
            categoryIds: $categoryIds,
            ownerId: $ownerId,
            page: $page,
            limit: $limit
        );

        return $this->json([
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($result['total'] / $limit)
        ], 200, [], ['groups' => [Test::GROUP_READ]]);
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
            ->setDescription($testRequest->description)
            ->setOwner($owner)
            ->setNotes($testRequest->notes);

        $this->handleSections($test, $testRequest->sections);

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

    #[Route('/by-template/{templateId}', name: 'by_template', methods: ['GET'])]
    public function getByTemplate(int $templateId): JsonResponse
    {
        $tests = $this->testRepository->findByTemplateId($templateId);
        return $this->json($tests, 200, [], ['groups' => [Test::GROUP_READ]]);
    }
}
