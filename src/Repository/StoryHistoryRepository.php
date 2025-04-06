<?php

namespace App\Repository;

use App\Entity\Story;
use App\Entity\StoryHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StoryHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoryHistory::class);
    }

    /**
     * Find all history entries for a story, grouped by timestamp
     * Returns the entries ordered by most recent first
     */
    public function findStoryHistory(Story $story): array
    {
        $entries = $this->createQueryBuilder('h')
            ->andWhere('h.story = :story')
            ->leftJoin('h.createdBy', 'u')
            ->leftJoin('h.test', 't')
            ->addSelect('u', 't')
            ->setParameter('story', $story)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->groupEntriesByTimestamp($entries);
    }

    /**
     * Find the most recent history entry for a story
     */
    public function findLatestHistoryEntry(Story $story): ?array
    {
        $entries = $this->createQueryBuilder('h')
            ->andWhere('h.story = :story')
            ->leftJoin('h.createdBy', 'u')
            ->leftJoin('h.test', 't')
            ->addSelect('u', 't')
            ->setParameter('story', $story)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($entries)) {
            return null;
        }

        return $this->groupEntriesByTimestamp($entries)[0];
    }

    /**
     * Find history entries for a story within a date range
     */
    public function findHistoryByDateRange(
        Story $story,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $entries = $this->createQueryBuilder('h')
            ->andWhere('h.story = :story')
            ->andWhere('h.createdAt BETWEEN :startDate AND :endDate')
            ->leftJoin('h.createdBy', 'u')
            ->leftJoin('h.test', 't')
            ->addSelect('u', 't')
            ->setParameter('story', $story)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->groupEntriesByTimestamp($entries);
    }

    /**
     * Groups history entries by timestamp and formats them into a structured array
     */
    private function groupEntriesByTimestamp(array $entries): array
    {
        $historyData = [];
        $currentTimestamp = null;
        $currentEntry = null;

        foreach ($entries as $entry) {
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

        return $historyData;
    }

    /**
     * Find all stories that were marked as failed in the last X days
     */
    public function findRecentlyFailedStories(int $days = 7): array
    {
        $date = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('h')
            ->select('DISTINCT s.id, s.name, h.createdAt')
            ->join('h.story', 's')
            ->andWhere('h.status = :status')
            ->andWhere('h.createdAt >= :date')
            ->setParameter('status', StoryHistory::STATUS_FAILED)
            ->setParameter('date', $date)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics about story failures
     */
    public function getFailureStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $results = $this->createQueryBuilder('h')
            ->select('h.status, COUNT(DISTINCT h.story) as count')
            ->andWhere('h.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('h.status')
            ->getQuery()
            ->getResult();

        $stats = [
            'failed' => 0,
            'completed' => 0,
            'total' => 0
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = (int)$result['count'];
            $stats['total'] += (int)$result['count'];
        }

        return $stats;
    }
}