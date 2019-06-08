<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="orders")
 */
class Order
{
    const STATUS_NEW = 'new';
    const STATUS_PAID = 'paid';

    protected static $statuses = [
        self::STATUS_NEW => 'Новый',
        self::STATUS_PAID => 'Оплачен',
    ];

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="json")
     * @var array
     */
    protected $products;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $status;

    public function __construct()
    {
        $this->products = [];
        $this->status = self::STATUS_NEW;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param int $id
     *
     * @return Order
     */
    public function addProduct(int $id): Order
    {
        if (!in_array($id, $this->products)) {
            $this->products[] = $id;
        }

        return $this;
    }

    /**
     * @param int $id
     *
     * @return Order
     */
    public function removeProduct(int $id): Order
    {
        if (in_array($id, $this->products)) {
            unset($this->products[array_search($id, $this->products)]);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Order
     */
    public function setStatus(string $status): Order
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusName(): string
    {
        return self::$statuses[$this->status];
    }
}
