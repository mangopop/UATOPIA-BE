<?php

namespace App\Controller;

use App\Dto\StoryRequest;
use App\Entity\Story;
use App\Repository\StoryRepository;
use App\Repository\TemplateRepository;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TestRepository;
use App\Dto\StoryTestResultRequest;
use App\Entity\StoryTestResult;

#[Route('/api/stories', name: 'api_stories_')]
class StoryController extends AbstractController
{
    public function __construct(
        private readonly StoryRepository $storyRepository,
        private readonly TemplateRepository $templateRepository,
        private readonly ValidationService $validationService,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $stories = $this->storyRepository->findAllSorted();

        // dd($stories);
        return $this->json($stories, 200, [], ['groups' => [Story::GROUP_READ]]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $storyRequest = StoryRequest::fromRequest($data);

        if ($response = $this->validationService->validate($storyRequest)) {
            return $response;
        }

        $owner = $this->getUser();
        if (!$owner) {
            return $this->json(['errors' => ['Authentication required']], 401);
        }

        $story = new Story();
        $story->setName($storyRequest->name)
            ->setOwner($owner);

        foreach ($storyRequest->templateIds as $templateId) {
            $template = $this->templateRepository->find($templateId);
            if ($template) {
                $story->addTemplate($template);
            }
        }

        $this->entityManager->persist($story);
        $this->entityManager->flush();

        return $this->json($story, 201, [], ['groups' => [Story::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Story $story): JsonResponse
    {
        return $this->json($story, 200, [], ['groups' => [Story::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Story $story): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $storyRequest = StoryRequest::fromRequest($data);

        if ($response = $this->validationService->validate($storyRequest)) {
            return $response;
        }

        $owner = $this->getUser();
        if (!$owner) {
            return $this->json(['errors' => ['Authentication required']], 401);
        }

        $story->setName($storyRequest->name)
            ->setOwner($owner);

        // Clear and re-add templates
        foreach ($story->getTemplates() as $template) {
            $story->removeTemplate($template);
        }

        foreach ($storyRequest->templateIds as $templateId) {
            $template = $this->templateRepository->find($templateId);
            if ($template) {
                $story->addTemplate($template);
            }
        }

        $this->entityManager->flush();

        return $this->json($story, 200, [], ['groups' => [Story::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Story $story): JsonResponse
    {
        $this->entityManager->remove($story);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    #[Route('/{id}/test-results', name: 'update_test_results', methods: ['PUT'])]
    public function updateTestResults(
        Request $request,
        Story $story,
        TestRepository $testRepository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $testResultRequest = StoryTestResultRequest::fromRequest($data);

        if ($response = $this->validationService->validate($testResultRequest)) {
            return $response;
        }

        $test = $testRepository->find($testResultRequest->testId);
        if (!$test) {
            return $this->json(['errors' => ['Test not found']], 404);
        }

        // Find existing test result
        $existingResult = $this->entityManager->getRepository(StoryTestResult::class)
            ->findOneBy([
                'story' => $story,
                'test' => $test
            ]);

        if ($existingResult) {
            // Update existing result
            $existingResult
                ->setStatus($testResultRequest->status)
                ->setNotes($testResultRequest->notes);
        } else {
            // Create new result
            $result = new StoryTestResult();
            $result->setTest($test)
                  ->setStory($story)
                  ->setStatus($testResultRequest->status)
                  ->setNotes($testResultRequest->notes)
                  ->setCodeNotes($testResultRequest->codeNotes);

            $this->entityManager->persist($result);
        }

        $this->entityManager->flush();

        return $this->json($story, 200, [], ['groups' => [Story::GROUP_READ]]);
    }
}