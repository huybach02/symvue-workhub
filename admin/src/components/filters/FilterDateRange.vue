<template>
    <v-menu v-model="menu" :close-on-content-click="false">
        <template #activator="{ props }">
            <v-text-field
                v-bind="props"
                density="compact"
                variant="plain"
                hide-details
                readonly
                :label="title || $t('filter.date_range.title')"
                :model-value="displayValue"
                class="filter-date-range"
            />
        </template>

        <v-card min-width="320" class="pa-4">
            <v-select
                v-model="selectedType"
                :items="typeOptions"
                :label="$t('filter.date_range.select_type')"
                density="compact"
                variant="outlined"
                item-title="title"
                item-value="value"
                class="mb-4"
                hide-details
                clearable
                @update:model-value="onTypeChange"
            />

            <!-- Range Filter -->
            <div v-if="selectedType === 'between'">
                <div class="mb-3">
                    <label class="text-caption text-grey-darken-1 mb-1 d-block">
                        {{ $t("filter.date_range.from_date") }}
                    </label>
                    <v-text-field
                        v-model="dateFrom"
                        type="date"
                        density="compact"
                        variant="outlined"
                        hide-details
                        class="mb-2"
                        clearable
                        @update:model-value="onDateChange"
                    />
                </div>

                <div>
                    <label class="text-caption text-grey-darken-1 mb-1 d-block">
                        {{ $t("filter.date_range.to_date") }}
                    </label>
                    <v-text-field
                        v-model="dateTo"
                        type="date"
                        density="compact"
                        variant="outlined"
                        hide-details
                        clearable
                        @update:model-value="onDateChange"
                    />
                </div>
            </div>

            <!-- Single Date filters -->
            <div v-else>
                <label class="text-caption text-grey-darken-1 mb-1 d-block">
                    {{ $t("filter.date_range.date") }}
                </label>
                <v-text-field
                    v-model="dateSingle"
                    type="date"
                    density="compact"
                    variant="outlined"
                    hide-details
                    clearable
                    @update:model-value="onDateChange"
                />
            </div>
        </v-card>
    </v-menu>
</template>

<script>
export default {
    name: "FilterDateRange",
    props: {
        value: {
            type: Object,
            default: () => ({}),
        },
        title: {
            type: String,
            default: "",
        },
    },
    emits: ["update"],
    data() {
        return {
            menu: false,
            // Default to 'equal_to' (Ngày)
            selectedType: "equal_to",
            dateSingle: null,
            dateFrom: null,
            dateTo: null,
            typeOptions: [],
        };
    },
    computed: {
        displayValue() {
            if (this.selectedType === "between") {
                if (this.dateFrom && this.dateTo) {
                    return `${this.formatDate(this.dateFrom)} - ${this.formatDate(this.dateTo)}`;
                }
                if (this.dateFrom)
                    return `${this.$t("filter.date_range.display.from")} ${this.formatDate(this.dateFrom)}`;
                if (this.dateTo)
                    return `${this.$t("filter.date_range.display.to")} ${this.formatDate(this.dateTo)}`;
                return "";
            }

            if (this.dateSingle) {
                const prefixMap = {
                    equal_to: "",
                    less_than: "< ",
                    greater_than: "> ",
                    less_than_or_equal_to: "<= ",
                    greater_than_or_equal_to: ">= ",
                };
                return `${prefixMap[this.selectedType] || ""}${this.formatDate(this.dateSingle)}`;
            }

            return "";
        },
    },
    watch: {
        value: {
            handler(val) {
                if (!val || Object.keys(val).length === 0) {
                    this.resetLocalState();
                    return;
                }

                if (val.type) {
                    this.selectedType = val.type;
                    if (val.type === "between" && Array.isArray(val.value)) {
                        this.dateFrom = val.value[0] || null;
                        this.dateTo = val.value[1] || null;
                        this.dateSingle = null;
                    } else {
                        this.dateSingle = val.value;
                        this.dateFrom = null;
                        this.dateTo = null;
                    }
                }
            },
            deep: true,
            immediate: true,
        },
    },
    mounted() {
        // Khởi tạo typeOptions với i18n
        this.typeOptions = [
            {
                title: this.$t("filter.date_range.types.equal_to"),
                value: "equal_to",
            },
            {
                title: this.$t("filter.date_range.types.less_than"),
                value: "less_than",
            },
            {
                title: this.$t("filter.date_range.types.greater_than"),
                value: "greater_than",
            },
            {
                title: this.$t("filter.date_range.types.less_than_or_equal_to"),
                value: "less_than_or_equal_to",
            },
            {
                title: this.$t(
                    "filter.date_range.types.greater_than_or_equal_to",
                ),
                value: "greater_than_or_equal_to",
            },
            {
                title: this.$t("filter.date_range.types.between"),
                value: "between",
            },
        ];
    },
    methods: {
        resetLocalState() {
            this.selectedType = "equal_to";
            this.dateSingle = null;
            this.dateFrom = null;
            this.dateTo = null;
        },
        onTypeChange() {
            this.dateSingle = null;
            this.dateFrom = null;
            this.dateTo = null;
            this.emitUpdate();
        },
        onDateChange() {
            this.emitUpdate();
        },
        emitUpdate() {
            let payloadValue = null;

            if (this.selectedType === "between") {
                if (!this.dateFrom && !this.dateTo) {
                    payloadValue = null;
                } else {
                    let finalDateFrom = this.dateFrom;
                    let finalDateTo = this.dateTo;

                    // Nếu chỉ có dateFrom mà không có dateTo, tự động set dateTo = hôm nay
                    if (this.dateFrom && !this.dateTo) {
                        const today = new Date();
                        const year = today.getFullYear();
                        const month = String(today.getMonth() + 1).padStart(
                            2,
                            "0",
                        );
                        const day = String(today.getDate()).padStart(2, "0");
                        finalDateTo = `${year}-${month}-${day}`;
                        // Cập nhật UI để hiển thị ngày đã tự động điền
                        this.dateTo = finalDateTo;
                    }

                    // Nếu chỉ có dateTo mà không có dateFrom, giữ dateFrom = null
                    // Backend sẽ xử lý lấy tất cả từ quá khứ đến dateTo

                    payloadValue = [finalDateFrom, finalDateTo];
                }
            } else {
                payloadValue = this.dateSingle;
            }

            if (
                !payloadValue ||
                (Array.isArray(payloadValue) &&
                    !payloadValue[0] &&
                    !payloadValue[1])
            ) {
                this.$emit("update", {});
                return;
            }

            this.$emit("update", {
                type: this.selectedType,
                value: payloadValue,
            });
        },
        formatDate(dateString) {
            if (!dateString) return "";
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, "0");
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        },
    },
};
</script>

<style scoped></style>
