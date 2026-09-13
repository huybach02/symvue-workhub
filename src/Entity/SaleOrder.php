<?php

declare(strict_types=1);

namespace App\Entity;

use App\Class\SaleOrderPaymentStatus;
use App\Class\SaleOrderStatus;
use App\Repository\SaleOrderRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: SaleOrderRepository::class)]
#[ORM\Table(name: 'sale_order')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_SALE_ORDER_CODE',
    fields: ['code'],
    options: ['where' => 'deleted_at IS NULL']
)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class SaleOrder implements \JsonSerializable
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'Khóa chính đơn bán hàng'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, options: ['comment' => 'Mã đơn bán hàng (HD-YYYYMMDD-XXXX)'])]
    private ?string $code = null;

    #[ORM\Column(name: 'order_date', type: Types::DATETIME_MUTABLE, options: ['comment' => 'Thời điểm tạo đơn'])]
    private ?\DateTimeInterface $orderDate = null;

    #[ORM\ManyToOne(targetEntity: Branch::class)]
    #[ORM\JoinColumn(name: 'branch_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Branch $branch = null;

    #[ORM\ManyToOne(targetEntity: DiningTable::class)]
    #[ORM\JoinColumn(name: 'dining_table_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?DiningTable $diningTable = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'cashier_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $cashier = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00', 'comment' => 'Tạm tính tiền hàng'])]
    private string $subtotal = '0.00';

    #[ORM\Column(name: 'discount_amount', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00', 'comment' => 'Tiền giảm giá/chiết khấu'])]
    private string $discountAmount = '0.00';

    #[ORM\Column(name: 'tax_amount', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00', 'comment' => 'Tiền thuế VAT'])]
    private string $taxAmount = '0.00';

    #[ORM\Column(name: 'total_amount', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00', 'comment' => 'Tổng tiền thanh toán thực tế'])]
    private string $totalAmount = '0.00';

    #[ORM\Column(name: 'payment_method', length: 30, nullable: true, options: ['comment' => 'Phương thức thanh toán'])]
    private ?string $paymentMethod = null;

    #[ORM\Column(name: 'payment_status', type: Types::SMALLINT, options: ['default' => 0, 'comment' => 'Trạng thái thanh toán (0: Chưa TT, 1: Đã TT, 2: Hoàn tiền)'])]
    private int $paymentStatus = SaleOrderPaymentStatus::Unpaid->value;

    #[ORM\Column(length: 30, options: ['default' => 'CREATED', 'comment' => 'Trạng thái đơn hàng: CREATED, PROCESSING, SHIPPED, COMPLETED'])]
    private string $status = SaleOrderStatus::Created->value;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú đơn hàng'])]
    private ?string $note = null;

    /**
     * @var Collection<int, SaleOrderItem>
     */
    #[ORM\OneToMany(targetEntity: SaleOrderItem::class, mappedBy: 'saleOrder', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->orderDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeInterface $orderDate): static
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    public function setBranch(?Branch $branch): static
    {
        $this->branch = $branch;
        return $this;
    }

    public function getDiningTable(): ?DiningTable
    {
        return $this->diningTable;
    }

    public function setDiningTable(?DiningTable $diningTable): static
    {
        $this->diningTable = $diningTable;
        return $this;
    }

    public function getCashier(): ?User
    {
        return $this->cashier;
    }

    public function setCashier(?User $cashier): static
    {
        $this->cashier = $cashier;
        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getDiscountAmount(): string
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(string $discountAmount): static
    {
        $this->discountAmount = $discountAmount;
        return $this;
    }

    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(string $taxAmount): static
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentStatus(): int
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(int $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return Collection<int, SaleOrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(SaleOrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setSaleOrder($this);
        }

        return $this;
    }

    public function removeItem(SaleOrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getSaleOrder() === $this) {
                $item->setSaleOrder(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'orderDate' => $this->orderDate?->format('Y-m-d H:i:s'),
            'branchId' => $this->branch?->getId(),
            'branch' => $this->branch ? [
                'id' => $this->branch->getId(),
                'code' => $this->branch->getCode(),
                'name' => $this->branch->getName(),
            ] : null,
            'diningTableId' => $this->diningTable?->getId(),
            'diningTable' => $this->diningTable ? [
                'id' => $this->diningTable->getId(),
                'tableNumber' => $this->diningTable->getTableNumber(),
            ] : null,
            'cashierId' => $this->cashier?->getId(),
            'cashier' => $this->cashier ? [
                'id' => $this->cashier->getId(),
                'name' => $this->cashier->getName(),
            ] : null,
            'subtotal' => formatDecimal($this->subtotal),
            'discountAmount' => formatDecimal($this->discountAmount),
            'taxAmount' => formatDecimal($this->taxAmount),
            'totalAmount' => formatDecimal($this->totalAmount),
            'paymentMethod' => $this->paymentMethod,
            'paymentStatus' => $this->paymentStatus,
            'status' => $this->status,
            'note' => $this->note,
            'items' => array_map(fn(SaleOrderItem $item) => $item->jsonSerialize(), $this->items->toArray()),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
