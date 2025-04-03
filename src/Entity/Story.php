<?php

namespace App\Entity;

use App\Repository\StoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class Story
{
    public const GROUP_READ = 'story:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_READ])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'stories')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_READ])]
    private ?User $owner = null;

    #[ORM\ManyToMany(targetEntity: Template::class, inversedBy: 'stories')]
    #[Groups([self::GROUP_READ])]
    private Collection $templates;

    #[ORM\ManyToMany(targetEntity: Folder::class, mappedBy: 'stories')]
    private Collection $folders;

    #[ORM\OneToMany(
        mappedBy: 'story',
        targetEntity: StoryTestResult::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[Groups([self::GROUP_READ])]
    private Collection $testResults;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?\DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->templates = new ArrayCollection();
        $this->testResults = new ArrayCollection();
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
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
            $template->addStory($this);
        }
        return $this;
    }

    public function removeTemplate(Template $template): self
    {
        if ($this->templates->removeElement($template)) {
            $template->removeStory($this);
        }
        return $this;
    }

    public function getFolders(): Collection
    {
        return $this->folders;
    }

    public function addFolder(Folder $folder): self
    {
        if (!$this->folders->contains($folder)) {
            $this->folders->add($folder);
            $folder->addStory($this);
        }
        return $this;
    }

    public function removeFolder(Folder $folder): self
    {
        if ($this->folders->removeElement($folder)) {
            $folder->removeStory($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, StoryTestResult>
     */
    public function getTestResults(): Collection
    {
        return $this->testResults;
    }

    public function getTestResult(Test $test): ?StoryTestResult
    {
        foreach ($this->testResults as $result) {
            if ($result->getTest() === $test) {
                return $result;
            }
        }
        return null;
    }

    public function setTestResult(Test $test, bool $passed, ?string $notes = null): self
    {
        $result = $this->getTestResult($test);
        if (!$result) {
            $result = new StoryTestResult();
            $result->setStory($this)
                  ->setTest($test);
            $this->testResults->add($result);
        }

        $result->setPassed($passed)
              ->setNotes($notes);

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