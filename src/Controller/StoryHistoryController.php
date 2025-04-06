<?php

namespace App\Controller;

use App\Entity\Story;
use App\Entity\StoryHistory;
use App\Entity\StoryTestResult;
use App\Repository\StoryRepository;
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
        $history = $this->entityManager->getRepository(StoryHistory::class)
            ->findBy(
                ['story' => $story],
                ['createdAt' => 'DESC']
            );

        $historyData = [];
        $currentTimestamp = null;
        $currentEntry = null;

        foreach ($history as $entry) {
            $timestamp = $entry->getCreatedAt()->format('Y-m-d H:i:s');

            // If this is a new timestamp, create a new entry
            if ($timestamp !== $currentTimestamp) {
                // Add the previous entry to the array if it exists
                if ($currentEntry !== null) {
                    $historyData[] = $currentEntry;
                }

                $currentTimestamp = $timestamp;
                $currentEntry = [
                    'timestamp' => $timestamp,
                    'status' => $entry->getStatus(),
                    'created_by' => [
                        'id' => $entry->getCreatedBy()->getId(),
                        'username' => $entry->getCreatedBy()->getUsername()
                    ],
                    'tests' => []
                ];
            }

            // Add test data to the current entry
            $testData = [
                'id' => $entry->getTest()->getId(),
                'name' => $entry->getTest()->getName(),
                'status' => $entry->getTestStatus(),
                'notes' => $entry->getNotes() ? explode("\n", $entry->getNotes()) : [],
                'sections' => []
            ];

            // Add section results
            foreach ($entry->getSectionResults() as $section) {
                $sectionData = [
                    'id' => $section['section_id'],
                    'name' => $section['section_name'],
                    'status' => $section['status']
                ];
                $testData['sections'][] = $sectionData;
            }

            // Add section notes if they exist
            if ($entry->getSectionNotes()) {
                $testData['section_notes'] = explode("\n", $entry->getSectionNotes());
            }

            $currentEntry['tests'][] = $testData;
        }

        // Add the last entry if it exists
        if ($currentEntry !== null) {
            $historyData[] = $currentEntry;
        }

        return new JsonResponse([
            'story_id' => $story->getId(),
            'story_name' => $story->getName(),
            'history' => $historyData
        ]);
    }
}