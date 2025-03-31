<?php

namespace App\Entity;

use App\Repository\TestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class Test
{
    public const GROUP_READ = 'test:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_READ])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'tests')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_READ])]
    private ?User $owner = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([self::GROUP_READ])]
    private ?string $notes = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'tests')]
    #[Groups([self::GROUP_READ])]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Template::class, mappedBy: 'tests')]
    private Collection $templates;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->templates = new ArrayCollection();
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }
        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);
        return $this;
    }

    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function addTemplate(Template $template): self
    {
        if (!$this->templates->contains($template)) {
            $this->templates->add($template);
            $template->addTest($this);
        }
        return $this;
    }

    public function removeTemplate(Template $template): self
    {
        if ($this->templates->removeElement($template)) {
            $template->removeTest($this);
        }
        return $this;
    }

}