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

            <template #[`item.action`]="{ item }">
                <div class="d-flex align-center justify-start gap-1">
                    <ThemSuaNguoiDung
                        :path="path"
                        mode="update"
                        :item="item"
                        @reload="$emit('reload')"
                    />
                    <v-btn
                        icon
                        size="small"
                        variant="text"
                        color="error"
                        @click="openDeleteDialog(item.id)"
                    >
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
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

            <template #[`item.status`]="{ item }">
                <v-chip
                    :color="item.status === 1 ? 'success' : 'error'"
                    size="small"
                >
                    {{
                        item.status === 1
                            ? $t("status_values.active")
                            : $t("status_values.inactive")
                    }}
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
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import ThemSuaNguoiDung from "./ThemSuaNguoiDung.vue";
import { deleteData } from "@/services/bases/deleteData";

export default {
    name: "DataTable",
    components: {
        FilterPagination,
        ConfirmDialog,
        ThemSuaNguoiDung,
    },
    props: {
        path: {
            type: String,
            default: "",
        },
        users: {
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
            showConfirmDelete: false,
            isDeleting: false,
            deletingId: null,
            headers: [
                {
                    key: "action",
                    width: 80,
                    minWidth: 80,
                    maxWidth: 80,
                    sortable: false,
                },
                {
                    title: this.$t("user.columns.id"),
                    key: "id",
                    width: 100,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("user.columns.name"),
                    key: "name",
                    width: 200,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("user.columns.email"),
                    key: "email",
                    width: 200,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("user.columns.phone"),
                    key: "phone",
                    width: 150,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("user.columns.role"),
                    key: "maVaiTro",
                    width: 120,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        { title: this.$t("user.roles.admin"), value: "ADMIN" },
                        { title: this.$t("user.roles.user"), value: "USER" },
                        {
                            title: this.$t("user.roles.manager"),
                            value: "MANAGER",
                        },
                        { title: this.$t("user.roles.staff"), value: "STAFF" },
                    ],
                },
                {
                    title: this.$t("base.status"),
                    key: "status",
                    width: 100,
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
                    value: (item) =>
                        item.status === 1
                            ? this.$t("status_values.active")
                            : this.$t("status_values.inactive"),
                },
                {
                    title: this.$t("base.created_at"),
                    key: "createdAt",
                    width: 150,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("base.updated_at"),
                    key: "updatedAt",
                    width: 150,
                    filterComponent: markRaw(FilterDateRange),
                },
            ],
        };
    },
    computed: {
        items() {
            return this.users;
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

<style scoped></style>
