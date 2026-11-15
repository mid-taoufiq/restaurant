<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Dish
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 30)]
    private string $category; // starter, main, dessert, drink

    #[ORM\Column]
    private float $price;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'dish', targetEntity: DishIngredient::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dishIngredients;

    #[ORM\OneToMany(mappedBy: 'dish', targetEntity: DishPreparation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dishPreparations;

    public function __construct(string $name = '', string $category = 'main', float $price = 10.0)
    {
        $this->name = $name;
        $this->category = $category;
        $this->price = $price;
        $this->dishIngredients = new ArrayCollection();
        $this->dishPreparations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }
    public function getPrice(): float { return $this->price; }
    public function setPrice(float $price): self { $this->price = $price; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
    /** @return Collection<int, DishIngredient> */
    public function getDishIngredients(): Collection { return $this->dishIngredients; }
    /** @return Collection<int, DishPreparation> */
    public function getDishPreparations(): Collection { return $this->dishPreparations; }

    public function addIngredient(Ingredient $ingredient, float $quantity, string $unit): self
    {
        $this->dishIngredients->add(new DishIngredient($this, $ingredient, $quantity, $unit));
        return $this;
    }

    public function addPreparation(Preparation $preparation, float $portions = 1.0): self
    {
        $this->dishPreparations->add(new DishPreparation($this, $preparation, $portions));
        return $this;
    }

    public function getAllergens(): array
    {
        $allergens = [];
        foreach ($this->dishIngredients as $di) {
            foreach ($di->getIngredient()->getAllergens() as $a) {
                $allergens[$a] = true;
            }
        }
        foreach ($this->dishPreparations as $dp) {
            foreach ($dp->getPreparation()->getItems() as $pi) {
                foreach ($pi->getIngredient()->getAllergens() as $a) {
                    $allergens[$a] = true;
                }
            }
        }
        return array_keys($allergens);
    }
}
