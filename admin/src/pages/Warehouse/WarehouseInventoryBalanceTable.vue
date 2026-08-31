<template>
    <div class="table-wrapper">
        <div class="table-scroll-container overflow-x-auto w-100">
            <v-data-table-server
                :items="balanceData.items"
                :items-length="balanceData.totalItems"
                :headers="headers"
                :sort-by="sortArray"
                :style="{ minWidth: tableMinWidth + 'px' }"
                :items-per-page="-1"
                hide-default-footer
                :loading="inventoryBalanceLoading"
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

                <template #[`item.merchandise`]="{ item }">
                    {{ merchandiseLabel(item.merchandise) }}
                </template>
                <template #[`item.lot`]="{ item }">
                    {{ item.lot?.internalCode || "--" }}
                </template>
                <template #[`item.lotStatus`]="{ item }">
                    <v-chip
                        size="small"
                        :color="lotStatusColor(item.lot?.status)"
                    >
                        {{ enumLabel("lot_statuses", item.lot?.status) }}
                    </v-chip>
                </template>
                <template #[`item.expiryDate`]="{ item }">
                    {{ formatDate(item.lot?.expiryDate) }}
                </template>
                <template #[`item.onHandBaseQuantity`]="{ item }">
                    {{ formatNumber(item.onHandBaseQuantity) }}
                </template>
                <template #[`item.reservedBaseQuantity`]="{ item }">
                    {{ formatNumber(item.reservedBaseQuantity) }}
                </template>
                <template #[`item.blockedBaseQuantity`]="{ item }">
                    {{ formatNumber(item.blockedBaseQuantity) }}
                </template>
                <template #[`item.availableBaseQuantity`]="{ item }">
                    <span
                        class="font-weight-bold"
                        :class="
                            Number(item.availableBaseQuantity) >= 0
                                ? 'text-success'
                                : 'text-error'
                        "
                    >
                        {{ formatNumber(item.availableBaseQuantity) }}
                    </span>
                </template>
                <template #[`item.baseUnit`]="{ item }">
                    {{ unitLabel(item.baseUnit) }}
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
            :total-items="balanceData.totalItems"
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
import FilterPlaceholder from "@/components/filters/FilterPlaceholder.vue";

export default {
    name: "WarehouseInventoryBalanceTable",
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
                    title: this.$t("warehouse.inventory.lot"),
                    key: "lot",
                    width: 300,
                    sortable: false,
                    fixed: "start",
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.lot_status"),
                    key: "lotStatus",
                    width: 160,
                    sortable: false,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t(
                                "warehouse.inventory.lot_statuses.AVAILABLE",
                            ),
                            value: "AVAILABLE",
                        },
                        {
                            title: this.$t(
                                "warehouse.inventory.lot_statuses.BLOCKED",
                            ),
                            value: "BLOCKED",
                        },
                        {
                            title: this.$t(
                                "warehouse.inventory.lot_statuses.EXPIRED",
                            ),
                            value: "EXPIRED",
                        },
                        {
                            title: this.$t(
                                "warehouse.inventory.lot_statuses.DEPLETED",
                            ),
                            value: "DEPLETED",
                        },
                    ],
                },
                {
                    title: this.$t("warehouse.inventory.expiry_date"),
                    key: "expiryDate",
                    width: 170,
                    sortable: false,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("warehouse.inventory.on_hand"),
                    key: "onHandBaseQuantity",
                    width: 160,
                    align: "end",
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.reserved"),
                    key: "reservedBaseQuantity",
                    width: 160,
                    align: "end",
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.blocked"),
                    key: "blockedBaseQuantity",
                    width: 160,
                    align: "end",
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("warehouse.inventory.available"),
                    key: "availableBaseQuantity",
                    width: 170,
                    align: "end",
                    filterComponent: markRaw(FilterPlaceholder),
                },
                {
                    title: this.$t("warehouse.inventory.unit"),
                    key: "baseUnit",
                    width: 120,
                    filterComponent: markRaw(FilterPlaceholder),
                },
            ],
        };
    },
    computed: {
        ...mapGetters("warehouse", [
            "inventoryBalancesByWarehouseId",
            "inventoryBalanceLoading",
        ]),
        balanceData() {
            return this.inventoryBalancesByWarehouseId(this.warehouseId);
        },
    },
    methods: {
        ...mapActions("warehouse", ["fetchInventoryBalances"]),
        async loadData() {
            if (!this.shouldFetch()) {
                return;
            }

            await this.fetchInventoryBalances({
                id: this.warehouseId,
                params: { ...this.query },
            });
        },
        lotStatusColor(value) {
            return (
                {
                    AVAILABLE: "success",
                    BLOCKED: "warning",
                    EXPIRED: "error",
                    DEPLETED: "grey",
                }[value] || "grey"
            );
        },
    },
};
</script>
