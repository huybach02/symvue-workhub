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
                                    <span class="v-data-table-header__content">
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
                    <div class="d-flex align-center justify-space-between ga-1">
                        <v-tooltip
                            v-if="permission?.show"
                            :text="$t('button.update')"
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <CreateEditNotification
                                    v-bind="tooltipProps"
                                    :path="path"
                                    mode="update"
                                    :item="item"
                                    @reload="$emit('reload')"
                                />
                            </template>
                        </v-tooltip>
                        <v-tooltip
                            v-if="permission?.delete"
                            :text="$t('button.delete')"
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="outlined"
                                    color="error"
                                    @click="openDeleteDialog(item.id)"
                                >
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template #[`item.color`]="{ item }">
                    <v-chip :color="item.color" size="small">
                        {{ $t("thong_bao.options.type." + item.color) }}
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
        <ConfirmDialog
            v-model="showConfirmDelete"
            :message="
                $t('media_library.delete_confirm_message', {
                    count: 1,
                })
            "
            :loading="isDeleting"
            @confirm="handleDelete"
            @cancel="showConfirmDelete = false"
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
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import CreateEditNotification from "./CreateEditNotification.vue";
import { deleteData } from "@/services/bases/deleteData";
import FilterAutoComplete from "@/components/filters/FilterAutoComplete.vue";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";

export default {
    name: "NotificationList",
    components: {
        FilterPagination,
        ConfirmDialog,
        CreateEditNotification,
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
            showConfirmDelete: false,
            isDeleting: false,
            deletingId: null,
            headers: [
                // {
                //     key: "action",
                //     width: 85,
                //     minWidth: 85,
                //     maxWidth: 85,
                //     sortable: false,
                // },
                {
                    title: this.$t("thong_bao.columns.fromUser"),
                    key: "fromUser",
                    sortable: false,
                    width: 180,
                    filterComponent: markRaw(FilterAutoComplete),
                    path: API_ROUTES_CONFIG.user,
                },
                {
                    title: this.$t("thong_bao.columns.sendTo"),
                    key: "sendTo",
                    sortable: false,
                    width: 200,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t("thong_bao.options.sendTo.all"),
                            value: "all",
                        },
                        {
                            title: this.$t(
                                "thong_bao.options.sendTo.department",
                            ),
                            value: "department",
                        },
                        {
                            title: this.$t("thong_bao.options.sendTo.user"),
                            value: "user",
                        },
                    ],
                },
                {
                    title: this.$t("thong_bao.columns.title"),
                    key: "title",
                    width: 200,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("thong_bao.columns.body"),
                    key: "body",
                    width: 300,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("thong_bao.columns.type"),
                    key: "color",
                    width: 150,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t("thong_bao.options.type.primary"),
                            value: "primary",
                        },
                        {
                            title: this.$t("thong_bao.options.type.info"),
                            value: "info",
                        },
                        {
                            title: this.$t("thong_bao.options.type.warning"),
                            value: "warning",
                        },
                        {
                            title: this.$t("thong_bao.options.type.error"),
                            value: "error",
                        },
                    ],
                    value: (item) =>
                        item.color === "primary"
                            ? this.$t("thong_bao.options.type.primary")
                            : item.color === "info"
                              ? this.$t("thong_bao.options.type.info")
                              : item.color === "warning"
                                ? this.$t("thong_bao.options.type.warning")
                                : this.$t("thong_bao.options.type.error"),
                },
                {
                    title: this.$t("base.created_at"),
                    key: "createdAt",
                    width: 150,
                    filterComponent: markRaw(FilterDateRange),
                },
            ],
        };
    },
    computed: {
        tableMinWidth() {
            return this.headers.reduce((total, col) => {
                return total + (col.width || col.minWidth || 0);
            }, 0);
        },
    },
    methods: {
        openDeleteDialog(id) {
            this.deletingId = id;
            this.showConfirmDelete = true;
        },
        async handleDelete() {
            this.isDeleting = true;
            await deleteData(this.path, this.deletingId);
            this.isDeleting = false;
            this.showConfirmDelete = false;
            this.deletingId = null;
            this.$emit("reload");
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
