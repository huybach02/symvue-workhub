import { functionHelper } from "@/helpers/functionHelper";

export const inventoryTableMixin = {
    data() {
        return {
            query: {
                page: 1,
                limit: 20,
                sort_column: null,
                sort_direction: null,
                f: [],
            },
            lastRequestKey: null,
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
        tableMinWidth() {
            return this.headers.reduce((total, column) => {
                return total + (column.width || column.minWidth || 0);
            }, 0);
        },
    },
    watch: {
        active: {
            immediate: true,
            handler(isActive) {
                if (!isActive) {
                    this.lastRequestKey = null;
                    return;
                }

                this.loadData();
            },
        },
        warehouseId() {
            if (this.active) {
                this.query.page = 1;
                this.lastRequestKey = null;
                this.loadData();
            }
        },
    },
    methods: {
        shouldFetch() {
            if (!this.active || !this.warehouseId) {
                return false;
            }

            const requestKey = `${this.warehouseId}:${JSON.stringify(this.query)}`;
            if (requestKey === this.lastRequestKey) {
                return false;
            }

            this.lastRequestKey = requestKey;
            return true;
        },
        onOptions(options) {
            const sort = options.sortBy?.[0] || null;
            this.query.sort_column = sort?.key || null;
            this.query.sort_direction = sort?.order || null;
            this.query.page = 1;
            this.loadData();
        },
        onPageChange(page) {
            this.query.page = page;
            this.loadData();
        },
        onLimitChange(limit) {
            this.query.limit = limit;
            this.query.page = 1;
            this.loadData();
        },
        onFilter(field, filter) {
            this.query.f = this.query.f.filter((item) => item.field !== field);

            const hasValue =
                filter.value !== undefined &&
                filter.value !== null &&
                filter.value !== "" &&
                (!Array.isArray(filter.value) || filter.value.length > 0);

            if (hasValue && filter.type) {
                this.query.f.push({
                    field,
                    operator: filter.type,
                    value: filter.value,
                });
            }

            this.query.page = 1;
            this.loadData();
        },
        merchandiseLabel(merchandise) {
            return [merchandise?.code, merchandise?.name]
                .filter(Boolean)
                .join(" - ") || "--";
        },
        unitLabel(unit) {
            return unit?.symbol || unit?.code || unit?.name || "--";
        },
        formatNumber(value, maximumFractionDigits = 6) {
            if (value === null || value === undefined || value === "") {
                return "--";
            }

            const number = Number(value);
            if (!Number.isFinite(number)) {
                return value;
            }

            return new Intl.NumberFormat(this.$i18n.locale, {
                maximumFractionDigits,
            }).format(number);
        },
        formatDelta(value) {
            const number = Number(value);
            if (!Number.isFinite(number)) {
                return "--";
            }

            return `${number > 0 ? "+" : ""}${this.formatNumber(number)}`;
        },
        formatDate(value) {
            return functionHelper.formatDate(value) || "--";
        },
        formatDateTime(value) {
            return functionHelper.formatDate(value, "DD/MM/YYYY HH:mm") || "--";
        },
        enumLabel(group, value) {
            if (!value) {
                return "--";
            }

            const key = `warehouse.inventory.${group}.${value}`;
            return this.$te(key) ? this.$t(key) : value;
        },
    },
};
