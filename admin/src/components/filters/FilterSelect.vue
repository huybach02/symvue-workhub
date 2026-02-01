<template>
    <v-select
        density="compact"
        variant="plain"
        :items="items"
        multiple
        :label="title"
        hide-details
        clearable
        @update:model-value="onChange"
        @click:clear="handleClear"
    />
</template>

<script>
export default {
    name: "FilterSelect",
    props: {
        items: {
            type: Array,
            default: () => [],
        },
        title: {
            type: String,
            default: "",
        },
    },
    emits: ["update"],
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
    },
};
</script>
