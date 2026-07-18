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
                @update:options="onOptions"
            >
                <template #headers="{ columns, isSorted, getSortIcon, toggleSort }">
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
                                class="d-flex align-center justify-space-between py-2"
                            >
                                <template v-if="!col.filterComponent">
                                    <span class="v-data-table-header__content">{{
                                        col.title
                                    }}</span>
                                </template>

                                <component
                                    :is="col.filterComponent"
                                    v-else
                                    :items="col.items"
                                    :title="col.title"
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
                        <v-tooltip
                            v-if="permission?.show"
                            :text="$t('button.detail') || 'Chi tiết'" 
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <DetailStockReceipt
                                    v-bind="tooltipProps"
                                    :path="path"
                                    mode="update"
                                    :item="item"
                                    @reload="$emit('reload', { ...query })"
                                />
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template #[`item.status`]="{ item }">
                    <v-chip
                        :color="getStatusColor(item.status)"
                        size="small"
                        variant="flat"
                    >
                        {{ $t("stock_receipt.status." + item.status) || item.status }}
                    </v-chip>
                </template>

                <template #[`item.fulfillmentStatus`]="{ item }">
                    <v-chip
                        :color="getFulfillmentColor(item.fulfillmentStatus)"
                        size="small"
                        variant="flat"
                    >
                        {{ $t("stock_receipt.fulfillment." + item.fulfillmentStatus) || item.fulfillmentStatus }}
                    </v-chip>
                </template>

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

        <!-- Custom Pagination -->
        <FilterPagination
            :total-items="totalItems"
            :current-page="query.page"
            :items-per-page="query.limit"
            @update:page="onPageChange"
            @update:items-per-page="onLimitChange"
        />
    </div>
</template>

<script>
import { markRaw } from "vue";
import FilterText from "@/components/filters/FilterText.vue";
import FilterSelect from "@/components/filters/FilterSelect.vue";
import FilterDateRange from "@/components/filters/FilterDateRange.vue";
import FilterPagination from "@/components/filters/FilterPagination.vue";
import { useFilterPagination } from "@/hooks/useFilterPagination.js";
import DetailStockReceipt from "./DetailStockReceipt.vue";
import { constant } from "@/utils/constants/constant";
import { mapGetters } from "vuex";

export default {
    name: "StockReceiptList",
    components: {
        FilterPagination,
        DetailStockReceipt,
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

        return {
            query,
            sortArray,
            onOptions,
            onPageChange,
            onLimitChange,
            onFilter,
        };
    },
    data() {
        return {
            headers: [
                {
                    key: "action",
                    width: 80,
                    minWidth: 80,
                    maxWidth: 80,
                    sortable: false,
                },
                {
                    title: this.$t("stock_receipt.columns.id") || "ID",
                    key: "id",
                    width: 80,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("stock_receipt.columns.code") || "Mã phiếu",
                    key: "code",
                    width: 180,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("stock_receipt.columns.title") || "Tiêu đề",
                    key: "title",
                    width: 250,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("stock_receipt.columns.warehouse") || "Kho nhận",
                    key: "warehouseSnapshot",
                    width: 200,
                    filterComponent: markRaw(FilterText),
                    value: (item) => item.warehouseSnapshot?.name || "",
                },
                {
                    title: this.$t("base.status") || "Trạng thái",
                    key: "status",
                    width: 150,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        { title: this.$t("stock_receipt.status.CREATED") || "CREATED", value: "CREATED" },
                        { title: this.$t("stock_receipt.status.IN_PROGRESS") || "IN_PROGRESS", value: "IN_PROGRESS" },
                        { title: this.$t("stock_receipt.status.PARTIALLY_COMPLETED") || "PARTIALLY_COMPLETED", value: "PARTIALLY_COMPLETED" },
                        { title: this.$t("stock_receipt.status.COMPLETED") || "COMPLETED", value: "COMPLETED" },
                        { title: this.$t("stock_receipt.status.CANCELLED") || "CANCELLED", value: "CANCELLED" },
                    ],
                },
                {
                    title: this.$t("stock_receipt.columns.fulfillment_status") || "Đáp ứng",
                    key: "fulfillmentStatus",
                    width: 180,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        { title: this.$t("stock_receipt.fulfillment.PENDING") || "PENDING", value: "PENDING" },
                        { title: this.$t("stock_receipt.fulfillment.FULL") || "FULL", value: "FULL" },
                        { title: this.$t("stock_receipt.fulfillment.PARTIAL_CLOSED") || "PARTIAL_CLOSED", value: "PARTIAL_CLOSED" },
                        { title: this.$t("stock_receipt.fulfillment.BACKORDER_OPEN") || "BACKORDER_OPEN", value: "BACKORDER_OPEN" },
                    ],
                },
                {
                    title: this.$t("base.created_at") || "Ngày tạo",
                    key: "createdAt",
                    width: 150,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("base.updated_at") || "Ngày cập nhật",
                    key: "updatedAt",
                    width: 150,
                    filterComponent: markRaw(FilterDateRange),
                },
            ],
        };
    },
    computed: {
        ...mapGetters("stockReceipt", ["items", "loading", "totalItems"]),
        tableMinWidth() {
            return this.headers.reduce((total, col) => {
                return total + (col.width || col.minWidth || 0);
            }, 0);
        },
    },
    methods: {
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
.table-scroll-container {
    overflow-x: auto;
    overflow-y: hidden;
    width: 100%;
}

.table-scroll-container :deep(.v-data-table),
.table-scroll-container :deep(table) {
    min-width: var(--table-min-width, 600px);
}
</style>
