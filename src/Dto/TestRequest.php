<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class TestRequest
{
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    public string $name;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Description cannot be longer than {{ limit }} characters'
    )]
    public ?string $description = null;

    #[Assert\Type('string')]
    public ?string $notes = null;

    #[Assert\Type('array')]
    public array $categoryIds = [];

    #[Assert\Type('array')]
    public array $sections = [];

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? null;
        $dto->description = $data['description'] ?? null;
        $dto->notes = $data['notes'] ?? null;
        $dto->categoryIds = $data['categoryIds'] ?? [];
        $dto->sections = $data['sections'] ?? [];
        return $dto;
    }
}