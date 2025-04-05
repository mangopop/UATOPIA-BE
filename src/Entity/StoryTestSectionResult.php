<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class StoryTestSectionResult
{
    public const STATUS_NOT_TESTED = 'not_tested';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([Story::GROUP_READ])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sectionResults')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Story $story = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([Story::GROUP_READ])]
    private ?TestSection $section = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_NOT_TESTED])]
    #[Groups([Story::GROUP_READ])]
    private string $status = self::STATUS_NOT_TESTED;

    #[ORM\OneToMany(
        mappedBy: 'sectionResult',
        targetEntity: StoryTestSectionResultNote::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[Groups([Story::GROUP_READ])]
    private Collection $notes;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups([Story::GROUP_READ])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStory(): ?Story
    {
        return $this->story;
    }

    public function setStory(?Story $story): self
    {
        $this->story = $story;
        return $this;
    }

    public function getSection(): ?TestSection
    {
        return $this->section;
    }

    public function setSection(?TestSection $section): self
    {
        $this->section = $section;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_NOT_TESTED, self::STATUS_PASSED, self::STATUS_FAILED])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, StoryTestSectionResultNote>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(string $noteText, User $user): self
    {
        $note = new StoryTestSectionResultNote();
        $note->setNote($noteText)
            ->setCreatedBy($user)
            ->setSectionResult($this);

        $this->notes->add($note);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}