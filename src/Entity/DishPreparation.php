<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DishPreparation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dish::class, inversedBy: 'dishPreparations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Dish $dish;

    #[ORM\ManyToOne(targetEntity: Preparation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Preparation $preparation;

    #[ORM\Column]
    private float $portions = 1.0;

    public function __construct(Dish $dish, Preparation $preparation, float $portions = 1.0)
    {
        $this->dish = $dish;
        $this->preparation = $preparation;
        $this->portions = $portions;
    }

    public function getId(): ?int { return $this->id; }
    public function getDish(): Dish { return $this->dish; }
    public function getPreparation(): Preparation { return $this->preparation; }
    public function getPortions(): float { return $this->portions; }
}
