<template>
    <v-overlay
        :model-value="modelValue"
        class="align-center justify-center"
        persistent
    >
        <div class="text-center">
            <v-progress-circular
                indeterminate
                size="80"
                width="6"
                color="primary"
            />
            <div v-if="text" class="mt-4 text-h6 text-white">
                {{ text }}
            </div>
        </div>
    </v-overlay>
</template>

<script>
export default {
    name: "Loading",
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        text: {
            type: String,
            default: "",
        },
    },
};
</script>

<style>
.v-overlay :deep(.v-overlay__scrim) {
    background-color: white !important;
    opacity: 0.9 !important;
}

.v-overlay {
    z-index: 9999;
}
</style>
