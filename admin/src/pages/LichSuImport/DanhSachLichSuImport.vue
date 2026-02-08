<template>
    <div class="table-wrapper">
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

            <template #[`item.status`]="{ item }">
                <v-chip
                    :color="item.status === 'SUCCESS' ? 'success' : 'error'"
                    size="small"
                >
                    {{
                        item.status === "SUCCESS"
                            ? $t("status.success")
                            : $t("status.error")
                    }}
                </v-chip>
            </template>

            <template #[`item.action`]="{ item }">
                <v-btn
                    v-if="item.status == 'FAILED'"
                    icon
                    size="small"
                    variant="tonal"
                    color="error"
                    @click="openViewDialog(item)"
                >
                    <v-icon>mdi-eye</v-icon>
                </v-btn>
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

        <!-- Custom Pagination -->
        <FilterPagination
            :total-items="totalItems"
            :current-page="query.page"
            :items-per-page="query.limit"
            @update:page="onPageChange"
            @update:items-per-page="onLimitChange"
        />

        <v-dialog v-model="dialog" max-width="1200" scrollable>
            <v-card
                prepend-icon="mdi-microsoft-excel"
                :title="$t('base.error_detail')"
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="close-btn"
                    @click="dialog = false"
                />

                <v-card-text>
                    <v-table>
                        <thead>
                            <tr>
                                <th class="text-left">
                                    {{ $t("lich_su_import.columns.row") }}
                                </th>
                                <th class="text-left">
                                    {{ $t("lich_su_import.columns.rawData") }}
                                </th>
                                <th class="text-left">
                                    {{ $t("lich_su_import.columns.errors") }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in viewItem.errorDetails"
                                :key="item.row"
                            >
                                <td>{{ item.row }}</td>
                                <td>
                                    <pre class="text-caption">{{
                                        formatJson(item.data_raw)
                                    }}</pre>
                                </td>
                                <td>
                                    <v-chip
                                        v-for="error in item.errors"
                                        :key="error"
                                        color="error"
                                        size="small"
                                        class="mr-2 mb-2"
                                    >
                                        {{ error }}
                                    </v-chip>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { markRaw } from "vue";
import FilterText from "@/components/filters/FilterText.vue";
import FilterSelect from "@/components/filters/FilterSelect.vue";
import FilterPagination from "@/components/filters/FilterPagination.vue";
import { useFilterPagination } from "@/hooks/useFilterPagination.js";

export default {
    name: "DanhSachLichSuImport",
    components: {
        FilterPagination,
    },
    props: {
        path: {
            type: String,
            default: "",
        },
        items: {
            type: Array,
            default: () => [],
        },
        totalItems: {
            type: Number,
            default: 0,
        },
        loading: {
            type: Boolean,
            default: false,
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
            dialog: false,
            viewItem: null,
            headers: [
                {
                    title: this.$t("lich_su_import.columns.id"),
                    key: "id",
                    width: 50,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("lich_su_import.columns.fileName"),
                    key: "fileName",
                    width: 200,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("lich_su_import.columns.entityType"),
                    key: "entityType",
                    width: 100,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("base.status"),
                    key: "status",
                    width: 100,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t("status.success"),
                            value: "SUCCESS",
                        },
                        {
                            title: this.$t("status.error"),
                            value: "ERROR",
                        },
                    ],
                    value: (item) =>
                        item.status === "SUCCESS"
                            ? this.$t("status.success")
                            : this.$t("status.error"),
                },
                {
                    title: this.$t("lich_su_import.columns.totalRows"),
                    key: "totalRows",
                    width: 100,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("lich_su_import.columns.successRows"),
                    key: "successRows",
                    width: 150,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("lich_su_import.columns.errorRows"),
                    key: "errorRows",
                    width: 100,
                    filterComponent: markRaw(FilterText),
                },
                {
                    key: "action",
                    width: 80,
                    minWidth: 80,
                    maxWidth: 80,
                    sortable: false,
                },
            ],
        };
    },
    methods: {
        openViewDialog(item) {
            this.viewItem = item;
            this.dialog = true;
        },
        formatJson(jsonString) {
            try {
                // Parse JSON string và format lại với indent 2 spaces
                const parsed = JSON.parse(jsonString);
                return JSON.stringify(parsed, null, 2);
            } catch {
                // Nếu không parse được, trả về string gốc
                return jsonString;
            }
        },
    },
};
</script>

<style scoped></style>
