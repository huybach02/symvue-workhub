<template>
    <div class="table-wrapper">
        <div class="table-scroll-container overflow-x-auto w-100">
            <v-data-table-server
                :items="movementData.items"
                :items-length="movementData.totalItems"
                :headers="headers"
                :sort-by="sortArray"
                :style="{ minWidth: tableMinWidth + 'px' }"
                :items-per-page="-1"
                hide-default-footer
                :loading="inventoryMovementLoading"
                @update:options="onOptions"
            >
                <template
                    #headers="{ columns, isSorted, getSortIcon, toggleSort }"
                >
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            :style="{
                                width: column.width
                                    ? column.width + 'px'
                                    : undefined,
                                minWidth: column.minWidth
                                    ? column.minWidth + 'px'
                                    : undefined,
                                maxWidth: column.maxWidth
                                    ? column.maxWidth + 'px'
                                    : undefined,
                                left:
                                    column.fixed === 'start'
                                        ? column.fixedOffset + 'px'
                                        : undefined,
                            }"
                            :class="{
                                'v-data-table-header__th': true,
                                'v-data-table-header-sticky': true,
                                'v-data-table-column--fixed':
                                    column.fixed === 'start',
                                'v-data-table-column--last-fixed':
                                    column.lastFixed,
                            }"
                        >
                            <div
                                class="d-flex align-center justify-space-between py-2"
                            >
                                <template v-if="!column.filterComponent">
                                    <span class="v-data-table-header__content">
                                        {{ column.title }}
                                    </span>
                                </template>

                                <component
                                    :is="column.filterComponent"
                                    v-else
                                    :items="column.items"
                                    :title="column.title"
                                    class="flex-grow-1"
                                    @update="
                                        (value) => onFilter(column.key, value)
                                    "
                                />

                                <v-icon
                                    v-if="column.sortable !== false"
                                    :icon="getSortIcon(column)"
                                    :class="{
                                        'v-data-table-header__sort-icon': true,
                                        'v-data-table-header__sort-icon--active':
                                            isSorted(column),
                                    }"
                                    size="small"
                                    @click="() => toggleSort(column)"
                                />
                            </div>
                        </th>
                    </tr>
                </template>

                <template #[`item.postedAt`]="{ item }">
                    {{ formatDateTime(item.postedAt) }}
                </template>
                <template #[`item.movementType`]="{ item }">
                    <v-chip
                        size="small"
                        :color="movementColor(item.movementType)"
                    >
                        {{ enumLabel("movement_types", item.movementType) }}
                    </v-chip>
                </template>
                <template #[`item.merchandise`]="{ item }">
                    {{ merchandiseLabel(item.merchandise) }}
                </template>
                <template #[`item.lot`]="{ item }">
                    {{ item.lot?.internalCode || "--" }}
                </template>
                <template #[`item.quantityBaseDelta`]="{ item }">
                    <span
                        :class="
                            Number(item.quantityBaseDelta) >= 0
                                ? 'text-success'
                                : 'text-error'
                        "
                    >
                        {{ formatDelta(item.quantityBaseDelta) }}
                        {{ unitLabel(item.baseUnit) }}
                    </span>
                </template>
                <template #[`item.unitCostBase`]="{ item }">
                    {{ formatUnitCost(item.unitCostBase) }}
                </template>
                <template #[`item.sourceType`]="{ item }">
                    {{ enumLabel("source_types", item.sourceType) }}
                </template>
                <template #[`item.postedBy`]="{ item }">
                    {{ item.postedBy?.name || "--" }}
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

        <FilterPagination
            :total-items="movementData.totalItems"
            :current-page="query.page"
            :items-per-page="query.limit"
            @update:page="onPageChange"
            @update:items-per-page="onLimitChange"
        />
    </div>
</template>

<script>
import { markRaw } from "vue";
import { mapActions, mapGetters } from "vuex";
import FilterDateRange from "@/components/filters/FilterDateRange.vue";
import FilterPagination from "@/components/filters/FilterPagination.vue";
import FilterSelect from "@/components/filters/FilterSelect.vue";
import FilterText from "@/components/filters/FilterText.vue";
import { inventoryTableMixin } from "./inventoryTableMixin";

export default {
    name: "WarehouseInventoryMovementTable",
    components: {
        FilterPagination,
    },
    mixins: [inventoryTableMixin],
    props: {
        warehouseId: {
            type: Number,
            default: null,
        },
        active: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            headers: [
                {
                    title: this.$t("warehouse.columns.id"),
                    key: "id",
                    width: 90,
                    fixed: "start",
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.merchandise"),
                    key: "merchandise",
                    width: 260,
                    fixed: "start",
                    sortable: false,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.posted_at"),
                    key: "postedAt",
                    width: 200,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("warehouse.inventory.movement_type"),
                    key: "movementType",
                    width: 200,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        "STOCK_IN",
                        "STOCK_OUT",
                        "ADJUSTMENT",
                        "TRANSFER_IN",
                        "TRANSFER_OUT",
                        "REVERSAL",
                    ].map((value) => ({
                        title: this.$t(
                            `warehouse.inventory.movement_types.${value}`,
                        ),
                        value,
                    })),
                },
                {
                    title: this.$t("warehouse.inventory.lot"),
                    key: "lot",
                    width: 300,
                    sortable: false,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.quantity_delta"),
                    key: "quantityBaseDelta",
                    width: 200,
                    align: "end",
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.unit_cost"),
                    key: "unitCostBase",
                    width: 180,
                    align: "end",
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.source_type"),
                    key: "sourceType",
                    width: 240,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        "PRODUCTION_MATERIAL_ISSUE",
                        "PRODUCTION_GOODS_RECEIPT",
                    ].map((value) => ({
                        title: this.$t(
                            `warehouse.inventory.source_types.${value}`,
                        ),
                        value,
                    })),
                },
                {
                    title: this.$t("warehouse.inventory.posted_by"),
                    key: "postedBy",
                    width: 200,
                    sortable: false,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.note"),
                    key: "note",
                    width: 300,
                    filterComponent: markRaw(FilterText),
                },
            ],
        };
    },
    computed: {
        ...mapGetters("warehouse", [
            "inventoryMovementsByWarehouseId",
            "inventoryMovementLoading",
        ]),
        ...mapGetters("generalSettings", ["currency", "dataLoaded"]),
        movementData() {
            return this.inventoryMovementsByWarehouseId(this.warehouseId);
        },
    },
    mounted() {
        if (!this.dataLoaded) {
            this.fetchGeneralSettings();
        }
    },
    methods: {
        ...mapActions("warehouse", ["fetchInventoryMovements"]),
        ...mapActions("generalSettings", {
            fetchGeneralSettings: "fetchSettings",
        }),
        async loadData() {
            if (!this.shouldFetch()) {
                return;
            }

            await this.fetchInventoryMovements({
                id: this.warehouseId,
                params: { ...this.query },
            });
        },
        movementColor(value) {
            if (["STOCK_IN", "TRANSFER_IN"].includes(value)) {
                return "success";
            }
            if (["STOCK_OUT", "TRANSFER_OUT"].includes(value)) {
                return "error";
            }
            return "info";
        },
        formatUnitCost(value) {
            if (value === null || value === undefined || value === "") {
                return "--";
            }

            return `${this.formatNumber(value, 4)} ${this.currency}`;
        },
    },
};
</script>
