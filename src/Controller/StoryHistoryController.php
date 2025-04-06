<?php

namespace App\Controller;

use App\Entity\Story;
use App\Entity\StoryHistory;
use App\Entity\StoryTestResult;
use App\Repository\StoryRepository;
use App\Repository\StoryHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/stories')]
class StoryHistoryController extends AbstractController
{
    public function __construct(
        private readonly StoryRepository $storyRepository,
        private readonly StoryHistoryRepository $storyHistoryRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/{id}/failed', name: 'story_failed', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function markFailed(Story $story): JsonResponse
    {
        if ($story->isCompleted()) {
            return new JsonResponse(['error' => 'Story is already completed and cannot be modified'], Response::HTTP_BAD_REQUEST);
        }

        // Create history entries for each test result
        foreach ($story->getTestResults() as $testResult) {
            $history = new StoryHistory();
            $history->setStory($story)
                ->setStatus(StoryHistory::STATUS_FAILED)
                ->setCreatedBy($this->getUser())
                ->setTest($testResult->getTest())
                ->setTestStatus($testResult->getStatus());

            // Capture test notes
            $testNotes = [];
            foreach ($testResult->getNotes() as $note) {
                $testNotes[] = sprintf(
                    '[%s] %s: %s',
                    $note->getCreatedAt()->format('Y-m-d H:i:s'),
                    $note->getCreatedBy()->getFirstName(),
                    $note->getNote()
                );
            }
            if (!empty($testNotes)) {
                $history->setNotes(implode("\n", $testNotes));
            }

            // Capture section results and notes
            $sectionResults = [];
            $sectionNotes = [];
            foreach ($story->getSectionResults() as $sectionResult) {
                if ($sectionResult->getSection()->getTest() === $testResult->getTest()) {
                    $sectionResults[] = [
                        'section_id' => $sectionResult->getSection()->getId(),
                        'section_name' => $sectionResult->getSection()->getName(),
                        'status' => $sectionResult->getStatus()
                    ];

                    // Capture section notes
                    foreach ($sectionResult->getNotes() as $note) {
                        $sectionNotes[] = sprintf(
                            '[Section: %s] [%s] %s: %s',
                            $sectionResult->getSection()->getName(),
                            $note->getCreatedAt()->format('Y-m-d H:i:s'),
                            $note->getCreatedBy()->getFirstName(),
                            $note->getNote()
                        );
                    }
                }
            }

            $history->setSectionResults($sectionResults);
            if (!empty($sectionNotes)) {
                $history->setSectionNotes(implode("\n", $sectionNotes));
            }

            $this->entityManager->persist($history);
        }

        $story->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success', 'message' => 'Story marked as failed and locked']);
    }

    #[Route('/{id}/complete', name: 'story_complete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function markComplete(Story $story): JsonResponse
    {
        if ($story->isCompleted()) {
            return new JsonResponse(['error' => 'Story is already completed and cannot be modified'], Response::HTTP_BAD_REQUEST);
        }

        // Check if all tests are passed
        foreach ($story->getTestResults() as $testResult) {
            if ($testResult->getStatus() !== StoryTestResult::STATUS_PASSED) {
                return new JsonResponse(
                    ['error' => 'Cannot complete story - not all tests have passed'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Create history entry for each test result
            $history = new StoryHistory();
            $history->setStory($story)
                ->setStatus(StoryHistory::STATUS_COMPLETED)
                ->setCreatedBy($this->getUser())
                ->setTest($testResult->getTest())
                ->setTestStatus($testResult->getStatus());

            // Capture any notes from the test result
            $notes = [];
            foreach ($testResult->getNotes() as $note) {
                $notes[] = sprintf(
                    '[%s] %s: %s',
                    $note->getCreatedAt()->format('Y-m-d H:i:s'),
                    $note->getCreatedBy()->getFirstName(),
                    $note->getNote()
                );
            }
            if (!empty($notes)) {
                $history->setNotes(implode("\n", $notes));
            }

            $this->entityManager->persist($history);
        }

        $story->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success', 'message' => 'Story marked as complete and locked']);
    }

    #[Route('/{id}/history', name: 'story_history', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getHistory(Story $story): JsonResponse
    {

        $historyGroups = $this->storyHistoryRepository->findStoryHistory($story);

        return new JsonResponse([
            'story_id' => $story->getId(),
            'story_name' => $story->getName(),
            'history' => $historyGroups
        ]);
    }
}