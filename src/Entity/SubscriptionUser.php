<?php

namespace App\Entity;

use App\Repository\SubscriptionUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionUserRepository::class)]
class SubscriptionUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptionUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $User_contract = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $StartDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $EndDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserContract(): ?User
    {
        return $this->User_contract;
    }

    public function setUserContract(?User $User_contract): static
    {
        $this->User_contract = $User_contract;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->StartDate;
    }

    public function setStartDate(\DateTimeInterface $StartDate): static
    {
        $this->StartDate = $StartDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->EndDate;
    }

    public function setEndDate(\DateTimeInterface $EndDate): static
    {
        $this->EndDate = $EndDate;

        return $this;
    }
}
