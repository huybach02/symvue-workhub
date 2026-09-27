<template>
    <div class="table-wrapper">
        <div
            class="table-scroll-container"
            :style="{ '--table-min-width': tableMinWidth + 'px' }"
        >
            <v-data-table
                :items="items"
                :headers="headers"
                :sort-by="sortArray"
                :items-per-page="-1"
                hide-default-footer
                :loading="loading"
                :row-props="getRowProps"
                @update:options="onOptions"
            >
                <template
                    #headers="{ columns, isSorted, getSortIcon, toggleSort }"
                >
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :style="{
                                width: col.width ? col.width + 'px' : undefined,
                                minWidth: col.minWidth
                                    ? col.minWidth + 'px'
                                    : undefined,
                                maxWidth: col.maxWidth
                                    ? col.maxWidth + 'px'
                                    : undefined,
                            }"
                            class="v-data-table-header__th v-data-table-header-sticky"
                        >
                            <div
                                class="d-flex align-center justify-space-between py-2 ga-1"
                            >
                                <template v-if="!col.filterComponent">
                                    <span
                                        class="v-data-table-header__content font-weight-bold"
                                    >
                                        {{ col.title }}
                                    </span>
                                </template>

                                <component
                                    :is="col.filterComponent"
                                    v-else
                                    :items="col.items"
                                    :title="col.title"
                                    :path="col.path"
                                    class="flex-grow-1"
                                    @update="(val) => onFilter(col.key, val)"
                                />

                                <v-icon
                                    v-if="col.sortable !== false"
                                    :icon="getSortIcon(col)"
                                    :class="{
                                        'v-data-table-header__sort-icon': true,
                                        'v-data-table-header__sort-icon--active':
                                            isSorted(col),
                                    }"
                                    size="small"
                                    @click="() => toggleSort(col)"
                                />
                            </div>
                        </th>
                    </tr>
                </template>

                <template #[`item.action`]="{ item }">
                    <div class="d-flex align-center justify-center">
                        <v-tooltip :text="$t('button.detail')" location="top">
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="outlined"
                                    color="primary"
                                    @click="openDetailDialog(item)"
                                >
                                    <v-icon size="small">mdi-eye</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template #[`item.code`]="{ item }">
                    <v-btn
                        variant="text"
                        size="small"
                        color="primary"
                        class="font-weight-bold px-0 text-none"
                        @click="openDetailDialog(item)"
                    >
                        <v-icon size="small" start>
                            mdi-receipt-text-outline
                        </v-icon>
                        {{ item.code || "--" }}
                    </v-btn>
                </template>

                <template #[`item.orderDate`]="{ item }">
                    <span class="text-body-2 font-weight-medium">
                        {{ formatDateTime(item.orderDate || item.createdAt) }}
                    </span>
                </template>

                <template #[`item.diningTableId`]="{ item }">
                    <v-chip
                        v-if="item.diningTable"
                        size="small"
                        color="info"
                        variant="tonal"
                        class="font-weight-medium"
                    >
                        {{
                            $t("sell_product.table_label", {
                                number: item.diningTable.tableNumber,
                            })
                        }}
                    </v-chip>
                    <span v-else class="text-body-2 text-medium-emphasis">
                        {{ $t("sale_order.table_takeaway") }}
                    </span>
                </template>

                <template #[`item.branchId`]="{ item }">
                    <span class="text-body-2">
                        {{ item.branch?.name || "--" }}
                    </span>
                </template>

                <template #[`item.cashierId`]="{ item }">
                    <span class="text-body-2">
                        {{ item.cashier?.name || "--" }}
                    </span>
                </template>

                <template #[`item.itemsCount`]="{ item }">
                    <v-chip
                        size="small"
                        variant="tonal"
                        color="primary"
                        class="font-weight-medium"
                    >
                        {{
                            $t("sell_product.items_count", {
                                count: item.items?.length || 0,
                            })
                        }}
                    </v-chip>
                </template>

                <template #[`item.totalAmount`]="{ item }">
                    <span class="text-body-2 font-weight-bold text-primary">
                        {{ formatNumber(item.totalAmount) }}
                        {{ $t("sale_order.currency_symbol") }}
                    </span>
                </template>

                <template #[`item.paymentMethod`]="{ item }">
                    <span class="text-body-2">
                        {{ getPaymentMethodLabel(item.paymentMethod) }}
                    </span>
                </template>

                <template #[`item.paymentStatus`]="{ item }">
                    <v-chip
                        :color="getPaymentStatusColor(item.paymentStatus)"
                        size="small"
                        variant="flat"
                        class="font-weight-medium"
                    >
                        {{ getPaymentStatusLabel(item.paymentStatus) }}
                    </v-chip>
                </template>

                <template #[`item.status`]="{ item }">
                    <v-chip
                        :color="getStatusColor(item.status)"
                        size="small"
                        variant="tonal"
                        class="font-weight-medium"
                    >
                        {{ getStatusLabel(item.status) }}
                    </v-chip>
                </template>

                <template #[`item.note`]="{ item }">
                    <v-tooltip
                        v-if="item.note"
                        :text="item.note"
                        location="top"
                    >
                        <template #activator="{ props: tooltipProps }">
                            <span
                                v-bind="tooltipProps"
                                class="text-body-2 text-truncate d-inline-block"
                                style="max-width: 160px"
                            >
                                {{ item.note }}
                            </span>
                        </template>
                    </v-tooltip>
                    <span v-else class="text-body-2 text-medium-emphasis">
                        --
                    </span>
                </template>

                <!-- Khi không có dữ liệu -->
                <template #no-data>
                    <div class="pa-8 text-center">
                        <v-icon
                            icon="mdi-database-off-outline"
                            size="large"
                            color="grey-lighten-1"
                        />
                        <div class="text-grey-darken-1 mt-2">
                            {{ $t("base.no_data") }}
                        </div>
                    </div>
                </template>
            </v-data-table>
        </div>

        <FilterPagination
            :total-items="totalItems"
            :current-page="query.page"
            :items-per-page="query.limit"
            @update:page="onPageChange"
            @update:items-per-page="onLimitChange"
        />

        <DetailSaleOrderDialog
            v-model="showDetailDialog"
            :order-id="selectedOrderId"
            :order-data="selectedOrder"
            @reload="emitReload"
        />
    </div>
