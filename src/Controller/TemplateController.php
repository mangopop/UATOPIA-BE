<?php

namespace App\Controller;

use App\Dto\TemplateRequest;
use App\Entity\Template;
use App\Repository\TemplateRepository;
use App\Repository\TestRepository;
use App\Repository\StoryRepository;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/templates', name: 'api_templates_')]
class TemplateController extends AbstractController
{
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly TestRepository $testRepository,
        private readonly StoryRepository $storyRepository,
        private readonly ValidationService $validationService,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $templates = $this->templateRepository->findAll();
        return $this->json($templates, 200, [], ['groups' => [Template::GROUP_READ]]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $templateRequest = TemplateRequest::fromRequest($data);

        if ($response = $this->validationService->validate($templateRequest)) {
            return $response;
        }

        $owner = $this->getUser();
        if (!$owner) {
            return $this->json(['errors' => ['Authentication required']], 401);
        }

        $template = new Template();
        $template->setName($templateRequest->name)
            ->setOwner($owner);

        foreach ($templateRequest->testIds as $testId) {
            $test = $this->testRepository->find($testId);
            if ($test) {
                $template->addTest($test);
            }
        }

        foreach ($templateRequest->storyIds as $storyId) {
            $story = $this->storyRepository->find($storyId);
            if ($story) {
                $template->addStory($story);
            }
        }

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $this->json($template, 201, [], ['groups' => [Template::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Template $template): JsonResponse
    {
        return $this->json($template, 200, [], ['groups' => [Template::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Template $template): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $templateRequest = TemplateRequest::fromRequest($data);

        if ($response = $this->validationService->validate($templateRequest)) {
            return $response;
        }

        $owner = $this->getUser();
        if (!$owner) {
            return $this->json(['errors' => ['Authentication required']], 401);
        }

        $template->setName($templateRequest->name)
            ->setOwner($owner);

        // Clear and re-add tests
        foreach ($template->getTests() as $test) {
            $template->removeTest($test);
        }

        foreach ($templateRequest->testIds as $testId) {
            $test = $this->testRepository->find($testId);
            if ($test) {
                $template->addTest($test);
            }
        }

        // Clear and re-add stories
        foreach ($template->getStories() as $story) {
            $template->removeStory($story);
        }

        foreach ($templateRequest->storyIds as $storyId) {
            $story = $this->storyRepository->find($storyId);
            if ($story) {
                $template->addStory($story);
            }
        }

        $this->entityManager->flush();

        return $this->json($template, 200, [], ['groups' => [Template::GROUP_READ]]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Template $template): JsonResponse
    {
        $this->entityManager->remove($template);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}