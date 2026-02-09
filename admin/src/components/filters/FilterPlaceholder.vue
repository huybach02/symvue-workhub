<template>
    <v-text-field
        density="compact"
        variant="plain"
        :label="title"
        hide-details
        disabled
    />
</template>

<script>
export default {
    name: "FilterPlaceholder",
    props: {
        title: {
            type: String,
            default: "",
        },
    },
};
</script>
