<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\StoryTestResult;

class StoryTestResultRequest
{
    #[Assert\NotNull(message: 'Test ID is required')]
    public int $testId;

    #[Assert\NotBlank(message: 'Status is required')]
    #[Assert\Choice(choices: [
        StoryTestResult::STATUS_NOT_TESTED,
        StoryTestResult::STATUS_PASSED,
        StoryTestResult::STATUS_FAILED
    ], message: 'Invalid status')]
    public string $status;

    public ?string $notes = null;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->testId = $data['testId'] ?? null;
        $dto->status = $data['status'] ?? StoryTestResult::STATUS_NOT_TESTED;
        $dto->notes = $data['notes'] ?? null;
        return $dto;
    }
}