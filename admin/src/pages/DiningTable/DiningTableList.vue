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
                                class="d-flex align-center justify-space-between py-2"
                            >
                                <template v-if="!col.filterComponent">
                                    <span
                                        class="v-data-table-header__content"
                                        >{{ col.title }}</span
                                    >
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

                <template #[`item.tableNumber`]="{ item }">
                    <span class="font-weight-bold text-primary">
                        {{ $t("dining_table.columns.table_number") }}
                        {{ item.tableNumber }}
                    </span>
                </template>

                <template #[`item.status`]="{ item }">
                    <v-chip
                        :color="item.status === 1 ? 'success' : 'error'"
                        size="small"
                        variant="flat"
                    >
                        {{
                            item.status === 1
                                ? $t("status_values.active")
                                : $t("status_values.inactive")
                        }}
                    </v-chip>
                </template>

                <template #[`item.isUsing`]="{ item }">
                    <v-chip
                        :color="item.isUsing ? 'warning' : 'default'"
                        size="small"
                        :prepend-icon="
                            item.isUsing
                                ? 'mdi-account-group'
                                : 'mdi-table-furniture'
                        "
                        variant="tonal"
                    >
                        {{
                            item.isUsing
                                ? $t("dining_table.using_status.in_use")
                                : $t("dining_table.using_status.empty")
                        }}
                    </v-chip>
                </template>

                <template #[`item.qrCode`]="{ item }">
                    <v-btn
                        size="small"
                        variant="tonal"
                        color="primary"
                        prepend-icon="mdi-qrcode"
                        @click="openQrDialog(item)"
                    >
                        {{ $t("dining_table.view_qr") }}
                    </v-btn>
                </template>

                <template #[`item.action`]="{ item }">
                    <v-tooltip
                        :text="
                            item.isUsing && item.status === 1
                                ? $t('dining_table.cannot_deactivate_in_use')
                                : item.status === 1
                                  ? $t('dining_table.toggle_inactive')
                                  : $t('dining_table.toggle_active')
                        "
                        location="top"
                    >
                        <template #activator="{ props: tooltipProps }">
                            <div v-bind="tooltipProps" class="d-inline-block">
                                <v-btn
                                    :color="
                                        item.status === 1 ? 'error' : 'success'
                                    "
                                    size="small"
                                    variant="outlined"
                                    :disabled="
                                        togglingId === item.id ||
                                        (item.status === 1 && item.isUsing)
                                    "
                                    :loading="togglingId === item.id"
                                    :prepend-icon="
                                        item.status === 1
                                            ? 'mdi-power-off'
                                            : 'mdi-power'
                                    "
                                    @click="handleToggleStatus(item)"
                                >
                                    {{
                                        item.status === 1
                                            ? $t("dining_table.toggle_inactive")
                                            : $t("dining_table.toggle_active")
                                    }}
                                </v-btn>
                            </div>
                        </template>
                    </v-tooltip>
                </template>

                <template #no-data>
                    <div class="pa-8 text-center">
                        <v-icon
                            icon="mdi-table-furniture"
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

        <!-- Phân trang -->
        <FilterPagination
            :total-items="totalItems"
            :current-page="query.page"
            :items-per-page="query.limit"
            @update:page="onPageChange"
            @update:items-per-page="onLimitChange"
        />

        <!-- Dialog xem mã QR -->
        <DiningTableQrDialog v-model="showQrDialog" :table="selectedTable" />
    </div>
</template>

<script>
import { markRaw } from "vue";
import FilterText from "@/components/filters/FilterText.vue";
import FilterSelect from "@/components/filters/FilterSelect.vue";
import FilterDateRange from "@/components/filters/FilterDateRange.vue";
import FilterPagination from "@/components/filters/FilterPagination.vue";
import { useFilterPagination } from "@/hooks/useFilterPagination.js";
import DiningTableQrDialog from "./DiningTableQrDialog.vue";
import { mapActions, mapGetters } from "vuex";
import FilterPlaceholder from "@/components/filters/FilterPlaceholder.vue";

export default {
    name: "DiningTableList",
    components: {
        FilterPagination,
        DiningTableQrDialog,
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
        // Sử dụng hook useFilterPagination đồng nhất với toàn bộ dự án
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
            selectedStatusFilter: "all",
            showQrDialog: false,
            selectedTable: null,
            togglingId: null,
            headers: [
                {
                    title: this.$t("dining_table.columns.table_number"),
                    key: "tableNumber",
                    width: 150,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("dining_table.columns.status"),
                    key: "status",
                    width: 150,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t("status_values.active"),
                            value: "1",
                        },
                        {
                            title: this.$t("status_values.inactive"),
                            value: "0",
                        },
                    ],
                },
                {
                    title: this.$t("dining_table.columns.is_using"),
                    key: "isUsing",
                    width: 170,
                    sortable: false,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t("dining_table.using_status.in_use"),
                            value: "true",
                        },
                        {
                            title: this.$t("dining_table.using_status.empty"),
                            value: "false",
                        },
                    ],
                },
                {
                    title: this.$t("dining_table.columns.qr_code"),
                    key: "qrCode",
                    width: 150,
                    sortable: false,
                    filterComponent: markRaw(FilterPlaceholder),
                },
                {
                    title: this.$t("base.created_at"),
                    key: "createdAt",
                    width: 160,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("dining_table.columns.action"),
                    key: "action",
                    width: 170,
                    sortable: false,
                    filterComponent: markRaw(FilterPlaceholder),
                },
            ],
        };
    },
    computed: {
        ...mapGetters("diningTable", ["items", "loading", "totalItems"]),
        tableMinWidth() {
            return this.headers.reduce((total, col) => {
                return total + (col.width || col.minWidth || 0);
            }, 0);
        },
    },
    methods: {
        ...mapActions("diningTable", ["toggleStatus"]),
        openQrDialog(item) {
            this.selectedTable = item;
            this.showQrDialog = true;
        },
        async handleToggleStatus(item) {
            this.togglingId = item.id;
            try {
                const res = await this.toggleStatus(item.id);
                if (res) {
                    this.$emit("reload", { ...this.query });
                }
            } finally {
                this.togglingId = null;
            }
        },
        onQuickStatusChange(val) {
            if (val === "all") {
                this.onFilter("status", {
                    type: "includes",
                    value: [],
                });
            } else {
                this.onFilter("status", {
                    type: "includes",
                    value: [val],
                });
            }
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
