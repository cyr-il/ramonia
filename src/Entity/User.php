<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]

class User implements UserInterface, PasswordAuthenticatedUserInterface

{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $subscriptionType = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, SubscriptionUser>
     */
    #[ORM\OneToMany(targetEntity: SubscriptionUser::class, mappedBy: 'User_contract')]
    private Collection $subscriptionUsers;

    #[ORM\Column]
    private bool $isVerified = false;

    public function __construct()
    {
        $this->subscriptionUsers = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getSubscriptionType(): ?string
    {
        return $this->subscriptionType;
    }

    public function setSubscriptionType(string $subscriptionType): static
    {
        $this->subscriptionType = $subscriptionType;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, SubscriptionUser>
     */
    public function getSubscriptionUsers(): Collection
    {
        return $this->subscriptionUsers;
    }

    public function addSubscriptionUser(SubscriptionUser $subscriptionUser): static
    {
        if (!$this->subscriptionUsers->contains($subscriptionUser)) {
            $this->subscriptionUsers->add($subscriptionUser);
            $subscriptionUser->setUserContract($this);
        }

        return $this;
    }

    public function removeSubscriptionUser(SubscriptionUser $subscriptionUser): static
    {
        if ($this->subscriptionUsers->removeElement($subscriptionUser)) {
            // set the owning side to null (unless already changed)
            if ($subscriptionUser->getUserContract() === $this) {
                $subscriptionUser->setUserContract(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        // Return the roles of the user
        // For example:
        return ['ROLE_USER'];
    }


    public function getUserIdentifier(): string
    {
        // Return the identifier of the user, which is usually the email or username
        // For example:
        return $this->email;
    }

    public function eraseCredentials():void
    {
        // This method is not used in Symfony 5.3 and later, so you can leave it empty
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
