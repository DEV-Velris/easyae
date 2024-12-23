<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClientRepository;
use Doctrine\Common\Collections\Collection;
use App\Entity\Traits\StatisticsPropertiesTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Client
{
    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['account', 'contrat', 'client'])]

    private ?int $id = null;

    #[ORM\Column(length: 64)]

    #[Groups(['account', 'client', 'contrat'])]


    private ?string $name = null;

    /**
     * @var Collection<int, Account>
     */
    #[ORM\OneToMany(targetEntity: Account::class, mappedBy: 'client')]
    #[Groups(['client'])]

    private Collection $accounts;

    /**
     * @var Collection<int, Contrat>
     */
    #[ORM\OneToMany(targetEntity: Contrat::class, mappedBy: 'client')]
    #[Groups(['client'])]
    private Collection $contrats;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    #[Groups(['client'])]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(inversedBy: 'client', cascade: ['persist', 'remove'])]
    #[Groups(['client'])]
    private ?FacturationModel $facturationModel = null;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->contrats = new ArrayCollection();
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

    /**
     * @return Collection<int, Contrat>
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(Contrat $contrat): static
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats->add($contrat);
            $contrat->setClient($this);
        }

        return $this;
    }

    public function removeContrat(Contrat $contrat): static
    {
        if ($this->contrats->removeElement($contrat)) {
            // set the owning side to null (unless already changed)
            if ($contrat->getClient() === $this) {
                $contrat->setClient(null);
            }
        }

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getFacturationModel(): ?FacturationModel
    {
        return $this->facturationModel;
    }

    public function setFacturationModel(?FacturationModel $facturationModel): static
    {
        $this->facturationModel = $facturationModel;

        return $this;
    }
}
