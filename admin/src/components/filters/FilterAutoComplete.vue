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
    />
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
            const res = await getDataSelect(this.path);
            this.listData = res.map((item) => {
                return {
                    title: item.label,
                    value: item.value,
                };
            });
        },
    },
};
</script>
