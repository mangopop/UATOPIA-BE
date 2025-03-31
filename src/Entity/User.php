<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Test::class)]
    private Collection $tests;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Template::class)]
    private Collection $templates;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Story::class)]
    private Collection $stories;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->stories = new ArrayCollection();
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    // Collection getters
    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function getStories(): Collection
    {
        return $this->stories;
    }

    // Collection adders and removers
    public function addTest(Test $test): self
    {
        if (!$this->tests->contains($test)) {
            $this->tests->add($test);
            $test->setOwner($this);
        }
        return $this;
    }

    public function removeTest(Test $test): self
    {
        if ($this->tests->removeElement($test)) {
            if ($test->getOwner() === $this) {
                $test->setOwner(null);
            }
        }
        return $this;
    }

    public function addTemplate(Template $template): self
    {
        if (!$this->templates->contains($template)) {
            $this->templates->add($template);
            $template->setOwner($this);
        }
        return $this;
    }

    public function removeTemplate(Template $template): self
    {
        if ($this->templates->removeElement($template)) {
            if ($template->getOwner() === $this) {
                $template->setOwner(null);
            }
        }
        return $this;
    }

    public function addStory(Story $story): self
    {
        if (!$this->stories->contains($story)) {
            $this->stories->add($story);
            $story->setOwner($this);
        }
        return $this;
    }

    public function removeStory(Story $story): self
    {
        if ($this->stories->removeElement($story)) {
            if ($story->getOwner() === $this) {
                $story->setOwner(null);
            }
        }
        return $this;
    }

    // Getters and setters...
}