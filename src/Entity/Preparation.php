<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Preparation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column]
    private float $yieldPortions = 10.0;

    #[ORM\OneToMany(mappedBy: 'preparation', targetEntity: PreparationItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(string $name = '', float $yieldPortions = 10.0)
    {
        $this->name = $name;
        $this->yieldPortions = max(0.01, $yieldPortions);
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getYieldPortions(): float { return $this->yieldPortions; }
    public function setYieldPortions(float $yieldPortions): self { $this->yieldPortions = max(0.01, $yieldPortions); return $this; }
    /** @return Collection<int, PreparationItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(Ingredient $ingredient, float $quantity, string $unit): self
    {
        $this->items->add(new PreparationItem($this, $ingredient, $quantity, $unit));
        return $this;
    }
}
