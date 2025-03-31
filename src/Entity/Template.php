<?php

namespace App\Entity;

use App\Repository\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Template
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'templates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToMany(targetEntity: Test::class, inversedBy: 'templates')]
    private Collection $tests;

    #[ORM\ManyToMany(targetEntity: Story::class, mappedBy: 'templates')]
    private Collection $stories;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
        $this->stories = new ArrayCollection();
    }

    // Getters and setters...
}