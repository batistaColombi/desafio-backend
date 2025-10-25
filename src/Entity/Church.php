<?php

namespace App\Entity;

use App\Repository\ChurchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChurchRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Broadcast]
#[ValidDocument]
class Church
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "O nome da igreja é obrigatório")]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ["CPF", "CNPJ"], message: "Escolha CPF ou CNPJ")]
    private ?string $document_type = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "O número do documento é obrigatório")]
    private ?string $document_number = null;

    #[ORM\Column(length: 50)]
    private ?string $internal_code = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_street = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $address_number = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $address_complement = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $cep = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(nullable: true)]
    private ?int $members_limit = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;

    #[ORM\OneToMany(targetEntity: Member::class, mappedBy: 'church', cascade: ["persist", "remove"])]
    private Collection $members;

    #[ORM\OneToMany(mappedBy: 'from_church', targetEntity: MemberTransfer::class, cascade: ["persist", "remove"])]
    private Collection $outgoingTransfers;

    #[ORM\OneToMany(mappedBy: 'to_church', targetEntity: MemberTransfer::class, cascade: ["persist", "remove"])]
    private Collection $incomingTransfers;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->outgoingTransfers = new ArrayCollection();
        $this->incomingTransfers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDocumentType(): ?string
    {
        return $this->document_type;
    }

    public function setDocumentType(string $document_type): static
    {
        $this->document_type = $document_type;
        return $this;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->document_number;
    }

    public function setDocumentNumber(string $document_number): static
    {
        $this->document_number = $document_number;
        return $this;
    }

    public function getInternalCode(): ?string
    {
        return $this->internal_code;
    }

    public function setInternalCode(?string $internal_code): static
    {
        $this->internal_code = $internal_code;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAddressStreet(): ?string
    {
        return $this->address_street;
    }

    public function getAddressNumber(): ?string
    {
        return $this->address_number;
    }

    public function getAddressComplement(): ?string
    {
        return $this->address_complement;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getCep(): ?string
    {
        return $this->cep;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setAddressStreet(?string $address_street): static
    {
        $this->address_street = $address_street;

        return $this;
    }

    public function setAddressNumber(?string $address_number): static
    {
        $this->address_number = $address_number;

        return $this;
    }

    public function setAddressComplement(?string $address_complement): static
    {
        $this->address_complement = $address_complement;

        return $this;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function setCep(?string $cep): static
    {
        $this->cep = $cep;

        return $this;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getMembersLimit(): ?int
    {
        return $this->members_limit;
    }

    public function setMembersLimit(?int $members_limit): static
    {
        $this->members_limit = $members_limit;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTime();
    }

    /** @return Collection<int, Member> */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(Member $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setChurch($this);
        }
        return $this;
    }

    public function removeMember(Member $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getChurch() === $this) {
                $member->setChurch(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, MemberTransfer> */
    public function getOutgoingTransfers(): Collection
    {
        return $this->outgoingTransfers;
    }

    /** @return Collection<int, MemberTransfer> */
    public function getIncomingTransfers(): Collection
    {
        return $this->incomingTransfers;
    }
}