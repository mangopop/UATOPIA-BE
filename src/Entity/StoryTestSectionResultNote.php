<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class StoryTestSectionResultNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([Story::GROUP_READ])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StoryTestSectionResult $sectionResult = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([Story::GROUP_READ])]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'text')]
    #[Groups([Story::GROUP_READ])]
    private string $note;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups([Story::GROUP_READ])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSectionResult(): ?StoryTestSectionResult
    {
        return $this->sectionResult;
    }

    public function setSectionResult(?StoryTestSectionResult $sectionResult): self
    {
        $this->sectionResult = $sectionResult;
        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $user): self
    {
        $this->createdBy = $user;
        return $this;
    }
}