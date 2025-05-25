<?php

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    private ?string $name = null;
    
    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $description = null;
    
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;
    
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
    
    #[ORM\ManyToOne(inversedBy: 'boards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;
      #[ORM\OneToMany(mappedBy: 'board', targetEntity: BoardList::class, orphanRemoval: true, cascade: ["persist", "remove"])]
    #[ORM\OrderBy(["position" => "ASC"])]
    private Collection $lists;
    
    #[ORM\OneToMany(mappedBy: 'board', targetEntity: Label::class, orphanRemoval: true, cascade: ["persist", "remove"])]
    private Collection $labels;
    
    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uuid = null;
    
    public function __construct()
    {
        $this->lists = new ArrayCollection();
        $this->labels = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->uuid = Uuid::v4();
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
    
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        
        return $this;
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        
        return $this;
    }
    
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        
        return $this;
    }
    
    public function getOwner(): ?User
    {
        return $this->owner;
    }
    
    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        
        return $this;
    }
    
    /**
     * @return Collection<int, BoardList>
     */
    public function getLists(): Collection
    {
        return $this->lists;
    }
    
    public function addList(BoardList $list): static
    {
        if (!$this->lists->contains($list)) {
            $this->lists->add($list);
            $list->setBoard($this);
        }
        
        return $this;
    }
    
    public function removeList(BoardList $list): static
    {
        if ($this->lists->removeElement($list)) {
            // set the owning side to null (unless already changed)
            if ($list->getBoard() === $this) {
                $list->setBoard(null);
            }
        }
        
        return $this;
    }
    
    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }
    
    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;
        
        return $this;
    }
    
    /**
     * @return Collection<int, Label>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }
    
    public function addLabel(Label $label): static
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
            $label->setBoard($this);
        }
        
        return $this;
    }
    
    public function removeLabel(Label $label): static
    {
        if ($this->labels->removeElement($label)) {
            // set the owning side to null (unless already changed)
            if ($label->getBoard() === $this) {
                $label->setBoard(null);
            }
        }
        
        return $this;
    }
}
