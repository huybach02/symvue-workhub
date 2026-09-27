<template>
    <v-dialog v-model="dialog" max-width="1400" scrollable>
        <v-card class="rounded-lg">
            <v-card-title
                class="d-flex align-center justify-space-between py-3 px-4 bg-grey-lighten-4 border-b"
            >
                <div class="d-flex align-center ga-2 flex-wrap">
                    <v-icon icon="mdi-receipt-text-outline" color="primary" />
                    <span class="text-h6 font-weight-bold text-grey-darken-3">
                        {{ $t("sale_order.detail_title") }}
                    </span>
                    <span
                        v-if="currentOrder.code"
                        class="text-subtitle-1 font-weight-bold text-primary"
                    >
                        #{{ currentOrder.code }}
                    </span>
                    <v-chip
                        v-if="currentOrder.status"
                        :color="getStatusColor(currentOrder.status)"
                        size="small"
                        variant="tonal"
                        class="font-weight-medium"
                    >
                        {{ getStatusLabel(currentOrder.status) }}
                    </v-chip>
                    <v-chip
                        v-if="
                            currentOrder.paymentStatus !== undefined &&
                            currentOrder.paymentStatus !== null
                        "
                        :color="
                            getPaymentStatusColor(currentOrder.paymentStatus)
                        "
                        size="small"
                        variant="flat"
                    >
                        {{ getPaymentStatusLabel(currentOrder.paymentStatus) }}
                    </v-chip>
                </div>
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    @click="close"
                />
            </v-card-title>

            <v-divider />

            <!-- Stepper trạng thái đơn hàng -->
            <StatusStepper
                v-if="currentOrder && currentOrder.status"
                :status="currentOrder.status"
                :steps="statusSteps"
                :loading-value="updatingStatus"
                @change="handleStepChange"
            />

            <v-divider />

            <v-card-text class="pa-4">
                <div
                    v-if="loading"
                    class="d-flex justify-center align-center py-12"
                >
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        size="40"
                    />
                </div>

                <div v-else>
                    <!-- Thông tin chung đơn hàng -->
                    <v-card variant="flat" border class="pa-4 mb-4 rounded-lg">
                        <div
                            class="text-subtitle-2 font-weight-bold text-primary mb-3 d-flex align-center ga-1"
                        >
                            <v-icon
                                icon="mdi-information-outline"
                                size="small"
                            />
                            <span>{{ $t("sale_order.general_info") }}</span>
                        </div>
                        <v-row dense>
                            <v-col cols="12" sm="6" md="4" class="py-1">
                                <div class="d-flex align-start ga-2">
                                    <span
                                        class="text-caption text-medium-emphasis flex-shrink-0"
                                    >
                                        {{ $t("sale_order.columns.code") }}:
                                    </span>
                                    <span
                                        class="text-body-2 font-weight-bold text-primary"
                                    >
                                        {{ currentOrder.code || "--" }}
                                    </span>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="6" md="4" class="py-1">
                                <div class="d-flex align-start ga-2">
                                    <span
                                        class="text-caption text-medium-emphasis flex-shrink-0"
                                    >
                                        {{
                                            $t("sale_order.columns.order_date")
                                        }}:
                                    </span>
                                    <span
                                        class="text-body-2 font-weight-medium"
                                    >
                                        {{
                                            formatDateTime(
                                                currentOrder.orderDate ||
                                                    currentOrder.createdAt,
                                            )
                                        }}
                                    </span>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="6" md="4" class="py-1">
                                <div class="d-flex align-start ga-2">
                                    <span
                                        class="text-caption text-medium-emphasis flex-shrink-0"
                                    >
                                        {{
                                            $t(
                                                "sale_order.columns.dining_table",
                                            )
                                        }}:
                                    </span>
                                    <v-chip
                                        v-if="currentOrder.diningTable"
                                        size="x-small"
                                        color="info"
                                        variant="tonal"
                                        class="font-weight-medium"
                                    >
                                        {{
                                            $t("sell_product.table_label", {
                                                number: currentOrder.diningTable
                                                    .tableNumber,
                                            })
                                        }}
                                    </v-chip>
                                    <span
                                        v-else
                                        class="text-body-2 text-medium-emphasis"
                                    >
                                        {{ $t("sale_order.table_takeaway") }}
                                    </span>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="6" md="4" class="py-1">
                                <div class="d-flex align-start ga-2">
                                    <span
                                        class="text-caption text-medium-emphasis flex-shrink-0"
                                    >
                                        {{ $t("sale_order.columns.branch") }}:
                                    </span>
                                    <span
                                        class="text-body-2 font-weight-medium"
                                    >
                                        {{ currentOrder.branch?.name || "--" }}
                                    </span>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="6" md="4" class="py-1">
                                <div class="d-flex align-start ga-2">
                                    <span
                                        class="text-caption text-medium-emphasis flex-shrink-0"
                                    >
                                        {{ $t("sale_order.columns.cashier") }}:
                                    </span>
                                    <span
                                        class="text-body-2 font-weight-medium"
                                    >
                                        {{ currentOrder.cashier?.name || "--" }}
                                    </span>
                                </div>
                            </v-col>

                            <v-col cols="12" sm="6" md="4" class="py-1">
                                <div class="d-flex align-start ga-2">
                                    <span
                                        class="text-caption text-medium-emphasis flex-shrink-0"
                                    >
                                        {{
                                            $t(
                                                "sale_order.columns.payment_method",
                                            )
                                        }}:
                                    </span>
                                    <span
                                        class="text-body-2 font-weight-medium"
                                    >
                                        {{
                                            getPaymentMethodLabel(
                                                currentOrder.paymentMethod,
                                            )
                                        }}
                                    </span>
                                </div>
                            </v-col>

                            <v-col cols="12" class="py-1">
                                <div class="d-flex align-start ga-2">
                                    <span
                                        class="text-caption text-medium-emphasis flex-shrink-0"
                                    >
                                        {{ $t("sale_order.columns.note") }}:
                                    </span>
                                    <span
                                        class="text-body-2 font-italic text-grey-darken-1"
                                    >
                                        {{ currentOrder.note || "--" }}
                                    </span>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card>

                    <!-- Danh sách món đặt -->
                    <v-card variant="flat" border class="mb-4 rounded-lg">
                        <div
                            class="d-flex align-center justify-space-between py-3 px-4 border-b bg-grey-lighten-5"
                        >
                            <div class="d-flex align-center ga-2">
                                <v-icon
                                    icon="mdi-silverware-fork-knife"
                                    size="small"
                                    color="primary"
                                />
                                <span
                                    class="text-subtitle-2 font-weight-bold text-grey-darken-3"
                                >
                                    {{ $t("sale_order.items.title") }}
                                </span>
                            </div>
                            <v-chip
                                size="x-small"
                                color="primary"
                                variant="flat"
                            >
                                {{
                                    $t("sell_product.items_count", {
                                        count: items.length,
                                    })
                                }}
                            </v-chip>
                        </div>

                        <v-table density="compact" hover>
                            <thead>
                                <tr>
                                    <th
                                        class="text-center font-weight-bold"
                                        style="width: 50px"
                                    >
                                        {{ $t("sale_order.items.index") }}
                                    </th>
                                    <th class="text-left font-weight-bold">
                                        {{
                                            $t("sale_order.items.product_name")
                                        }}
                                    </th>
                                    <th class="text-left font-weight-bold">
                                        {{
                                            $t("sale_order.items.variant_name")
                                        }}
                                    </th>
                                    <th class="text-left font-weight-bold">
                                        {{ $t("sale_order.items.note") }}
                                    </th>
                                    <th
                                        class="text-end font-weight-bold"
                                        style="width: 200px"
                                    >
                                        {{ $t("sale_order.items.unit_price") }}
                                    </th>
                                    <th
                                        class="text-center font-weight-bold"
                                        style="width: 130px"
                                    >
                                        {{ $t("sale_order.items.quantity") }}
                                    </th>
                                    <th
                                        class="text-end font-weight-bold"
                                        style="width: 140px"
                                    >
                                        {{ $t("sale_order.items.subtotal") }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="items.length === 0">
                                    <td
                                        colspan="7"
                                        class="text-center py-6 text-medium-emphasis"
                                    >
                                        {{ $t("sale_order.items.empty") }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="(item, idx) in items"
                                    :key="item.id || idx"
                                >
                                    <td
                                        class="text-center text-caption text-medium-emphasis"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td>
                                        <div
                                            class="font-weight-medium text-body-2"
                                        >
                                            {{ getItemProductName(item) }}
                                        </div>
                                    </td>
                                    <td>
                                        <v-chip
                                            v-if="getItemVariantName(item)"
                                            size="x-small"
                                            variant="tonal"
                                            color="secondary"
                                        >
                                            {{ getItemVariantName(item) }}
                                        </v-chip>
                                        <span
                                            v-else
                                            class="text-caption text-medium-emphasis"
                                        >
                                            --
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            v-if="item.note"
                                            class="text-caption font-italic text-medium-emphasis"
                                        >
                                            {{ item.note }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-caption text-medium-emphasis"
                                        >
                                            --
                                        </span>
                                    </td>
                                    <td class="text-end text-body-2">
                                        {{ formatNumber(item.unitPrice) }}
                                        {{ $t("sale_order.currency_symbol") }}
                                    </td>
                                    <td
                                        class="text-center font-weight-bold text-body-2"
                                    >
                                        {{ formatNumber(item.quantity) }}
                                    </td>
                                    <td
                                        class="text-end font-weight-bold text-body-2 text-primary"
                                    >
                                        {{ formatNumber(item.subtotal) }}
                                        {{ $t("sale_order.currency_symbol") }}
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card>

                    <!-- Tổng kết thanh toán -->
                    <v-row justify="end">
                        <v-col cols="12" md="6" lg="5">
                            <v-card
                                variant="flat"
                                border
                                class="pa-4 rounded-lg bg-grey-lighten-5"
                            >
                                <div
                                    class="text-subtitle-2 font-weight-bold text-grey-darken-3 mb-2"
                                >
                                    {{ $t("sale_order.summary.title") }}
                                </div>

                                <div class="d-flex justify-space-between py-1">
                                    <span
                                        class="text-body-2 text-medium-emphasis"
                                    >
                                        {{ $t("sale_order.summary.subtotal") }}
                                    </span>
                                    <span
                                        class="text-body-2 font-weight-medium"
                                    >
                                        {{
                                            formatNumber(currentOrder.subtotal)
                                        }}
                                        {{ $t("sale_order.currency_symbol") }}
                                    </span>
                                </div>

                                <div
                                    v-if="
                                        parseFloat(
                                            currentOrder.discountAmount,
                                        ) > 0
                                    "
                                    class="d-flex justify-space-between py-1"
                                >
                                    <span
                                        class="text-body-2 text-medium-emphasis"
                                    >
                                        {{ $t("sale_order.summary.discount") }}
                                    </span>
                                    <span
                                        class="text-body-2 text-error font-weight-medium"
                                    >
                                        -{{
                                            formatNumber(
                                                currentOrder.discountAmount,
                                            )
                                        }}
                                        {{ $t("sale_order.currency_symbol") }}
                                    </span>
                                </div>

                                <div
                                    v-if="
                                        parseFloat(currentOrder.taxAmount) > 0
                                    "
                                    class="d-flex justify-space-between py-1"
                                >
                                    <span
                                        class="text-body-2 text-medium-emphasis"
                                    >
                                        {{ $t("sale_order.summary.tax") }}
                                    </span>
                                    <span
                                        class="text-body-2 font-weight-medium"
                                    >
                                        +{{
                                            formatNumber(currentOrder.taxAmount)
                                        }}
                                        {{ $t("sale_order.currency_symbol") }}
                                    </span>
                                </div>

                                <v-divider class="my-2" />

                                <div
                                    class="d-flex justify-space-between align-center py-1"
                                >
                                    <span
                                        class="text-subtitle-1 font-weight-bold text-grey-darken-4"
                                    >
                                        {{ $t("sale_order.summary.total") }}
                                    </span>
                                    <span
                                        class="text-h6 font-weight-bold text-primary"
                                    >
                                        {{
                                            formatNumber(
                                                currentOrder.totalAmount,
                                            )
                                        }}
                                        {{ $t("sale_order.currency_symbol") }}
                                    </span>
                                </div>
                            </v-card>
                        </v-col>
                    </v-row>
                </div>
            </v-card-text>
        </v-card>

        <ConfirmDialog
            v-model="confirmPaymentDialog"
            :title="$t('sale_order.confirm_payment_title') || 'Xác nhận thanh toán'"
            :message="
                $t('sale_order.confirm_payment_message', {
                    code: currentOrder.code || '',
                }) || 'Xác nhận thanh toán và hoàn tất đơn hàng?'
            "
            icon="mdi-cash-check"
            icon-color="white"
            header-color="primary"
            :confirm-text="$t('button.confirm') || 'Xác nhận'"
            confirm-color="primary"
            :cancel-text="$t('button.cancel') || 'Hủy'"
            :loading="confirmPaymentLoading"
            @confirm="handleConfirmPayment"
            @cancel="confirmPaymentDialog = false"
        />
    </v-dialog>
</template>

<script>
import { functionHelper } from "@/helpers/functionHelper";
import { mapActions } from "vuex";
import StatusStepper from "@/components/StatusStepper.vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

export default {
    name: "DetailSaleOrderDialog",
    components: {
        StatusStepper,
        ConfirmDialog,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        orderId: {
            type: [Number, String],
            default: null,
        },
        orderData: {
            type: Object,
            default: null,
        },
    },
    emits: ["update:modelValue", "reload"],
    data() {
        return {
            loading: false,
            detail: null,
            updatingStatus: null,
            confirmPaymentDialog: false,
            confirmPaymentLoading: false,
            statusSteps: [
                { key: "sale_order.status_values.CREATED", value: "CREATED" },
                {
                    key: "sale_order.status_values.PROCESSING",
                    value: "PROCESSING",
                },
                { key: "sale_order.status_values.SHIPPED", value: "SHIPPED" },
                { key: "sale_order.status_values.PAYMENT", value: "PAYMENT" },
                {
                    key: "sale_order.status_values.COMPLETED",
                    value: "COMPLETED",
                },
            ],
        };
    },
    computed: {
        dialog: {
            get() {
                return this.modelValue;
            },
            set(val) {
                this.$emit("update:modelValue", val);
            },
        },
        currentOrder() {
            return this.detail || this.orderData || {};
        },
        items() {
            return this.currentOrder?.items || [];
        },
    },
    watch: {
        modelValue(newVal) {
            if (newVal && this.orderId) {
                this.loadDetail(this.orderId);
            } else if (!newVal) {
                this.detail = null;
            }
        },
        orderId(newId) {
            if (newId && this.dialog) {
                this.loadDetail(newId);
            }
        },
    },
    mounted() {
        window.addEventListener(
            "sale_order:status_updated",
            this.handleRealtimeStatusUpdate,
        );
    },
    beforeUnmount() {
        window.removeEventListener(
            "sale_order:status_updated",
            this.handleRealtimeStatusUpdate,
        );
    },
    methods: {
        ...mapActions("saleOrder", ["fetchItemDetail", "updateStatus"]),
        handleRealtimeStatusUpdate(event) {
            const updatedOrder = event?.detail?.saleOrder;
            if (
                updatedOrder &&
                Number(updatedOrder.id) === Number(this.currentOrder?.id)
            ) {
                this.detail = { ...this.currentOrder, ...updatedOrder };
            }
        },
        async handleStepChange(targetStep) {
            if (targetStep === "PAYMENT") {
                this.confirmPaymentDialog = true;
                return;
            }

            await this.executeStatusChange(targetStep);
        },
        async executeStatusChange(status, paymentStatus = null) {
            this.updatingStatus = status;
            try {
                const res = await this.updateStatus({
                    id: this.currentOrder.id,
                    status,
                    paymentStatus,
                });
                if (res) {
                    this.detail = res;
                    this.$emit("reload");
                }
            } finally {
                this.updatingStatus = null;
            }
        },
        async handleConfirmPayment() {
            this.confirmPaymentLoading = true;
            try {
                await this.executeStatusChange("COMPLETED", 1);
                this.confirmPaymentDialog = false;
            } finally {
                this.confirmPaymentLoading = false;
            }
        },
        async loadDetail(id) {
            this.loading = true;
            const res = await this.fetchItemDetail({ id, force: true });
            if (res) {
                this.detail = res;
            }
            this.loading = false;
        },
        getItemProductName(item) {
            return (
                item.productSnapshot?.name ||
                item.businessProduct?.name ||
                item.productName ||
                "--"
            );
        },
        getItemVariantName(item) {
            return (
                item.variantSnapshot?.name ||
                item.variant?.name ||
                item.variantName ||
                ""
            );
        },
        getStatusColor(status) {
            switch (status) {
                case "CREATED":
                    return "info";
                case "PROCESSING":
                    return "warning";
                case "SHIPPED":
                    return "primary";
                case "COMPLETED":
                    return "success";
                default:
                    return "grey";
            }
        },
        getStatusLabel(status) {
            return this.$t(`sale_order.status_values.${status}`) || status;
        },
        getPaymentStatusColor(paymentStatus) {
            switch (paymentStatus) {
                case 1:
                    return "success";
                case 2:
                    return "grey";
                case 0:
                default:
                    return "error";
            }
        },
        getPaymentStatusLabel(paymentStatus) {
            switch (paymentStatus) {
                case 1:
                    return this.$t("sale_order.payment_status_values.paid");
                case 2:
                    return this.$t("sale_order.payment_status_values.refunded");
                case 0:
                default:
                    return this.$t("sale_order.payment_status_values.unpaid");
            }
        },
        getPaymentMethodLabel(method) {
            if (!method) return "--";
            const upper = String(method).toUpperCase();
            const key = `sale_order.payment_method_values.${upper}`;
            return this.$te(key) ? this.$t(key) : method;
        },
        formatNumber(value) {
            return functionHelper.formatNumber(parseFloat(value) || 0);
        },
        formatDateTime(dateString) {
            return (
                functionHelper.formatDate(dateString, "DD/MM/YYYY HH:mm") ||
                "--"
            );
        },
        close() {
            this.dialog = false;
        },
    },
};
</script>
