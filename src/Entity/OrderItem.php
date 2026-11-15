<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CustomerOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CustomerOrder $customerOrder;

    #[ORM\ManyToOne(targetEntity: Dish::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Dish $dish;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column]
    private float $unitPrice;

    #[ORM\Column(nullable: true)]
    private ?float $unitCostSnapshot = null;

    #[ORM\Column(length: 30)]
    private string $status = 'pending'; // pending, preparing, ready, served, cancelled

    public function __construct(CustomerOrder $customerOrder, Dish $dish, int $quantity = 1, ?float $unitPrice = null)
    {
        $this->customerOrder = $customerOrder;
        $this->dish = $dish;
        $this->quantity = max(1, $quantity);
        $this->unitPrice = $unitPrice ?? $dish->getPrice();
    }

    public function getId(): ?int { return $this->id; }
    public function getCustomerOrder(): CustomerOrder { return $this->customerOrder; }
    public function getDish(): Dish { return $this->dish; }
    public function getQuantity(): int { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getUnitCostSnapshot(): ?float { return $this->unitCostSnapshot; }
    public function setUnitCostSnapshot(?float $unitCostSnapshot): self { $this->unitCostSnapshot = $unitCostSnapshot; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}
