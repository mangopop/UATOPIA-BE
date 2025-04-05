<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class StoryRequest
{
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    public string $name;

    #[Assert\Type('array')]
    public array $templateIds = [];

    #[Assert\Type('array')]
    public array $testIds = [];

    #[Assert\Type('array')]
    public array $categoryIds = [];

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? null;
        $dto->templateIds = $data['templateIds'] ?? [];
        $dto->testIds = $data['testIds'] ?? [];
        $dto->categoryIds = $data['categoryIds'] ?? [];
        return $dto;
    }
}