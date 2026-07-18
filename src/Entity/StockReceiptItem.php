<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockReceiptItemRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockReceiptItemRepository::class)]
#[ORM\Table(name: 'stock_receipt_item')]
class StockReceiptItem
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StockReceiptProvider::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'receipt_provider_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Provider process chứa merchandise'])]
    private ?StockReceiptProvider $receiptProvider = null;

    #[ORM\Column(type: Types::GUID, options: ['comment' => 'ID ổn định của dòng merchandise trong payload Request'])]
    private ?string $sourceRequestLineId = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'source_receipt_item_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Dòng nguồn nếu đây là merchandise của phiếu bổ sung'])]
    private ?self $sourceReceiptItem = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'merchandise_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Merchandise cần nhập'])]
    private ?Merchandise $merchandise = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Mã merchandise tại thời điểm tạo phiếu'])]
    private ?string $merchandiseCodeSnapshot = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Tên merchandise tại thời điểm tạo phiếu'])]
    private ?string $merchandiseNameSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng yêu cầu theo đơn vị trên đề xuất'])]
    private ?string $expectedQuantity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'expected_unit_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Đơn vị yêu cầu, ví dụ thùng'])]
    private ?Unit $expectedUnit = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Nhãn đơn vị tại thời điểm tạo, ví dụ “Thùng 36 chai”'])]
    private ?string $expectedUnitLabelSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 8, options: ['comment' => 'Hệ số quy đổi đơn vị yêu cầu về base unit'])]
    private ?string $expectedFactorToBase = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng yêu cầu đã quy đổi về base unit: expected_quantity * expected_factor_to_base'])]
    private ?string $expectedBaseQuantity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'base_unit_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Base unit của merchandise'])]
    private ?Unit $baseUnit = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Tên base unit tại thời điểm tạo phiếu'])]
    private ?string $baseUnitLabelSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, options: ['comment' => 'Giá nhập trên một đơn vị yêu cầu'])]
    private ?string $unitPriceSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, options: ['comment' => 'Giá nhập quy đổi trên một base unit: unit_price_snapshot / expected_factor_to_base'])]
    private ?string $baseUnitCostSnapshot = null;

    #[ORM\Column(length: 10, options: ['comment' => 'Tiền tệ, ví dụ VND'])]
    private ?string $currency = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Thứ tự hiển thị'])]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú dòng hàng'])]
    private ?string $note = null;

    /**
     * @var Collection<int, StockReceiptItemLot>
     */
    #[ORM\OneToMany(targetEntity: StockReceiptItemLot::class, mappedBy: 'receiptItem', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lots;

    public function __construct()
    {
        $this->lots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReceiptProvider(): ?StockReceiptProvider
    {
        return $this->receiptProvider;
    }

    public function setReceiptProvider(?StockReceiptProvider $receiptProvider): static
    {
        $this->receiptProvider = $receiptProvider;
        return $this;
    }

    public function getSourceRequestLineId(): ?string
    {
        return $this->sourceRequestLineId;
    }

    public function setSourceRequestLineId(string $sourceRequestLineId): static
    {
        $this->sourceRequestLineId = $sourceRequestLineId;
        return $this;
    }

    public function getSourceReceiptItem(): ?self
    {
        return $this->sourceReceiptItem;
    }

    public function setSourceReceiptItem(?self $sourceReceiptItem): static
    {
        $this->sourceReceiptItem = $sourceReceiptItem;
        return $this;
    }

    public function getMerchandise(): ?Merchandise
    {
        return $this->merchandise;
    }

    public function setMerchandise(?Merchandise $merchandise): static
    {
        $this->merchandise = $merchandise;
        return $this;
    }

    public function getMerchandiseCodeSnapshot(): ?string
    {
        return $this->merchandiseCodeSnapshot;
    }

    public function setMerchandiseCodeSnapshot(string $merchandiseCodeSnapshot): static
    {
        $this->merchandiseCodeSnapshot = $merchandiseCodeSnapshot;
        return $this;
    }

    public function getMerchandiseNameSnapshot(): ?string
    {
        return $this->merchandiseNameSnapshot;
    }

    public function setMerchandiseNameSnapshot(string $merchandiseNameSnapshot): static
    {
        $this->merchandiseNameSnapshot = $merchandiseNameSnapshot;
        return $this;
    }

    public function getExpectedQuantity(): ?string
    {
        return $this->expectedQuantity;
    }

    public function setExpectedQuantity(string $expectedQuantity): static
    {
        $this->expectedQuantity = $expectedQuantity;
        return $this;
    }

    public function getExpectedUnit(): ?Unit
    {
        return $this->expectedUnit;
    }

    public function setExpectedUnit(?Unit $expectedUnit): static
    {
        $this->expectedUnit = $expectedUnit;
        return $this;
    }

    public function getExpectedUnitLabelSnapshot(): ?string
    {
        return $this->expectedUnitLabelSnapshot;
    }

    public function setExpectedUnitLabelSnapshot(string $expectedUnitLabelSnapshot): static
    {
        $this->expectedUnitLabelSnapshot = $expectedUnitLabelSnapshot;
        return $this;
    }

    public function getExpectedFactorToBase(): ?string
    {
        return $this->expectedFactorToBase;
    }

    public function setExpectedFactorToBase(string $expectedFactorToBase): static
    {
        $this->expectedFactorToBase = $expectedFactorToBase;
        return $this;
    }

    public function getExpectedBaseQuantity(): ?string
    {
        return $this->expectedBaseQuantity;
    }

    public function setExpectedBaseQuantity(string $expectedBaseQuantity): static
    {
        $this->expectedBaseQuantity = $expectedBaseQuantity;
        return $this;
    }

    public function getBaseUnit(): ?Unit
    {
        return $this->baseUnit;
    }

    public function setBaseUnit(?Unit $baseUnit): static
    {
        $this->baseUnit = $baseUnit;
        return $this;
    }

    public function getBaseUnitLabelSnapshot(): ?string
    {
        return $this->baseUnitLabelSnapshot;
    }

    public function setBaseUnitLabelSnapshot(string $baseUnitLabelSnapshot): static
    {
        $this->baseUnitLabelSnapshot = $baseUnitLabelSnapshot;
        return $this;
    }

    public function getUnitPriceSnapshot(): ?string
    {
        return $this->unitPriceSnapshot;
    }

    public function setUnitPriceSnapshot(string $unitPriceSnapshot): static
    {
        $this->unitPriceSnapshot = $unitPriceSnapshot;
        return $this;
    }

    public function getBaseUnitCostSnapshot(): ?string
    {
        return $this->baseUnitCostSnapshot;
    }

    public function setBaseUnitCostSnapshot(string $baseUnitCostSnapshot): static
    {
        $this->baseUnitCostSnapshot = $baseUnitCostSnapshot;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
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
     * @return Collection<int, StockReceiptItemLot>
     */
    public function getLots(): Collection
    {
        return $this->lots;
    }

    public function addLot(StockReceiptItemLot $lot): static
    {
        if (!$this->lots->contains($lot)) {
            $this->lots->add($lot);
            $lot->setReceiptItem($this);
        }
        return $this;
    }

    public function removeLot(StockReceiptItemLot $lot): static
    {
        if ($this->lots->removeElement($lot)) {
            if ($lot->getReceiptItem() === $this) {
                $lot->setReceiptItem(null);
            }
        }
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'receiptProviderId' => $this->receiptProvider?->getId(),
            'sourceRequestLineId' => $this->sourceRequestLineId,
            'sourceReceiptItemId' => $this->sourceReceiptItem?->getId(),
            'merchandiseId' => $this->merchandise?->getId(),
            'merchandiseCodeSnapshot' => $this->merchandiseCodeSnapshot,
            'merchandiseNameSnapshot' => $this->merchandiseNameSnapshot,
            'expectedQuantity' => $this->expectedQuantity,
            'expectedUnitId' => $this->expectedUnit?->getId(),
            'expectedUnitLabelSnapshot' => $this->expectedUnitLabelSnapshot,
            'expectedFactorToBase' => $this->expectedFactorToBase,
            'expectedBaseQuantity' => $this->expectedBaseQuantity,
            'baseUnitId' => $this->baseUnit?->getId(),
            'baseUnitLabelSnapshot' => $this->baseUnitLabelSnapshot,
            'unitPriceSnapshot' => $this->unitPriceSnapshot,
            'baseUnitCostSnapshot' => $this->baseUnitCostSnapshot,
            'currency' => $this->currency,
            'sortOrder' => $this->sortOrder,
            'note' => $this->note,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
