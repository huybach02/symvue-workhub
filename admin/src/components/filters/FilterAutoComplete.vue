<template>
    <v-autocomplete
        density="compact"
        variant="plain"
        :items="computedItems"
        multiple
        :label="title"
        hide-details
        clearable
        :menu-props="{ width: '350px' }"
        @update:model-value="onChange"
        @click:clear="handleClear"
    >
        <template #no-data>
            <div class="filter-autocomplete-state">
                <template v-if="isLoading">
                    <v-progress-circular
                        indeterminate
                        size="18"
                        width="2"
                        color="primary"
                    />
                </template>
                <span v-else class="text-body-2">
                    {{ $t("base.no_data") }}
                </span>
            </div>
        </template>
    </v-autocomplete>
</template>

<script>
import { getDataSelect } from "@/services/bases/getData";

export default {
    name: "FilterAutoComplete",
    props: {
        title: {
            type: String,
            default: "",
        },
        path: {
            type: String,
            default: "",
        },
        items: {
            type: Array,
            default: null,
        },
    },
    emits: ["update"],
    data() {
        return {
            listData: [],
            isLoading: false,
        };
    },
    computed: {
        computedItems() {
            if (this.items && this.items.length > 0) {
                return this.items;
            }
            if (this.listData.length > 0) {
                return this.listData;
            }
            return [];
        },
    },
    mounted() {
        if (this.path && (!this.items || this.items.length === 0)) {
            this.getItems();
        }
    },
    methods: {
        onChange(val) {
            this.$emit("update", {
                type: "includes",
                value: val,
            });
        },
        handleClear() {
            this.$emit("update", {
                type: "includes",
                value: [],
            });
        },
        async getItems() {
            this.isLoading = true;
            try {
                const res = (await getDataSelect(this.path)) ?? [];
                this.listData = res.map((item) => {
                    return {
                        title: item.label,
                        value: item.value,
                    };
                });
            } finally {
                this.isLoading = false;
            }
        },
    },
};
</script>

<style scoped>
.filter-autocomplete-state {
    min-height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 16px;
}
</style>
