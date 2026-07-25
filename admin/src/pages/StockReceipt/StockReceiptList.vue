<template>
    <div class="table-wrapper">
        <div
            class="table-scroll-container"
            :style="{ '--table-min-width': tableMinWidth + 'px' }"
        >
            <v-data-table-server
                :items="visibleItems"
                :items-length="totalItems"
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

                <template #[`item.code`]="{ item }">
                    <div
                        class="receipt-tree-cell"
                        :style="{
                            paddingLeft: `${item._treeDepth * 28}px`,
                        }"
                    >
                        <v-progress-circular
                            v-if="isChildLoading(item.id)"
                            indeterminate
                            size="16"
                            width="2"
                            color="primary"
                            class="mr-3 ml-1"
                        />

                        <v-btn
                            v-else-if="item._hasChildren"
                            :icon="
                                item._expanded
                                    ? 'mdi-chevron-down'
                                    : 'mdi-chevron-right'
                            "
                            variant="text"
                            class="tree-toggle mr-1"
                            @click.stop="toggleReceipt(item)"
                        />

                        <span v-else class="tree-toggle-placeholder" />

                        <v-icon
                            :icon="
                                item._treeDepth === 0
                                    ? 'mdi-file-document-outline'
                                    : 'mdi-file-tree-outline'
                            "
                            size="18"
                            :color="
                                item._treeDepth === 0
                                    ? 'primary'
                                    : 'grey-darken-1'
                            "
                            class="mr-2"
                        />

                        <span
                            :class="{
                                'font-weight-medium': item._treeDepth === 0,
                            }"
                        >
                            {{ item.code }}
                        </span>
                    </div>
                </template>

                <template #[`item.action`]="{ item }">
                    <div class="d-flex align-center justify-center ga-1">
                        <v-tooltip
                            v-if="permission?.show"
                            :text="$t('button.detail') || 'Chi tiết'" 
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="outlined"
                                    color="primary"
                                    @click="openDetail(item)"
                                >
                                    <v-icon>mdi-eye</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip
                            :text="
                                $t(
                                    'stock_receipt.inspection_history.tooltip',
                                ) || 'Lịch sử kiểm hàng'
                            "
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="outlined"
                                    color="warning"
                                    @click="openInspectionHistory(item)"
                                >
                                    <v-icon>mdi-clipboard-check-outline</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip
                            :text="
                                $t('stock_receipt.event.tooltip') ||
                                'Lịch sử thao tác'
                            "
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="outlined"
                                    color="info"
                                    @click="openEventHistory(item)"
                                >
                                    <v-icon>mdi-history</v-icon>
                                </v-btn>
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
            </v-data-table-server>
        </div>

        <!-- Custom Pagination -->
        <FilterPagination
            :total-items="totalItems"
            :current-page="query.page"
            :items-per-page="query.limit"
            @update:page="onPageChange"
            @update:items-per-page="onLimitChange"
        />

        <DetailStockReceipt
            v-if="selectedDetailItem"
            v-model="detailDialog"
            :path="path"
            mode="update"
            :item="selectedDetailItem"
            @reload="$emit('reload', { ...query })"
        />

        <StockReceiptInspectionDialog
            v-if="selectedInspectionItem"
            v-model="inspectionHistoryDialog"
            :item="selectedInspectionItem"
        />

        <StockReceiptEventDialog
            v-if="selectedEventItem"
            v-model="eventDialog"
            :item="selectedEventItem"
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
import StockReceiptEventDialog from "./components/StockReceiptEventDialog.vue";
import StockReceiptInspectionDialog from "./components/StockReceiptInspectionDialog.vue";
import { constant } from "@/utils/constants/constant";
import { mapGetters, mapActions } from "vuex";

