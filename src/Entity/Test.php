<?php

namespace App\Entity;

use App\Repository\TestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Test
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'tests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'tests')]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Template::class, mappedBy: 'tests')]
    private Collection $templates;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->templates = new ArrayCollection();
    }

    // Getters and setters...
}