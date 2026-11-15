<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PreparationItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Preparation::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Preparation $preparation;

    #[ORM\ManyToOne(targetEntity: Ingredient::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Ingredient $ingredient;

    #[ORM\Column]
    private float $quantity;

    #[ORM\Column(length: 20)]
    private string $unit;

    public function __construct(Preparation $preparation, Ingredient $ingredient, float $quantity, string $unit)
    {
        $this->preparation = $preparation;
        $this->ingredient = $ingredient;
        $this->quantity = $quantity;
        $this->unit = $unit;
    }

    public function getId(): ?int { return $this->id; }
    public function getPreparation(): Preparation { return $this->preparation; }
    public function getIngredient(): Ingredient { return $this->ingredient; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnit(): string { return $this->unit; }
}
