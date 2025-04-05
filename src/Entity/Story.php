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

    #[ORM\OneToMany(
        mappedBy: 'story',
        targetEntity: StoryTestSectionResult::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[Groups([self::GROUP_READ])]
    private Collection $sectionResults;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups([self::GROUP_READ, Template::GROUP_READ, Story::GROUP_READ])]
    private ?\DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->templates = new ArrayCollection();
        $this->testResults = new ArrayCollection();
        $this->sectionResults = new ArrayCollection();
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

    /**
     * @return Collection<int, StoryTestSectionResult>
     */
    public function getSectionResults(): Collection
    {
        return $this->sectionResults;
    }

    public function getSectionResult(TestSection $section): ?StoryTestSectionResult
    {
        foreach ($this->sectionResults as $result) {
            if ($result->getSection() === $section) {
                return $result;
            }
        }
        return null;
    }

    public function setSectionResult(TestSection $section, string $status): StoryTestSectionResult
    {
        $sectionResult = $this->getSectionResult($section);
        if (!$sectionResult) {
            $sectionResult = new StoryTestSectionResult();
            $sectionResult->setStory($this)
                         ->setSection($section);
            $this->sectionResults->add($sectionResult);
        }

        $sectionResult->setStatus($status);

        // Update the overall test status for the test that owns this section
        $this->updateTestStatus($section->getTest());

        return $sectionResult;
    }

    private function updateTestStatus(Test $test): void
    {
        $testResult = $this->getTestResult($test);
        if (!$testResult) {
            $testResult = new StoryTestResult();
            $testResult->setStory($this)
                      ->setTest($test);
            $this->testResults->add($testResult);
        }

        $allPassed = true;
        $anyTested = false;
        $anyFailed = false;

        foreach ($this->sectionResults as $sectionResult) {
            if ($sectionResult->getSection()->getTest() !== $test) {
                continue;
            }

            if ($sectionResult->getStatus() !== StoryTestSectionResult::STATUS_NOT_TESTED) {
                $anyTested = true;
            }
            if ($sectionResult->getStatus() === StoryTestSectionResult::STATUS_FAILED) {
                $anyFailed = true;
            }
            if ($sectionResult->getStatus() !== StoryTestSectionResult::STATUS_PASSED) {
                $allPassed = false;
            }
        }

        if ($anyFailed) {
            $testResult->setStatus(StoryTestResult::STATUS_FAILED);
        } else if (!$anyTested) {
            $testResult->setStatus(StoryTestResult::STATUS_NOT_TESTED);
        } else if ($allPassed) {
            $testResult->setStatus(StoryTestResult::STATUS_PASSED);
        } else {
            $testResult->setStatus(StoryTestResult::STATUS_NOT_TESTED);
        }
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

    public function addSectionResultNote(TestSection $section, string $noteText, User $user): self
    {
        $sectionResult = $this->getSectionResult($section);
        if (!$sectionResult) {
            $sectionResult = $this->setSectionResult($section, StoryTestSectionResult::STATUS_NOT_TESTED);
        }

        $sectionResult->addNote($noteText, $user);
        return $this;
    }
}