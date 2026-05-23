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
                        }"
                        class="v-data-table-header__th"
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
                <v-btn
                    v-bind="tooltipProps"
                    icon
                    size="small"
                    variant="outlined"
                    color="primary"
                    @click.stop="$emit('show-detail', item.id)"
                >
                    <v-icon>mdi-eye</v-icon>
                </v-btn>
            </template>

            <template #[`item.title`]="{ item }">
                <div class="py-2">
                    <div class="font-weight-bold">
                        {{ item.title }}
                    </div>
                    <div class="text-body-2 text-medium-emphasis">
                        {{ item.summary || item.payload?.reason || "--" }}
                    </div>
                </div>
            </template>

            <template #[`item.requester`]="{ item }">
                {{ item.requester?.name || "--" }}
            </template>

            <template #[`item.type`]="{ item }">
                {{ getRequestTypeTitle(item.type) }}
            </template>

            <template #[`item.status`]="{ item }">
                <v-chip
                    size="small"
                    :color="getRequestStatusColor(item.status)"
                    variant="tonal"
                >
                    {{ getRequestStatusLabel(item.status) }}
                </v-chip>
            </template>

            <template #[`item.createdAt`]="{ item }">
                {{ item.createdAt || "--" }}
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
import FilterAutoComplete from "@/components/filters/FilterAutoComplete.vue";
import FilterDateRange from "@/components/filters/FilterDateRange.vue";
import FilterPagination from "@/components/filters/FilterPagination.vue";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "RequestTable",
    components: {
        FilterPagination,
    },
    props: {
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
        query: {
            type: Object,
            required: true,
        },
        requestTypes: {
            type: Array,
            default: () => [],
        },
        showRequester: {
            type: Boolean,
            default: false,
        },
        showType: {
            type: Boolean,
            default: false,
        },
        showStatusFilter: {
            type: Boolean,
            default: true,
        },
    },
    emits: ["update:query", "reload", "show-detail"],
    data() {
        return {
            statusFilterItems: [
                { title: this.$t("request.status.pending"), value: "pending" },
                {
                    title: this.$t("request.status.rejected"),
                    value: "rejected",
                },
                {
                    title: this.$t("request.status.approved"),
                    value: "approved",
                },
                {
                    title: this.$t("request.status.cancelled"),
                    value: "cancelled",
                },
            ],
        };
    },
    computed: {
        sortArray() {
            if (!this.query.sort_column) {
                return [];
            }

            return [
                {
                    key: this.query.sort_column,
                    order: this.query.sort_direction,
                },
            ];
        },
        headers() {
            const headers = [
                {
                    title: "",
                    key: "action",
                    width: 72,
                    minWidth: 72,
                    sortable: false,
                },
                {
                    title: this.$t("request.column_title"),
                    key: "title",
                    minWidth: 260,
                    filterComponent: markRaw(FilterText),
                },
                {
                    title: this.$t("request.column_code"),
                    key: "code",
                    minWidth: 180,
                    filterComponent: markRaw(FilterText),
                },
            ];

            if (this.showRequester) {
                headers.push({
                    title: this.$t("request.requester"),
                    key: "requester",
                    minWidth: 180,
                    filterComponent: markRaw(FilterAutoComplete),
                    path: API_ROUTES_CONFIG.users,
                    sortable: false,
                });
            }

            if (this.showType) {
                headers.push({
                    title: this.$t("request.request_type"),
                    key: "type",
                    minWidth: 180,
                    filterComponent: markRaw(FilterSelect),
                    items: this.requestTypes.map((item) => ({
                        title: item.title,
                        value: item.code,
                    })),
                });
            }

            headers.push({
                title: this.$t("base.status"),
                key: "status",
                minWidth: 170,
                filterComponent: this.showStatusFilter
                    ? markRaw(FilterSelect)
                    : null,
                items: this.statusFilterItems,
            });

            headers.push({
                title: this.$t("base.created_at"),
                key: "createdAt",
                minWidth: 180,
                filterComponent: markRaw(FilterDateRange),
            });

            return headers;
        },
    },
    methods: {
        emitQuery(query) {
            this.$emit("update:query", query);
            this.$emit("reload", query);
        },
        onOptions(opt) {
            this.emitQuery({
                ...this.query,
                page: 1,
                sort_column: opt.sortBy?.length ? opt.sortBy[0].key : null,
                sort_direction: opt.sortBy?.length ? opt.sortBy[0].order : null,
            });
        },
        onPageChange(page) {
            this.emitQuery({
                ...this.query,
                page,
            });
        },
        onLimitChange(limit) {
            this.emitQuery({
                ...this.query,
                page: 1,
                limit,
            });
        },
        onFilter(field, filterObj) {
            const filters = (this.query.f ?? []).filter(
                (item) => item.field !== field,
            );
            const hasValue =
                filterObj.value !== undefined &&
                filterObj.value !== null &&
                filterObj.value !== "" &&
                (!Array.isArray(filterObj.value) || filterObj.value.length > 0);

            if (hasValue && filterObj.type) {
                filters.push({
                    field,
                    operator: filterObj.type,
                    value: filterObj.value,
                });
            }

            this.emitQuery({
                ...this.query,
                page: 1,
                f: filters,
            });
        },
        getRequestStatusLabel(status) {
            return functionHelper.getRequestStatusLabel(status);
        },
        getRequestStatusColor(status) {
            return functionHelper.getRequestStatusColor(status);
        },
        getRequestTypeTitle(type) {
            return (
                this.requestTypes.find((item) => item.code === type)?.title ||
                type ||
                "--"
            );
        },
    },
};
</script>
