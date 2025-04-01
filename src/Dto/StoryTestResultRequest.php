<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class StoryTestResultRequest
{
    #[Assert\NotNull(message: 'Test ID is required')]
    public int $testId;

    #[Assert\NotNull(message: 'Passed status is required')]
    public bool $passed;

    public ?string $notes = null;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->testId = $data['testId'] ?? null;
        $dto->passed = $data['passed'] ?? null;
        $dto->notes = $data['notes'] ?? null;
        return $dto;
    }
}