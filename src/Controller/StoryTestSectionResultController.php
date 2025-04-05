<?php

namespace App\Controller;

use App\Entity\Story;
use App\Entity\TestSection;
use App\Entity\StoryTestSectionResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api')]
class StoryTestSectionResultController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    #[Route('/stories/{id}/section-results', name: 'get_story_section_results', methods: ['GET'])]
    public function getSectionResults(Story $story): JsonResponse
    {
        return $this->json([
            'section_results' => $story->getSectionResults()
        ], Response::HTTP_OK, [], ['groups' => [Story::GROUP_READ]]);
    }

    #[Route('/stories/{storyId}/section-results/{sectionId}', name: 'update_section_result', methods: ['PUT'])]
    public function updateSectionResult(
        int $storyId,
        int $sectionId,
        Request $request
    ): JsonResponse {
        $story = $this->entityManager->getRepository(Story::class)->find($storyId);
        $section = $this->entityManager->getRepository(TestSection::class)->find($sectionId);

        if (!$story || !$section) {
            return $this->json(['error' => 'Story or section not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        if (!in_array($status, [
            StoryTestSectionResult::STATUS_PASSED,
            StoryTestSectionResult::STATUS_FAILED,
            StoryTestSectionResult::STATUS_NOT_TESTED
        ])) {
            return $this->json(['error' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
        }

        $sectionResult = $story->setSectionResult($section, $status);
        $this->entityManager->flush();

        return $this->json($sectionResult, Response::HTTP_OK, [], ['groups' => [Story::GROUP_READ]]);
    }

    #[Route('/stories/{storyId}/section-results/{sectionId}/notes', name: 'add_section_result_note', methods: ['POST'])]
    public function addNote(
        int $storyId,
        int $sectionId,
        Request $request
    ): JsonResponse {
        $story = $this->entityManager->getRepository(Story::class)->find($storyId);
        $section = $this->entityManager->getRepository(TestSection::class)->find($sectionId);

        if (!$story || !$section) {
            return $this->json(['error' => 'Story or section not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $noteText = $data['note'] ?? null;

        if (!$noteText) {
            return $this->json(['error' => 'Note text is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->security->getUser();
        $story->addSectionResultNote($section, $noteText, $user);
        $this->entityManager->flush();

        $sectionResult = $story->getSectionResult($section);
        $lastNote = $sectionResult->getNotes()->first();

        return $this->json($lastNote, Response::HTTP_CREATED, [], ['groups' => [Story::GROUP_READ]]);
    }

    #[Route('/stories/{storyId}/section-results/{sectionId}/notes', name: 'get_section_result_notes', methods: ['GET'])]
    public function getNotes(
        int $storyId,
        int $sectionId
    ): JsonResponse {
        $story = $this->entityManager->getRepository(Story::class)->find($storyId);
        $section = $this->entityManager->getRepository(TestSection::class)->find($sectionId);

        if (!$story || !$section) {
            return $this->json(['error' => 'Story or section not found'], Response::HTTP_NOT_FOUND);
        }

        $sectionResult = $story->getSectionResult($section);
        if (!$sectionResult) {
            return $this->json(['notes' => []], Response::HTTP_OK, [], ['groups' => [Story::GROUP_READ]]);
        }

        return $this->json([
            'notes' => $sectionResult->getNotes()
        ], Response::HTTP_OK, [], ['groups' => [Story::GROUP_READ]]);
    }
}