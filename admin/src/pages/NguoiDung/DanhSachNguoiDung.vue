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
                            width: col.width ? col.width + 'px' : 'auto',
                            minWidth: col.minWidth + 'px',
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
                        {{ $t("user.list.no_data") }}
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

export default {
    name: "DataTable",
    components: {
        FilterPagination,
    },
    props: {
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
            headers: [
                {
                    title: this.$t("user.list.id"),
                    key: "id",
                    width: 50,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("user.list.name"),
                    key: "name",
                    width: 200,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("user.list.role"),
                    key: "maVaiTro",
                    width: 100,
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
                    title: this.$t("user.list.status"),
                    key: "status",
                    width: 100,
                    filterComponent: markRaw(FilterSelect),
                    items: [
                        {
                            title: this.$t("status_values.active"),
                            value: "1",
                        },
                        {
                            title: this.$t("status_values.blocked"),
                            value: "0",
                        },
                    ],
                    value: (item) =>
                        item.status === 1
                            ? this.$t("status_values.active")
                            : this.$t("status_values.inactive"),
                },
                {
                    title: this.$t("user.list.created_at"),
                    key: "createdAt",
                    width: 150,
                    filterComponent: markRaw(FilterDateRange),
                },
                {
                    title: this.$t("user.list.updated_at"),
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
};
</script>

<style scoped></style>
