<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category:read', Test::GROUP_READ, Story::GROUP_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category:read', Test::GROUP_READ, Story::GROUP_READ])]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Test::class, mappedBy: 'categories')]
    #[Groups(['category:read'])]
    private Collection $tests;

    #[ORM\ManyToMany(targetEntity: Story::class, mappedBy: 'categories')]
    #[Groups(['category:read'])]
    private Collection $stories;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
        $this->stories = new ArrayCollection();
    }

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

    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function addTest(Test $test): self
    {
        if (!$this->tests->contains($test)) {
            $this->tests->add($test);
            $test->addCategory($this);
        }
        return $this;
    }

    public function removeTest(Test $test): self
    {
        if ($this->tests->removeElement($test)) {
            $test->removeCategory($this);
        }
        return $this;
    }

    public function getStories(): Collection
    {
        return $this->stories;
    }

    public function addStory(Story $story): self
    {
        if (!$this->stories->contains($story)) {
            $this->stories->add($story);
            $story->addCategory($this);
        }
        return $this;
    }

    public function removeStory(Story $story): self
    {
        if ($this->stories->removeElement($story)) {
            $story->removeCategory($this);
        }
        return $this;
    }
}