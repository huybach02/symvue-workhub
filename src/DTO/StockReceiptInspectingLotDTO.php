<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback(callback: "validateInspection", groups: ["create"])]
final readonly class StockReceiptInspectingLotDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ["create"])] #[
            Assert\Length(exactly: 6, groups: ["create"]),
        ]
        #[
            Assert\Regex(
                pattern: '/^[A-Z0-9]{6}$/',
                message: "Mã dòng lô phải gồm đúng 6 ký tự A-Z hoặc 0-9",
                groups: ["create"],
            ),
        ]
        public ?string $clientLineUuid = null,

        #[Assert\NotBlank(groups: ["create"])] #[
            Assert\Type(type: ["string", "numeric"], groups: ["create"]),
        ]
        #[
            Assert\Regex(
                pattern: '/^\d{1,12}(?:\.\d{1,6})?$/',
                message: "Số lượng nhận phải là số thập phân có tối đa 6 chữ số lẻ",
                groups: ["create"],
            ),
        ]
        public string|int|float|null $receivedQuantity = null,

        #[Assert\NotBlank(groups: ["create"])] #[
            Assert\Positive(groups: ["create"]),
        ]
        public ?int $receivedUnitId = null,

        #[Assert\NotBlank(groups: ["create"])] #[
            Assert\Type(type: ["string", "numeric"], groups: ["create"]),
        ]
        #[
            Assert\Regex(
                pattern: '/^\d{1,12}(?:\.\d{1,6})?$/',
                message: "Số lượng chấp nhận phải là số thập phân có tối đa 6 chữ số lẻ",
                groups: ["create"],
            ),
        ]
        public string|int|float|null $acceptedQuantity = null,

        public ?string $rejectionReason = null,

        #[Assert\NotBlank(groups: ["create"])] #[
            Assert\Date(groups: ["create"]),
        ]
        public ?string $manufactureDate = null,

        #[Assert\NotBlank(groups: ["create"])] #[
            Assert\Date(groups: ["create"]),
        ]
        public ?string $expiryDate = null,

        #[
            Assert\Length(max: 100, groups: ["create"]),
        ]
        public ?string $supplierLotCode = null,

        public ?string $note = null,
    ) {}

    public function validateInspection(ExecutionContextInterface $context): void
    {
        $receivedQtyStr =
            $this->receivedQuantity !== null
                ? (string) $this->receivedQuantity
                : null;
        $acceptedQtyStr =
            $this->acceptedQuantity !== null
                ? (string) $this->acceptedQuantity
                : null;

        if (
            $receivedQtyStr !== null &&
            preg_match('/^\d{1,12}(?:\.\d{1,6})?$/', $receivedQtyStr)
        ) {
            if (bccomp($receivedQtyStr, "0", 6) <= 0) {
                $context
                    ->buildViolation("Số lượng nhận phải lớn hơn 0")
                    ->atPath("receivedQuantity")
                    ->addViolation();
            }
        }

        if (
            $receivedQtyStr !== null &&
            $acceptedQtyStr !== null &&
            preg_match('/^\d{1,12}(?:\.\d{1,6})?$/', $receivedQtyStr) &&
            preg_match('/^\d{1,12}(?:\.\d{1,6})?$/', $acceptedQtyStr) &&
            bccomp($acceptedQtyStr, $receivedQtyStr, 6) > 0
        ) {
            $context
                ->buildViolation(
                    "Số lượng chấp nhận không được lớn hơn số lượng nhận",
                )
                ->atPath("acceptedQuantity")
                ->addViolation();
        }

        if (
            $receivedQtyStr !== null &&
            $acceptedQtyStr !== null &&
            preg_match('/^\d{1,12}(?:\.\d{1,6})?$/', $receivedQtyStr) &&
            preg_match('/^\d{1,12}(?:\.\d{1,6})?$/', $acceptedQtyStr) &&
            bccomp($receivedQtyStr, $acceptedQtyStr, 6) > 0 &&
            trim((string) $this->rejectionReason) === ""
        ) {
            $context
                ->buildViolation(
                    "Lý do từ chối là bắt buộc khi có hàng bị từ chối",
                )
                ->atPath("rejectionReason")
                ->addViolation();
        }

        if (
            $this->manufactureDate !== null &&
            $this->expiryDate !== null &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->manufactureDate) &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->expiryDate) &&
            $this->expiryDate <= $this->manufactureDate
        ) {
            $context
                ->buildViolation("Hạn sử dụng phải sau ngày sản xuất")
                ->atPath("expiryDate")
                ->addViolation();
        }
    }
}
