<?php

namespace App\Entity;

use App\Repository\StoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Story
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'stories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToMany(targetEntity: Template::class, inversedBy: 'stories')]
    private Collection $templates;

    #[ORM\ManyToMany(targetEntity: Folder::class, mappedBy: 'stories')]
    private Collection $folders;

    public function __construct()
    {
        $this->templates = new ArrayCollection();
    }

    // Getters and setters...
}