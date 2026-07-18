<template>
    <div>
        <v-btn
            v-if="mode === 'update'"
            v-bind="$attrs"
            icon
            size="small"
            variant="outlined"
            color="primary"
            @click="dialog = true"
        >
            <v-icon>mdi-eye</v-icon>
        </v-btn>

        <!-- Dialog xem chi tiết phiếu -->
        <v-dialog v-model="dialog" max-width="1400" scrollable persistent>
            <v-card class="position-relative">
                <v-card-title
                    class="d-flex align-center justify-space-between py-3 px-4"
                >
                    <span class="font-weight-bold text-h6 text-grey-darken-3">
                        {{
                            $t("stock_receipt.detail_title") ||
                            "Chi tiết phiếu nhập kho"
                        }}: {{ item?.code }}
                    </span>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        size="small"
                        class="close-btn"
                        @click="dialog = false"
                    />
                </v-card-title>

                <v-card-text v-if="loadingDetail" class="pa-4">
                    <div class="d-flex justify-center align-center py-8">
                        <v-progress-circular indeterminate color="primary" />
                    </div>
                </v-card-text>

                <v-card-text v-else-if="detailData" class="pa-4">
                    <v-row dense>
                        <!-- Thông tin chung -->
                        <v-col cols="12">
                            <v-card variant="flat" border class="pa-4 mb-4">
                                <v-row dense class="align-center">
                                    <v-col cols="12" sm="6" md="4" class="py-1">
                                        <div class="d-flex align-center ga-2">
                                            <span
                                                class="text-subtitle-2 text-medium-emphasis"
                                                >{{
                                                    $t(
                                                        "stock_receipt.columns.code",
                                                    ) || "Mã phiếu"
                                                }}:
                                            </span>
                                            <span
                                                class="text-body-1 font-weight-bold text-primary"
                                                >{{ detailData.code }}
                                            </span>
                                        </div>
                                    </v-col>
                                    <v-col cols="12" sm="6" md="4" class="py-1">
                                        <div class="d-flex align-center ga-2">
                                            <span
                                                class="text-subtitle-2 text-medium-emphasis"
                                                >{{
                                                    $t(
                                                        "stock_receipt.columns.request",
                                                    ) || "Đề xuất nguồn"
                                                }}:
                                            </span>
                                            <span
                                                class="text-body-1 font-weight-medium text-grey-darken-3"
                                                >{{
                                                    detailData.requestCode ||
                                                    "--"
                                                }}
                                            </span>
                                        </div>
                                    </v-col>
                                    <v-col cols="12" sm="6" md="4" class="py-1">
                                        <div class="d-flex align-center ga-2">
                                            <span
                                                class="text-subtitle-2 text-medium-emphasis"
                                                >{{
                                                    $t(
                                                        "stock_receipt.columns.warehouse",
                                                    ) || "Kho nhận"
                                                }}:
                                            </span>
                                            <span
                                                class="text-body-1 font-weight-medium text-grey-darken-3"
                                                >{{
                                                    detailData.warehouseSnapshot
                                                        ?.name || "--"
                                                }}
                                            </span>
                                        </div>
                                    </v-col>
                                    <v-col
                                        cols="12"
                                        sm="6"
                                        md="4"
                                        class="py-1 mt-sm-2"
                                    >
                                        <div class="d-flex align-center ga-2">
                                            <span
                                                class="text-subtitle-2 text-medium-emphasis"
                                                >{{
                                                    $t("base.status") ||
                                                    "Trạng thái"
                                                }}:
                                            </span>
                                            <v-chip
                                                size="small"
                                                :color="
                                                    getStatusColor(
                                                        detailData.status,
                                                    )
                                                "
                                                variant="flat"
                                                class="font-weight-bold"
                                            >
                                                {{
                                                    $t(
                                                        "stock_receipt.status." +
                                                            detailData.status,
                                                    ) || detailData.status
                                                }}
                                            </v-chip>
                                        </div>
                                    </v-col>
                                    <v-col
                                        cols="12"
                                        sm="6"
                                        md="4"
                                        class="py-1 mt-sm-2"
                                    >
                                        <div class="d-flex align-center ga-2">
                                            <span
                                                class="text-subtitle-2 text-medium-emphasis"
                                                >{{
                                                    $t(
                                                        "stock_receipt.columns.fulfillment_status",
                                                    ) || "Đáp ứng"
                                                }}:
                                            </span>
                                            <v-chip
                                                size="small"
                                                :color="
                                                    getFulfillmentColor(
                                                        detailData.fulfillmentStatus,
                                                    )
                                                "
                                                variant="flat"
                                                class="font-weight-bold"
                                            >
                                                {{
                                                    $t(
                                                        "stock_receipt.fulfillment." +
                                                            detailData.fulfillmentStatus,
                                                    ) ||
                                                    detailData.fulfillmentStatus
                                                }}
                                            </v-chip>
                                        </div>
                                    </v-col>
                                    <v-col
                                        cols="12"
                                        sm="6"
                                        md="4"
                                        class="py-1 mt-sm-2"
                                    >
                                        <div class="d-flex align-center ga-2">
                                            <span
                                                class="text-subtitle-2 text-medium-emphasis"
                                                >{{
                                                    $t("base.created_at") ||
                                                    "Ngày tạo"
                                                }}:
                                            </span>
                                            <span
                                                class="text-body-1 font-weight-medium text-grey-darken-3"
                                                >{{ detailData.createdAt }}
                                            </span>
                                        </div>
                                    </v-col>
                                    <v-col
                                        v-if="detailData.note"
                                        cols="12"
                                        class="py-1 mt-2"
                                    >
                                        <div class="d-flex align-start ga-2">
                                            <span
                                                class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                                >{{
                                                    $t("field.ghi_chu") ||
                                                    "Ghi chú"
                                                }}:
                                            </span>
                                            <span
                                                class="text-body-2 text-grey-darken-2"
                                                >{{ detailData.note }}
                                            </span>
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-card>
                        </v-col>

                        <!-- Danh sách nhà cung cấp và nguyên liệu -->
                        <v-col cols="12" class="mt-4">
                            <v-card
                                v-for="(
                                    providerGroup, providerIndex
                                ) in detailData.providers || []"
                                :key="providerIndex"
                                variant="flat"
                                border
                                class="mb-4 overflow-hidden"
                            >
                                <v-card-title
                                    class="bg-grey-lighten-4 py-3 px-4 d-flex align-center justify-space-between flex-wrap ga-2"
                                >
                                    <div class="d-flex align-center ga-2">
                                        <v-chip
                                            size="small"
                                            color="primary"
                                            variant="flat"
                                        >
                                            {{ providerIndex + 1 }}
                                        </v-chip>
                                        <span
                                            class="text-subtitle-1 font-weight-bold text-grey-darken-3"
                                        >
                                            {{
                                                providerGroup.providerSnapshot
                                                    ?.name || "--"
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        class="text-subtitle-2 text-medium-emphasis"
                                    >
                                        {{ $t("base.status") || "Trạng thái" }}:
                                        {{
                                            $t(
                                                "stock_receipt.provider_status." +
                                                    providerGroup.status,
                                            ) || providerGroup.status
                                        }}
                                    </div>
                                </v-card-title>

                                <v-divider />

                                <!-- Stepper trạng thái nhà cung cấp -->
                                <StatusStepper
                                    :status="providerGroup.status"
                                    :steps="statusSteps"
                                    :loading-value="
                                        updatingProviderId === providerGroup.id
                                            ? targetStatus
                                            : null
                                    "
                                    :colors="statusColors"
                                    @change="
                                        (val) =>
                                            changeProviderStatus(
                                                providerGroup,
                                                val,
                                            )
                                    "
                                />

                                <v-divider />

                                <v-table density="comfortable">
                                    <thead>
                                        <tr class="bg-grey-lighten-5">
                                            <th
                                                class="text-center font-weight-bold py-3"
                                                style="width: 60px"
                                            >
                                                {{ $t("field.stt") || "STT" }}
                                            </th>
                                            <th
                                                class="text-left font-weight-bold py-3"
                                            >
                                                {{
                                                    $t(
                                                        "field.stock_in_merchandise",
                                                    ) ||
                                                    "Nguyên liệu/Thành phẩm"
                                                }}
                                            </th>
                                            <th
                                                class="text-right font-weight-bold py-3"
                                                style="width: 120px"
                                            >
                                                {{
                                                    $t("field.sl_yeu_cau") ||
                                                    "Số lượng yêu cầu"
                                                }}
                                            </th>
                                            <th
                                                class="text-center font-weight-bold py-3"
                                                style="width: 120px"
                                            >
                                                {{
                                                    $t("field.unit") || "Đơn vị"
                                                }}
                                            </th>
                                            <th
                                                class="text-right font-weight-bold py-3"
                                                style="width: 150px"
                                            >
                                                {{
                                                    $t("field.import_price") ||
                                                    "Giá nhập"
                                                }}
                                            </th>
                                            <th
                                                class="text-right font-weight-bold py-3"
                                                style="width: 180px"
                                            >
                                                {{
                                                    $t("field.total_amount") ||
                                                    "Thành tiền"
                                                }}
                                            </th>
                                            <th
                                                class="text-left font-weight-bold py-3"
                                                style="width: 200px"
                                            >
                                                {{
                                                    $t("field.ghi_chu") ||
                                                    "Ghi chú"
                                                }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(
                                                row, itemIndex
                                            ) in providerGroup.items || []"
                                            :key="itemIndex"
                                        >
                                            <td
                                                class="text-center py-2 text-grey-darken-1"
                                            >
                                                {{ itemIndex + 1 }}
                                            </td>
                                            <td
                                                class="py-2 font-weight-medium text-grey-darken-4"
                                            >
                                                {{
                                                    row.merchandiseNameSnapshot
                                                }}
                                            </td>
                                            <td
                                                class="text-right py-2 font-weight-bold text-grey-darken-3"
                                            >
                                                {{
                                                    formatNumber(
                                                        row.expectedQuantity,
                                                    )
                                                }}
                                            </td>
                                            <td class="text-center py-2">
                                                <v-chip
                                                    size="small"
                                                    variant="tonal"
                                                    color="secondary"
                                                >
                                                    {{
                                                        row.expectedUnitLabelSnapshot
                                                    }}
                                                </v-chip>
                                            </td>
                                            <td
                                                class="text-right py-2 text-grey-darken-3"
                                            >
                                                {{
                                                    formatNumber(
                                                        row.unitPriceSnapshot,
                                                    )
                                                }}
                                                <span
                                                    class="text-caption text-medium-emphasis"
                                                    >{{ row.currency }}
                                                </span>
                                            </td>
                                            <td
                                                class="text-right py-2 font-weight-bold text-primary"
                                            >
                                                {{
                                                    formatNumber(
                                                        (Number(
                                                            row.expectedQuantity,
                                                        ) || 0) *
                                                            (Number(
                                                                row.unitPriceSnapshot,
                                                            ) || 0),
                                                    )
                                                }}
                                                <span class="text-caption">{{
                                                    row.currency
                                                }}</span>
                                            </td>
                                            <td
                                                class="py-2 text-body-2 text-medium-emphasis"
                                            >
                                                {{ row.note || "--" }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Dialog kiểm hàng -->
    </div>
</template>

<script>
import { mapActions } from "vuex";
import { functionHelper } from "@/helpers/functionHelper";
import {
    constant,
    STOCK_RECEIPT_PROVIDER_STATUS,
} from "@/utils/constants/constant";
import StatusStepper from "@/components/StatusStepper.vue";

export default {
    name: "DetailStockReceipt",
    components: {
        StatusStepper,
    },
    inheritAttrs: false,
    props: {
        mode: {
            type: String,
            default: "create",
        },
        item: {
            type: Object,
            default: null,
        },
        path: {
            type: String,
            default: "",
        },
    },
    data() {
        return {
            dialog: false,
            detailData: null,
            loadingDetail: false,
            updatingProviderId: null,
            targetStatus: null,
            statusSteps: constant.STOCK_RECEIPT_PROVIDER_STATUS_STEPS,
            inspectionDialog: false,
            inspectingProvider: null,
        };
    },
    computed: {
        statusColors() {
            return constant.STOCK_RECEIPT_STATUS_COLORS;
        },
    },
    watch: {
        async dialog(isOpen) {
            if (isOpen && this.item?.id) {
                this.loadingDetail = true;
                try {
                    this.detailData = await this.fetchItemDetail({
                        id: this.item.id,
                        force: true,
                    });
                } finally {
                    this.loadingDetail = false;
                }
            }
        },
    },
    methods: {
        ...mapActions("stockReceipt", [
            "fetchItemDetail",
            "updateProviderStatus",
        ]),
        async changeProviderStatus(providerGroup, status) {
            this.updatingProviderId = providerGroup.id;
            this.targetStatus = status;
            try {
                await this.updateProviderStatus({
                    id: providerGroup.id,
                    status,
                });
                this.detailData = await this.fetchItemDetail({
                    id: this.item.id,
                    force: true,
                });
                this.$emit("reload");

                if (status === STOCK_RECEIPT_PROVIDER_STATUS.INSPECTING) {
                    this.inspectingProvider =
                        this.detailData?.providers?.find(
                            (provider) => provider.id === providerGroup.id,
                        ) || providerGroup;
                    this.inspectionDialog = true;
                }
            } finally {
                this.updatingProviderId = null;
                this.targetStatus = null;
            }
        },
        async handleInspectionSaved() {
            this.detailData = await this.fetchItemDetail({
                id: this.item.id,
                force: true,
            });
            this.$emit("reload");
        },
        formatNumber(val) {
            if (val == null) return "--";
            return functionHelper.formatNumber(Number(val) || 0);
        },
        getStatusColor(status) {
            return constant.STOCK_RECEIPT_STATUS_COLORS[status] || "primary";
        },
        getFulfillmentColor(status) {
            return constant.FULFILLMENT_STATUS_COLORS[status] || "primary";
        },
    },
};
</script>

<style scoped>
.close-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 1;
}
.border-b {
    border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}
</style>
