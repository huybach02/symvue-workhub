<template>
    <div class="bg-white border-b py-2 px-2 d-flex justify-center">
        <v-timeline
            direction="horizontal"
            align="center"
            truncate-line="both"
            style="width: 100%; max-width: 900px"
            density="compact"
        >
            <v-timeline-item
                v-for="step in steps"
                :key="step.value"
                :dot-color="getStepColor(step.value)"
                :icon="getStepIcon(step.value)"
                fill-dot
            >
                <div class="text-center mt-n4">
                    <v-tooltip
                        location="top"
                        :text="getStepTooltip(step.value)"
                    >
                        <template #activator="{ props: tooltipProps }">
                            <v-btn
                                v-bind="tooltipProps"
                                size="small"
                                class="text-caption font-weight-bold text-none"
                                :variant="
                                    status === step.value ? 'tonal' : 'text'
                                "
                                :color="
                                    status === step.value
                                        ? 'primary'
                                        : 'grey-darken-1'
                                "
                                :loading="loadingValue === step.value"
                                :disabled="
                                    disabled ||
                                    (status !== step.value &&
                                        !isNextStep(step.value))
                                "
                                :style="
                                    status === step.value
                                        ? 'pointer-events: none;'
                                        : ''
                                "
                                @click="onStepClick(step.value)"
                            >
                                {{ $t(step.key) || step.label || step.value }}
                            </v-btn>
                        </template>
                    </v-tooltip>
                </div>
            </v-timeline-item>
        </v-timeline>
    </div>
</template>

<script>
export default {
    name: "StatusStepper",
    props: {
        status: {
            type: String,
            required: true,
        },
        steps: {
            type: Array,
            required: true,
        },
        loadingValue: {
            type: String,
            default: null,
        },
        colors: {
            type: Object,
            default: () => ({}),
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["change"],
    methods: {
        isStepCompleted(stepValue) {
            const currentIndex = this.steps.findIndex(
                (s) => s.value === this.status,
            );
            const stepIndex = this.steps.findIndex(
                (s) => s.value === stepValue,
            );
            return (
                stepIndex !== -1 &&
                currentIndex !== -1 &&
                stepIndex < currentIndex
            );
        },
        isNextStep(stepValue) {
            const currentIndex = this.steps.findIndex(
                (s) => s.value === this.status,
            );
            const stepIndex = this.steps.findIndex(
                (s) => s.value === stepValue,
            );
            return currentIndex !== -1 && stepIndex === currentIndex + 1;
        },
        getStepColor(stepValue) {
            if (this.status === stepValue) {
                return "primary";
            }
            if (this.isStepCompleted(stepValue)) {
                return "success";
            }
            return "grey-lighten-2";
        },
        getStepIcon(stepValue) {
            if (this.isStepCompleted(stepValue)) {
                return "mdi-check";
            }
            if (this.status === stepValue) {
                return "mdi-radiobox-marked";
            }
            return "mdi-circle-medium";
        },
        getStepTooltip(stepValue) {
            if (this.status === stepValue) {
                return (
                    this.$t("stock_receipt.stepper_tooltip.current") ||
                    "Trạng thái hiện tại"
                );
            }
            if (this.isStepCompleted(stepValue)) {
                return (
                    this.$t("stock_receipt.stepper_tooltip.completed") ||
                    "Đã hoàn thành"
                );
            }
            if (this.isNextStep(stepValue)) {
                return (
                    this.$t("stock_receipt.stepper_tooltip.next") ||
                    "Bấm để chuyển sang trạng thái này"
                );
            }
            return (
                this.$t("stock_receipt.stepper_tooltip.locked") ||
                "Chưa thể thực hiện bước này"
            );
        },
        onStepClick(value) {
            if (
                this.status !== value &&
                !this.disabled &&
                this.isNextStep(value)
            ) {
                this.$emit("change", value);
            }
        },
    },
};
</script>

<style scoped>
.border-b {
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}
</style>
