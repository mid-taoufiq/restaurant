<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 20)]
    private string $unit; // kg, g, L, mL, piece

    #[ORM\Column]
    private float $currentStock = 0.0;

    #[ORM\Column]
    private float $minStock = 0.0;

    #[ORM\Column]
    private float $unitCost = 0.0;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $barcode = null;

    #[ORM\Column(type: 'json')]
    private array $allergens = [];

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'ingredients')]
    private ?Supplier $supplier = null;

    public function __construct(
        string $name = '',
        string $unit = 'kg',
        float $currentStock = 0.0,
        float $minStock = 1.0,
        float $unitCost = 1.0,
        ?Supplier $supplier = null,
        ?string $barcode = null,
        array $allergens = []
    ) {
        $this->name = $name;
        $this->unit = $unit;
        $this->currentStock = $currentStock;
        $this->minStock = $minStock;
        $this->unitCost = $unitCost;
        $this->supplier = $supplier;
        $this->barcode = $barcode;
        $this->allergens = $allergens;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getUnit(): string { return $this->unit; }
    public function setUnit(string $unit): self { $this->unit = $unit; return $this; }
    public function getCurrentStock(): float { return $this->currentStock; }
    public function setCurrentStock(float $currentStock): self { $this->currentStock = $currentStock; return $this; }
    public function getMinStock(): float { return $this->minStock; }
    public function setMinStock(float $minStock): self { $this->minStock = $minStock; return $this; }
    public function getUnitCost(): float { return $this->unitCost; }
    public function setUnitCost(float $unitCost): self { $this->unitCost = $unitCost; return $this; }
    public function getBarcode(): ?string { return $this->barcode; }
    public function setBarcode(?string $barcode): self { $this->barcode = $barcode; return $this; }
    public function getAllergens(): array { return $this->allergens; }
    public function setAllergens(array $allergens): self { $this->allergens = array_values(array_unique($allergens)); return $this; }
    public function getSupplier(): ?Supplier { return $this->supplier; }
    public function setSupplier(?Supplier $supplier): self { $this->supplier = $supplier; return $this; }

    public function isLowStock(): bool
    {
        return $this->currentStock <= $this->minStock;
    }
}
