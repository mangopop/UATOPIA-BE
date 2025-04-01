<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email address')]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 6,
        minMessage: 'Password must be at least {{ limit }} characters'
    )]
    public string $password;

    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'First name must be at least {{ limit }} characters',
        maxMessage: 'First name cannot be longer than {{ limit }} characters'
    )]
    public string $firstName;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Last name must be at least {{ limit }} characters',
        maxMessage: 'Last name cannot be longer than {{ limit }} characters'
    )]
    public string $lastName;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? null;
        $dto->password = $data['password'] ?? null;
        $dto->firstName = $data['firstName'] ?? null;
        $dto->lastName = $data['lastName'] ?? null;
        return $dto;
    }
}