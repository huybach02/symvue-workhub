<template>
    <v-dialog v-model="dialog" max-width="1400" scrollable>
            <v-card>
                <v-card-title
                    class="d-flex align-start justify-space-between py-3 px-4 bg-grey-lighten-4 ga-2"
                >
                    <div
                        class="d-flex align-start ga-2 text-wrap pr-2"
                        style="word-break: break-word;"
                    >
                        <v-icon
                            icon="mdi-clipboard-check-outline"
                            color="warning"
                            class="mt-1 flex-shrink-0"
                        />
                        <span
                            class="font-weight-bold text-h6 text-grey-darken-3 text-wrap"
                            style="word-break: break-word;"
                        >
                            {{
                                $t("stock_receipt.inspection_history.title") ||
                                "Lịch sử kiểm hàng"
                            }}: {{ item?.code }}
                        </span>
                    </div>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        size="small"
                        class="flex-shrink-0 align-self-start"
                        @click="dialog = false"
                    />
                </v-card-title>

                <v-divider />

                <v-card-text class="pa-4">
                    <div
                        v-if="loading"
                        class="d-flex justify-center align-center py-8"
                    >
                        <v-progress-circular indeterminate color="warning" />
                    </div>

                    <div
                        v-else-if="!hasInspectionData"
                        class="text-center text-grey py-8"
                    >
                        <v-icon
                            icon="mdi-clipboard-text-off-outline"
                            size="48"
                            color="grey-lighten-1"
                            class="mb-2"
                        />
                        <div>
                            {{
                                $t(
                                    "stock_receipt.inspection_history.no_data",
                                ) || "Chưa có dữ liệu kiểm hàng"
                            }}
                        </div>
                    </div>

                    <div v-else>
                        <v-card
                            v-for="provider in detailData?.providers || []"
                            :key="provider.id"
                            variant="flat"
                            border
                            class="mb-4"
                        >
                            <!-- Header nhà cung cấp -->
                            <div
                                class="d-flex align-center justify-space-between flex-wrap ga-2 pa-3 bg-grey-lighten-4 border-b"
                            >
                                <div
                                    class="d-flex align-center flex-wrap ga-2 text-wrap"
                                    style="word-break: break-word;"
                                >
                                    <v-icon
                                        icon="mdi-truck-delivery-outline"
                                        color="primary"
                                        class="flex-shrink-0"
                                    />
                                    <span
                                        class="font-weight-bold text-subtitle-1 text-grey-darken-3 text-wrap"
                                        style="word-break: break-word;"
                                    >
                                        {{
                                            provider.providerSnapshot?.name ||
                                            "N/A"
                                        }}
                                    </span>
                                    <v-chip
                                        size="small"
                                        :color="
                                            getProviderStatusColor(
                                                provider.status,
                                            )
                                        "
                                        variant="flat"
                                        class="flex-shrink-0"
                                    >
                                        {{
                                            $t(
                                                "stock_receipt.provider_status." +
                                                    provider.status,
                                            ) || provider.status
                                        }}
                                    </v-chip>
                                    <v-chip
                                        size="small"
                                        :color="
                                            getFulfillmentColor(
                                                provider.fulfillmentStatus,
                                            )
                                        "
                                        variant="tonal"
                                        class="flex-shrink-0"
                                    >
                                        {{
                                            $t(
                                                "stock_receipt.fulfillment." +
                                                    provider.fulfillmentStatus,
                                            ) || provider.fulfillmentStatus
                                        }}
                                    </v-chip>
                                </div>

                                <div
                                    class="d-flex align-center ga-3 text-body-2 text-grey-darken-1"
                                >
                                    <span v-if="provider.inspectedAt">
                                        <v-icon
                                            icon="mdi-clock-outline"
                                            size="14"
                                            class="mr-1"
                                        />
                                        {{
                                            $t(
                                                "stock_receipt.inspection_history.inspected_at",
                                            ) || "Thời gian"
                                        }}: {{ provider.inspectedAt }}
                                    </span>
                                </div>
                            </div>

                            <!-- Danh sách hàng hóa & lô kiểm -->
                            <div class="pa-3">
                                <div
                                    v-for="(rItem, itemIdx) in provider.items ||
                                    []"
                                    :key="rItem.id"
                                    class="mb-3 border rounded pa-3 bg-white"
                                >
                                    <div
                                        class="d-flex align-center justify-space-between flex-wrap ga-2 mb-2"
                                    >
                                        <div
                                            class="d-flex align-center flex-wrap ga-2 text-wrap"
                                            style="word-break: break-word;"
                                        >
                                            <v-chip
                                                size="small"
                                                color="primary"
                                                variant="flat"
                                                class="flex-shrink-0"
                                            >
                                                {{ itemIdx + 1 }}
                                            </v-chip>
                                            <span
                                                class="font-weight-bold text-subtitle-1 text-grey-darken-3 text-wrap"
                                                style="word-break: break-word;"
                                            >
                                                {{
                                                    rItem.merchandiseNameSnapshot
                                                }}
                                            </span>
                                            <span
                                                class="text-body-2 text-grey flex-shrink-0"
                                            >
                                                ({{
                                                    rItem.merchandiseCodeSnapshot
                                                }})
                                            </span>
                                        </div>

                                        <div
                                            class="d-flex align-center flex-wrap ga-1 text-body-2"
                                        >
                                            <span
                                                class="text-grey-darken-1 font-weight-medium"
                                            >
                                                {{
                                                    $t(
                                                        "stock_receipt.inspection_history.expected_qty",
                                                    ) || "Số lượng yêu cầu"
                                                }}:
                                            </span>
                                            <v-chip
                                                size="small"
                                                color="primary"
                                                variant="tonal"
                                                class="font-weight-bold"
                                            >
                                                {{
                                                    formatDecimal(
                                                        rItem.expectedQuantity,
                                                    )
                                                }}
                                            </v-chip>
                                            <v-chip
                                                size="small"
                                                color="secondary"
                                                variant="tonal"
                                            >
                                                {{
                                                    rItem.expectedUnitLabelSnapshot
                                                }}
                                            </v-chip>
                                        </div>
                                    </div>

                                    <!-- Bảng các lô kiểm hàng -->
                                    <div
                                        v-if="
                                            !rItem.lots ||
                                            rItem.lots.length === 0
                                        "
                                        class="text-caption text-grey italic pa-2 text-center border rounded bg-grey-lighten-5"
                                    >
                                        Chưa có thông tin lô kiểm hàng
                                    </div>

                                    <div v-else class="overflow-x-auto">
                                        <v-table
                                            density="compact"
                                            border
                                            class="rounded"
                                            style="min-width: 550px;"
                                        >
                                        <thead>
                                            <tr>
                                                <th class="text-left">
                                                    {{
                                                        $t(
                                                            "stock_receipt.inspection_history.mfg_exp_date",
                                                        ) || "NSX / HSD"
                                                    }}
                                                </th>
                                                <th class="text-right">
                                                    {{
                                                        $t(
                                                            "stock_receipt.inspection_history.received_qty",
                                                        ) || "Số nhận"
                                                    }}
                                                </th>
                                                <th class="text-right">
                                                    {{
                                                        $t(
                                                            "stock_receipt.inspection_history.accepted_qty",
                                                        ) || "Chấp nhận"
                                                    }}
                                                </th>
                                                <th class="text-right">
                                                    {{
                                                        $t(
                                                            "stock_receipt.inspection_history.rejected_qty",
                                                        ) || "Từ chối"
                                                    }}
                                                </th>
                                                <th class="text-left">
                                                    {{
                                                        $t(
                                                            "stock_receipt.inspection_history.supplier_lot",
                                                        ) || "Mã lô NSX"
                                                    }}
                                                </th>
                                                <th class="text-left">
                                                    {{
                                                        $t(
                                                            "stock_receipt.inspection_history.rejection_reason",
                                                        ) || "Lý do từ chối"
                                                    }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="lot in rItem.lots"
                                                :key="lot.id"
                                            >
                                                <td class="text-body-2">
                                                    <div
                                                        class="d-flex align-center ga-1"
                                                    >
                                                        <v-icon
                                                            icon="mdi-calendar-range"
                                                            size="16"
                                                            color="grey-darken-1"
                                                        />
                                                        <span>{{
                                                            lot.manufacture_date ||
                                                            "--"
                                                        }}</span>
                                                        <span>➔</span>
                                                        <span>{{
                                                            lot.expiry_date ||
                                                            "--"
                                                        }}</span>
                                                    </div>
                                                </td>
                                                <td
                                                    class="text-right text-body-2"
                                                >
                                                    <div
                                                        class="d-flex align-center justify-end ga-1"
                                                    >
                                                        <v-chip
                                                            size="small"
                                                            color="info"
                                                            variant="tonal"
                                                            class="font-weight-medium"
                                                        >
                                                            {{
                                                                formatDecimal(
                                                                    lot.received_quantity,
                                                                )
                                                            }}
                                                        </v-chip>
                                                        <v-chip
                                                            size="small"
                                                            color="secondary"
                                                            variant="tonal"
                                                        >
                                                            {{
                                                                lot.received_unit_label_snapshot
                                                            }}
                                                        </v-chip>
                                                    </div>
                                                </td>
                                                <td
                                                    class="text-right text-body-2"
                                                >
                                                    <div
                                                        class="d-flex align-center justify-end ga-1"
                                                    >
                                                        <v-chip
                                                            size="small"
                                                            color="success"
                                                            variant="tonal"
                                                            class="font-weight-bold"
                                                        >
                                                            {{
                                                                formatDecimal(
                                                                    lot.accepted_quantity,
                                                                )
                                                            }}
                                                        </v-chip>
                                                        <v-chip
                                                            size="small"
                                                            color="secondary"
                                                            variant="tonal"
                                                        >
                                                            {{
                                                                lot.received_unit_label_snapshot
                                                            }}
                                                        </v-chip>
                                                    </div>
                                                </td>
                                                <td
                                                    class="text-right text-body-2"
                                                >
                                                    <div
                                                        class="d-flex align-center justify-end ga-1"
                                                    >
                                                        <v-chip
                                                            size="small"
                                                            :color="
                                                                Number(
                                                                    lot.rejected_quantity,
                                                                ) > 0
                                                                    ? 'error'
                                                                    : 'grey'
                                                            "
                                                            :variant="
                                                                Number(
                                                                    lot.rejected_quantity,
                                                                ) > 0
                                                                    ? 'flat'
                                                                    : 'tonal'
                                                            "
                                                            class="font-weight-bold"
                                                        >
                                                            {{
                                                                formatDecimal(
                                                                    lot.rejected_quantity,
                                                                )
                                                            }}
                                                        </v-chip>
                                                        <v-chip
                                                            size="small"
                                                            color="secondary"
                                                            variant="tonal"
                                                        >
                                                            {{
                                                                lot.received_unit_label_snapshot
                                                            }}
                                                        </v-chip>
                                                    </div>
                                                </td>
                                                <td class="text-body-2">
                                                    {{
                                                        lot.supplier_lot_code ||
                                                        "--"
                                                    }}
                                                </td>
                                                <td
                                                    class="text-body-2 text-grey-darken-2"
                                                >
                                                    {{
                                                        lot.rejection_reason ||
                                                        "--"
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </v-table>
                                    </div>
                                </div>
                            </div>
                        </v-card>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>
</template>

<script>
import { mapActions } from "vuex";
import { constant } from "@/utils/constants/constant";

export default {
    name: "StockReceiptInspectionDialog",
    props: {
        item: {
            type: Object,
            required: true,
        },
        modelValue: {
            type: Boolean,
            default: undefined,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            internalDialog: false,
            loading: false,
            detailData: null,
        };
    },
    computed: {
        dialog: {
            get() {
                return this.modelValue !== undefined
                    ? this.modelValue
                    : this.internalDialog;
            },
            set(val) {
                this.internalDialog = val;
                this.$emit("update:modelValue", val);
            },
        },
        hasInspectionData() {
            if (!this.detailData?.providers) return false;

            return this.detailData.providers.some((provider) =>
                provider.items?.some(
                    (item) => item.lots && item.lots.length > 0,
                ),
            );
        },
    },
    watch: {
        dialog: {
            immediate: true,
            async handler(isOpen) {
                if (isOpen && this.item?.id) {
                    this.loadData();
                }
            },
        },
        item: {
            immediate: true,
            async handler(newItem) {
                if (this.dialog && newItem?.id) {
                    this.loadData();
                }
            },
        },
    },
    methods: {
        ...mapActions("stockReceipt", ["fetchItemDetail"]),
        async loadData() {
            if (!this.item?.id) return;
            this.loading = true;
            try {
                const detail = await this.fetchItemDetail({
                    id: this.item.id,
                    force: true,
                });
                this.detailData = detail;
            } finally {
                this.loading = false;
            }
        },
        getProviderStatusColor(status) {
            return constant.STOCK_RECEIPT_STATUS_COLORS[status] || "grey";
        },
        getFulfillmentColor(status) {
            return constant.FULFILLMENT_STATUS_COLORS[status] || "grey";
        },
        formatDecimal(val) {
            if (val === null || val === undefined || val === "") return "--";
            const str = String(val).trim();
            if (!str.includes(".")) return str;
            const [intPart, decPart] = str.split(".");
            const cleanDec = decPart.replace(/0+$/, "");
            return cleanDec ? `${intPart}.${cleanDec}` : intPart;
        },
    },
};
</script>
