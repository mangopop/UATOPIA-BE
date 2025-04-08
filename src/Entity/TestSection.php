<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class TestSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([Test::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups([Test::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([Test::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Test $test = null;

    #[ORM\Column(type: 'integer')]
    #[Groups([Test::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private int $orderIndex = 0;

    #[ORM\Column(type: 'boolean')]
    #[Groups([Test::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getTest(): ?Test
    {
        return $this->test;
    }

    public function setTest(?Test $test): self
    {
        $this->test = $test;
        return $this;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }
}