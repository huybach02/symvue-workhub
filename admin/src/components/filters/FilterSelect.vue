<template>
    <v-select
        density="compact"
        variant="plain"
        :items="items"
        multiple
        :label="title"
        hide-details
        @update:model-value="onChange"
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
    },
};
</script>
