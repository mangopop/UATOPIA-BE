<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class TestSectionRequest
{
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    public string $name;

    public ?string $description = null;

    #[Assert\GreaterThanOrEqual(0)]
    public int $orderIndex = 0;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? null;
        $dto->description = $data['description'] ?? null;
        $dto->orderIndex = $data['orderIndex'] ?? 0;
        return $dto;
    }
}