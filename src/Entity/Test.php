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
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'tests')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?User $owner = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?string $notes = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'tests')]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Template::class, mappedBy: 'tests')]
    private Collection $templates;

    #[ORM\OneToMany(
        mappedBy: 'test',
        targetEntity: TestSection::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private Collection $sections;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?\DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->sections = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
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

    /**
     * @return Collection<int, TestSection>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(TestSection $section): self
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setTest($this);
        }
        return $this;
    }

    public function removeSection(TestSection $section): self
    {
        if ($this->sections->removeElement($section)) {
            if ($section->getTest() === $this) {
                $section->setTest(null);
            }
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}