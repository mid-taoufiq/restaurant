<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class StockMovement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ingredient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ingredient $ingredient;

    #[ORM\Column]
    private float $quantityDelta;

    #[ORM\Column(length: 40)]
    private string $type; // purchase, service_consumption, loss, inventory_adjustment

    #[ORM\Column(length: 255)]
    private string $reason;

    #[ORM\Column]
    private float $unitCostAtMovement;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Ingredient $ingredient,
        float $quantityDelta,
        string $type,
        string $reason,
        ?float $unitCostAtMovement = null
    ) {
        $this->ingredient = $ingredient;
        $this->quantityDelta = $quantityDelta;
        $this->type = $type;
        $this->reason = $reason;
        $this->unitCostAtMovement = $unitCostAtMovement ?? $ingredient->getUnitCost();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getIngredient(): Ingredient { return $this->ingredient; }
    public function getQuantityDelta(): float { return $this->quantityDelta; }
    public function getType(): string { return $this->type; }
    public function getReason(): string { return $this->reason; }
    public function getUnitCostAtMovement(): float { return $this->unitCostAtMovement; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
