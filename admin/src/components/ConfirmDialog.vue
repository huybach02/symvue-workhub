<template>
    <v-dialog
        v-model="isVisible"
        max-width="500"
        persistent
        scrim="rgba(0, 0, 0, 0.5)"
    >
        <v-card>
            <!-- Header -->
            <v-card-title class="text-h5 bg-error">
                <v-icon :icon="icon" :color="iconColor" class="mr-2" />
                {{ displayTitle }}
            </v-card-title>

            <!-- Content -->
            <v-card-text class="pt-4">
                <p class="text-body-1">{{ displayMessage }}</p>
            </v-card-text>

            <!-- Actions -->
            <v-card-actions class="px-4 pb-4">
                <v-spacer />
                <v-btn
                    :color="cancelColor"
                    :disabled="loading"
                    variant="text"
                    @click="handleCancel"
                >
                    {{ displayCancelText }}
                </v-btn>
                <v-btn
                    :color="confirmColor"
                    :loading="loading"
                    variant="elevated"
                    @click="handleConfirm"
                >
                    {{ displayConfirmText }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
export default {
    name: "ConfirmDialog",

    props: {
        // Hiển thị/ẩn dialog
        modelValue: {
            type: Boolean,
            default: false,
        },
        // Tiêu đề dialog
        title: {
            type: String,
            default: "",
        },
        // Nội dung thông báo
        message: {
            type: String,
            default: "",
        },
        // Icon hiển thị
        icon: {
            type: String,
            default: "mdi-help-circle",
        },
        // Màu icon
        iconColor: {
            type: String,
            default: "white",
        },
        // Text nút xác nhận
        confirmText: {
            type: String,
            default: "",
        },
        // Màu nút xác nhận
        confirmColor: {
            type: String,
            default: "error",
        },
        // Text nút hủy
        cancelText: {
            type: String,
            default: "",
        },
        // Màu nút hủy
        cancelColor: {
            type: String,
            default: "grey",
        },
        // Trạng thái loading
        loading: {
            type: Boolean,
            default: false,
        },
    },

    emits: ["update:modelValue", "confirm", "cancel"],

    computed: {
        isVisible: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
        displayTitle() {
            return this.title || this.$t("confirm_dialog.default_title");
        },
        displayMessage() {
            return this.message || this.$t("confirm_dialog.default_message");
        },
        displayConfirmText() {
            return this.confirmText || this.$t("confirm_dialog.confirm_button");
        },
        displayCancelText() {
            return this.cancelText || this.$t("confirm_dialog.cancel_button");
        },
    },

    methods: {
        handleConfirm() {
            this.$emit("confirm");
        },

        handleCancel() {
            this.$emit("cancel");
            this.isVisible = false;
        },
    },
};
</script>

<style scoped>
/* Tùy chỉnh style nếu cần */
</style>
