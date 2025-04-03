<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class StoryTestResult
{
    public const STATUS_NOT_TESTED = 'not_tested';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([Story::GROUP_READ])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'testResults')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Story $story = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([Story::GROUP_READ])]
    private ?Test $test = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_NOT_TESTED])]
    #[Groups([Story::GROUP_READ])]
    private string $status = self::STATUS_NOT_TESTED;

    #[ORM\OneToMany(
        mappedBy: 'testResult',
        targetEntity: StoryTestResultNote::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]

    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[Groups([Story::GROUP_READ])]
    private Collection $notes;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
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

    public function getTest(): ?Test
    {
        return $this->test;
    }

    public function setTest(?Test $test): self
    {
        $this->test = $test;
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
        return $this;
    }

    /**
     * @return Collection<int, StoryTestResultNote>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(string $noteText, User $user): self
    {
        $note = new StoryTestResultNote();
        $note->setNote($noteText)
            ->setCreatedBy($user)
            ->setTestResult($this);

        $this->notes->add($note);
        return $this;
    }
}