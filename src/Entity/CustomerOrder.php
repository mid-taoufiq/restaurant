<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CustomerOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tableNumber;

    #[ORM\Column(length: 30)]
    private string $status = 'open'; // open, closed

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $waiterName = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'customerOrder', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(int $tableNumber = 1, ?string $waiterName = null)
    {
        $this->tableNumber = $tableNumber;
        $this->waiterName = $waiterName;
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTableNumber(): int { return $this->tableNumber; }
    public function setTableNumber(int $tableNumber): self { $this->tableNumber = $tableNumber; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getWaiterName(): ?string { return $this->waiterName; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(Dish $dish, int $quantity = 1, ?float $overrideUnitPrice = null): OrderItem
    {
        $item = new OrderItem($this, $dish, $quantity, $overrideUnitPrice ?? $dish->getPrice());
        $this->items->add($item);
        return $item;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            if ($item->getStatus() !== 'cancelled') {
                $total += $item->getUnitPrice() * $item->getQuantity();
            }
        }
        return $total;
    }
}