export default {
    name: "StockReceiptList",
    components: {
        FilterPagination,
        DetailStockReceipt,
        StockReceiptEventDialog,
        StockReceiptInspectionDialog,
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
            expandedReceiptIds: [],
            selectedDetailItem: null,
            detailDialog: false,
            selectedInspectionItem: null,
            inspectionHistoryDialog: false,
            selectedEventItem: null,
            eventDialog: false,
            headers: [
                {
                    key: "action",
                    width: 130,
                    minWidth: 130,
                    maxWidth: 130,
                    sortable: false,
                },
                {
                    title: this.$t("stock_receipt.columns.id") || "ID",
                    key: "id",
                    width: 100,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("stock_receipt.columns.code") || "Mã phiếu",
                    key: "code",
                    width: 450,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("stock_receipt.columns.title") || "Tiêu đề",
                    key: "title",
                    width: 500,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("stock_receipt.columns.warehouse") || "Kho nhận",
                    key: "warehouseSnapshot",
                    width: 250,
                    filterComponent: markRaw(FilterText),
                    value: (item) => item.warehouseSnapshot?.name || "",
                },
                {
                    title: this.$t("base.status") || "Trạng thái",
                    key: "status",
                    width: 200,
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
                    width: 200,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("base.updated_at") || "Ngày cập nhật",
                    key: "updatedAt",
                    width: 200,
                    filterComponent: markRaw(FilterDateRange),
                },
            ],
        };
    },
    computed: {
        ...mapGetters("stockReceipt", [
            "items",
            "loading",
            "totalItems",
            "childrenByParentId",
            "isChildLoading",
        ]),
        visibleItems() {
            const rows = [];

            const appendRows = (receipts, depth = 0) => {
                for (const receipt of receipts) {
                    const children = this.childrenByParentId[receipt.id] ?? [];

                    const hasChildren =
                        Boolean(receipt.hasChildren) ||
                        Number(receipt.childCount) > 0 ||
                        children.length > 0;

                    const expanded = this.expandedReceiptIds.includes(
                        receipt.id,
                    );

                    rows.push({
                        ...receipt,
                        _treeDepth: depth,
                        _hasChildren: hasChildren,
                        _expanded: expanded,
                    });

                    if (expanded && children.length > 0) {
                        appendRows(children, depth + 1);
                    }
                }
            };

            appendRows(this.items);

            return rows;
        },
        tableMinWidth() {
            return this.headers.reduce((total, col) => {
                return total + (col.width || col.minWidth || 0);
            }, 0);
        },
    },
    watch: {
        items: {
            immediate: true,
            handler(newItems) {
                if (Array.isArray(newItems)) {
                    // Tải lại dữ liệu phiếu con cho các node đang được mở (expanded)
                    if (this.expandedReceiptIds.length > 0) {
                        this.expandedReceiptIds.forEach(async (parentId) => {
                            await this.fetchChildren(parentId);
                        });
                    }

                    if (this.query?.f?.length > 0) {
                        newItems.forEach(async (item) => {
                            if (item.hasChildren || item.childCount > 0) {
                                if (
                                    !this.expandedReceiptIds.includes(item.id)
                                ) {
                                    this.expandedReceiptIds.push(item.id);
                                }
                                if (!this.childrenByParentId[item.id]) {
                                    await this.fetchChildren(item.id);
                                }
                            }
                        });
                    }
                }
            },
        },
    },
    methods: {
        ...mapActions("stockReceipt", ["fetchChildren"]),
        openDetail(item) {
            this.selectedDetailItem = item;
            this.detailDialog = true;
        },
        openInspectionHistory(item) {
            this.selectedInspectionItem = item;
            this.inspectionHistoryDialog = true;
        },
        openEventHistory(item) {
            this.selectedEventItem = item;
            this.eventDialog = true;
        },
        async toggleReceipt(item) {
            const expanded = this.expandedReceiptIds.includes(item.id);

            if (expanded) {
                this.expandedReceiptIds = this.expandedReceiptIds.filter(
                    (id) => id !== item.id,
                );
                return;
            }

            const loaded = Object.prototype.hasOwnProperty.call(
                this.childrenByParentId,
                item.id,
            );

            if (!loaded) {
                await this.fetchChildren(item.id);
            }

            this.expandedReceiptIds = [...this.expandedReceiptIds, item.id];
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
.table-scroll-container {
    overflow-x: auto;
    overflow-y: hidden;
    width: 100%;
}

.table-scroll-container :deep(.v-data-table),
.table-scroll-container :deep(table) {
    min-width: var(--table-min-width, 600px);
}

.receipt-tree-cell {
    display: flex;
    align-items: center;
    min-height: 40px;
    position: relative;
}

.tree-toggle {
    flex: 0 0 28px;
}

.tree-toggle-placeholder {
    display: inline-block;
    flex: 0 0 28px;
}
</style>
