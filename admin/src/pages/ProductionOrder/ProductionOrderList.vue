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
                    <div class="d-flex align-center justify-center ga-2">
                        <v-tooltip
                            v-if="permission?.show"
                            :text="$t('button.detail')"
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
                            v-if="permission?.show"
                            :text="$t('production_order.event.tooltip')"
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="outlined"
                                    color="info"
                                    @click="openEvent(item)"
                                >
                                    <v-icon>mdi-history</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template #[`item.requestId`]="{ item }">
                    <div v-if="item.request" class="d-flex flex-column">
                        <span>{{ item.request.code || item.request.id }}</span>
                        <span class="text-caption text-medium-emphasis">
                            {{ item.request.title || "--" }}
                        </span>
                    </div>
                    <span v-else>--</span>
                </template>

                <template #[`item.materialWarehouseId`]="{ item }">
                    <div
                        v-if="item.materialWarehouse"
                        class="d-flex flex-column"
                    >
                        <span>{{ item.materialWarehouse.name || "--" }}</span>
                        <span class="text-caption text-medium-emphasis">
                            {{ item.materialWarehouse.code || "--" }}
                        </span>
                    </div>
                    <span v-else>--</span>
                </template>

                <template #[`item.finishedGoodsWarehouseId`]="{ item }">
                    <div
                        v-if="item.finishedGoodsWarehouse"
                        class="d-flex flex-column"
                    >
                        <span>{{
                            item.finishedGoodsWarehouse.name || "--"
                        }}</span>
                        <span class="text-caption text-medium-emphasis">
                            {{ item.finishedGoodsWarehouse.code || "--" }}
                        </span>
                    </div>
                    <span v-else>--</span>
                </template>

                <template #[`item.status`]="{ item }">
                    <v-chip :color="getStatusColor(item.status)"
                        size="small"
                    >
                        {{ getStatusLabel(item.status) }}
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
        <DetailProductionOrder
            v-model="detailDialog"
            :item="selectedDetailItem"
            @reload="$emit('reload', { ...query })"
        />
        <ProductionOrderEventDialog
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
import DetailProductionOrder from "./DetailProductionOrder.vue";
import ProductionOrderEventDialog from "./ProductionOrderEventDialog.vue";
import { mapGetters } from "vuex";
import FilterAutoComplete from "@/components/filters/FilterAutoComplete.vue";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";

export default {
    name: "ProductionOrderList",
    components: {
        FilterPagination,
        DetailProductionOrder,
        ProductionOrderEventDialog,
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
        // Sử dụng hook useFilterPagination
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
            selectedDetailItem: null,
            detailDialog: false,
            selectedEventItem: null,
            eventDialog: false,
            headers: [
                {
                    key: "action",
                    width: 110,
                    minWidth: 110,
                    maxWidth: 110,
                    sortable: false,
                },
                {
                    title: this.$t("production_order.columns.id"),
                    key: "id",
                    width: 100,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("production_order.columns.code"),
                    key: "code",
                    width: 150,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("production_order.columns.title"),
                    key: "title",
                    width: 450,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("production_order.columns.request"),
                    key: "requestId",
                    width: 180,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t(
                        "production_order.columns.material_warehouse",
                    ),
                    key: "materialWarehouseId",
                    width: 240,
                    filterComponent: markRaw(FilterAutoComplete),
                    path: API_ROUTES_CONFIG.warehouse,
                    sortable: false,
                },
                {
                    title: this.$t(
                        "production_order.columns.finished_goods_warehouse",
                    ),
                    key: "finishedGoodsWarehouseId",
                    width: 240,
                    filterComponent: markRaw(FilterAutoComplete),
                    path: API_ROUTES_CONFIG.warehouse,
                    sortable: false,
                },
                {
                    title: this.$t("base.status"),
                    key: "status",
                    width: 170,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t("production_order.status.CREATED"),
                            value: "CREATED",
                        },
                        {
                            title: this.$t(
                                "production_order.status.MATERIAL_ISSUED",
                            ),
                            value: "MATERIAL_ISSUED",
                        },
                        {
                            title: this.$t("production_order.status.STARTED"),
                            value: "STARTED",
                        },
                        {
                            title: this.$t(
                                "production_order.status.IN_PROGRESS",
                            ),
                            value: "IN_PROGRESS",
                        },
                        {
                            title: this.$t(
                                "production_order.status.INSPECTING",
                            ),
                            value: "INSPECTING",
                        },
                        {
                            title: this.$t("production_order.status.COMPLETED"),
                            value: "COMPLETED",
                        },
                        {
                            title: this.$t("production_order.status.CANCELLED"),
                            value: "CANCELLED",
                        },
                    ],
                },
                {
                    title: this.$t("production_order.columns.started_at"),
                    key: "startedAt",
                    width: 180,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("production_order.columns.completed_at"),
                    key: "completedAt",
                    width: 180,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("base.note"),
                    key: "note",
                    width: 240,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("base.created_at"),
                    key: "createdAt",
                    width: 180,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("base.updated_at"),
                    key: "updatedAt",
                    width: 180,
                    filterComponent: markRaw(FilterDateRange),
                },
            ],
        };
    },
    computed: {
        ...mapGetters("productionOrder", ["items", "loading", "totalItems"]),
        tableMinWidth() {
            return this.headers.reduce((total, col) => {
                return total + (col.width || col.minWidth || 0);
            }, 0);
        },
    },
    methods: {
        getStatusLabel(status) {
            return (
                this.$t("production_order.status." + status) || status || "--"
            );
        },
        getStatusColor(status) {
            return (
                {
                    CREATED: "grey",
                    MATERIAL_ISSUED: "deep-purple",
                    STARTED: "info",
                    IN_PROGRESS: "warning",
                    INSPECTING: "teal",
                    COMPLETED: "success",
                    CANCELLED: "error",
                }[status] || "primary"
            );
        },
        openDetail(item) {
            this.selectedDetailItem = item;
            this.detailDialog = true;
        },
        openEvent(item) {
            this.selectedEventItem = item;
            this.eventDialog = true;
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
