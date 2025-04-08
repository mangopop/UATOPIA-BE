<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class TestSectionRequest
{
    public ?int $id = null;

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
    public ?int $orderIndex = null;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->id = $data['id'] ?? null;
        $dto->name = $data['name'] ?? '';
        $dto->description = $data['description'] ?? null;
        $dto->orderIndex = $data['orderIndex'] ?? null;
        return $dto;
    }
}