</template>

<script>
import { markRaw } from "vue";
import FilterText from "@/components/filters/FilterText.vue";
import FilterSelect from "@/components/filters/FilterSelect.vue";
import FilterDateRange from "@/components/filters/FilterDateRange.vue";
import FilterAutoComplete from "@/components/filters/FilterAutoComplete.vue";
import FilterPagination from "@/components/filters/FilterPagination.vue";
import { useFilterPagination } from "@/hooks/useFilterPagination.js";
import DetailSaleOrderDialog from "./DetailSaleOrderDialog.vue";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { functionHelper } from "@/helpers/functionHelper";
import { mapGetters } from "vuex";
import FilterPlaceholder from "@/components/filters/FilterPlaceholder.vue";

export default {
    name: "SaleOrderList",
    components: {
        FilterPagination,
        DetailSaleOrderDialog,
    },
    props: {
        path: {
            type: String,
            default: "",
        },
        permission: {
            type: Object,
            default: () => ({}),
        },
    },
    emits: ["reload"],
    setup(props, { emit }) {
        const {
            query,
            sortArray,
            onOptions,
            onPageChange,
            onLimitChange,
            onFilter,
        } = useFilterPagination((queryData) => {
            emit("reload", { ...queryData });
        });

        const emitReload = () => {
            emit("reload", { ...query.value });
        };

        return {
            query,
            sortArray,
            onOptions,
            onPageChange,
            onLimitChange,
            onFilter,
            emitReload,
        };
    },
    data() {
        return {
            highlightedOrderIds: [],
            highlightTimeouts: [],
            showDetailDialog: false,
            selectedOrderId: null,
            selectedOrder: null,
            headers: [
                {
                    key: "action",
                    width: 70,
                    minWidth: 70,
                    maxWidth: 70,
                    sortable: false,
                },
                {
                    title: this.$t("sale_order.columns.code"),
                    key: "code",
                    width: 170,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("sale_order.columns.order_date"),
                    key: "orderDate",
                    width: 160,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("sale_order.columns.dining_table"),
                    key: "diningTableId",
                    width: 140,
                    filterComponent: markRaw(FilterAutoComplete),
                    path: API_ROUTES_CONFIG.diningTable,
                    sortable: false,
                },
                {
                    title: this.$t("sale_order.columns.branch"),
                    key: "branchId",
                    width: 160,
                    filterComponent: markRaw(FilterAutoComplete),
                    path: API_ROUTES_CONFIG.branch,
                    sortable: false,
                },
                {
                    title: this.$t("sale_order.columns.items_count"),
                    key: "itemsCount",
                    width: 100,
                    sortable: false,
                    filterComponent: markRaw(FilterPlaceholder),
                },
                {
                    title: this.$t("sale_order.columns.total_amount"),
                    key: "totalAmount",
                    width: 180,
                    sortable: false,
                    filterComponent: markRaw(FilterPlaceholder),
                },
                {
                    title: this.$t("sale_order.columns.payment_status"),
                    key: "paymentStatus",
                    width: 200,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t(
                                "sale_order.payment_status_values.unpaid",
                            ),
                            value: "0",
                        },
                        {
                            title: this.$t(
                                "sale_order.payment_status_values.paid",
                            ),
                            value: "1",
                        },
                        {
                            title: this.$t(
                                "sale_order.payment_status_values.refunded",
                            ),
                            value: "2",
                        },
                    ],
                },
                {
                    title: this.$t("sale_order.columns.status"),
                    key: "status",
                    width: 200,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t("sale_order.status_values.CREATED"),
                            value: "CREATED",
                        },
                        {
                            title: this.$t(
                                "sale_order.status_values.PROCESSING",
                            ),
                            value: "PROCESSING",
                        },
                        {
                            title: this.$t("sale_order.status_values.SHIPPED"),
                            value: "SHIPPED",
                        },
                        {
                            title: this.$t(
                                "sale_order.status_values.COMPLETED",
                            ),
                            value: "COMPLETED",
                        },
                    ],
                },
                {
                    title: this.$t("sale_order.columns.payment_method"),
                    key: "paymentMethod",
                    width: 230,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t(
                                "sale_order.payment_method_values.CASH",
                            ),
                            value: "CASH",
                        },
                        {
                            title: this.$t(
                                "sale_order.payment_method_values.TRANSFER",
                            ),
                            value: "TRANSFER",
                        },
                        {
                            title: this.$t(
                                "sale_order.payment_method_values.CARD",
                            ),
                            value: "CARD",
                        },
                        {
                            title: this.$t(
                                "sale_order.payment_method_values.OTHER",
                            ),
                            value: "OTHER",
                        },
                    ],
                },
                {
                    title: this.$t("sale_order.columns.cashier"),
                    key: "cashierId",
                    width: 150,
                    filterComponent: markRaw(FilterAutoComplete),
                    path: API_ROUTES_CONFIG.users,
                    sortable: false,
                },
                {
                    title: this.$t("sale_order.columns.note"),
                    key: "note",
                    width: 180,
                    filterComponent: markRaw(FilterText),
                    sortable: false,
                },
            ],
        };
    },
    computed: {
        ...mapGetters("saleOrder", ["items", "loading", "totalItems"]),
        tableMinWidth() {
            return this.headers.reduce((total, col) => {
                return total + (col.width || col.minWidth || 0);
            }, 0);
        },
    },
    mounted() {
        window.addEventListener(
            "sale_order:created",
            this.handleNewSaleOrder,
        );
    },
    beforeUnmount() {
        window.removeEventListener(
            "sale_order:created",
            this.handleNewSaleOrder,
        );
        this.highlightTimeouts.forEach((timer) => clearTimeout(timer));
    },
    methods: {
        handleNewSaleOrder(event) {
            const orderId = event?.detail?.saleOrder?.id;
            if (!orderId) return;

            const numId = Number(orderId);
            if (!this.highlightedOrderIds.includes(numId)) {
                this.highlightedOrderIds = [...this.highlightedOrderIds, numId];
            }

            const timer = setTimeout(() => {
                this.removeHighlightedOrder(numId);
            }, 30000);

            this.highlightTimeouts.push(timer);
        },
        removeHighlightedOrder(orderId) {
            const numId = Number(orderId);
            if (this.highlightedOrderIds.includes(numId)) {
                this.highlightedOrderIds = this.highlightedOrderIds.filter(
                    (id) => id !== numId,
                );
            }
        },
        getRowProps({ item }) {
            const id = Number(item?.id ?? item?.raw?.id);
            if (this.highlightedOrderIds.includes(id)) {
                return { class: "order-row-new-blink" };
            }
            return {};
        },
        openDetailDialog(item) {
            this.selectedOrderId = item.id;
            this.selectedOrder = item;
            this.showDetailDialog = true;
            this.removeHighlightedOrder(item.id);
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
            return (
                this.$t(`sale_order.status_values.${status}`) || status || "--"
            );
        },
        getPaymentStatusColor(paymentStatus) {
            switch (Number(paymentStatus)) {
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
            switch (Number(paymentStatus)) {
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
    },
};
</script>

<style scoped>
.table-scroll-container {
    overflow-x: auto;
    overflow-y: hidden;
    width: 100%;
}

.table-scroll-container :deep(.v-data-table),
.table-scroll-container :deep(table) {
    min-width: var(--table-min-width, 600px);
}

.table-scroll-container :deep(.order-row-new-blink td) {
    animation: blink-row-subtle 2s ease-in-out infinite alternate;
    border-top: 1px solid rgba(16, 185, 129, 0.35) !important;
    border-bottom: 1px solid rgba(16, 185, 129, 0.35) !important;
    background-color: rgba(16, 185, 129, 0.06) !important;
    transition: all 0.3s ease;
}

.table-scroll-container :deep(.order-row-new-blink td:first-child) {
    border-left: 3px solid #10b981 !important;
}

@keyframes blink-row-subtle {
    0% {
        border-top-color: rgba(16, 185, 129, 0.6) !important;
        border-bottom-color: rgba(16, 185, 129, 0.6) !important;
        background-color: rgba(16, 185, 129, 0.1) !important;
    }
    100% {
        border-top-color: rgba(16, 185, 129, 0.12) !important;
        border-bottom-color: rgba(16, 185, 129, 0.12) !important;
        background-color: rgba(16, 185, 129, 0.01) !important;
    }
}
</style>
