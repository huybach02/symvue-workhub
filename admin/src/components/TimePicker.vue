<template>
    <v-menu
        v-model="menuOpen"
        :close-on-content-click="false"
        transition="scale-transition"
        offset-y
        min-width="auto"
    >
        <template #activator="{ props: menuProps }">
            <v-text-field
                v-bind="{ ...menuProps, ...$attrs }"
                :model-value="displayValue"
                :label="label"
                :error-messages="errorMessages"
                :readonly="readonly"
                :disabled="disabled"
                :variant="variant"
                :density="density"
                :prepend-inner-icon="prependInnerIcon"
                :append-inner-icon="appendInnerIcon"
                :clearable="clearable"
                @click:clear="handleClear"
            >
                <template v-if="$slots.label" #label>
                    <slot name="label" />
                </template>
            </v-text-field>
        </template>
        <v-time-picker
            :model-value="internalValue"
            format="24hr"
            scrollable
            @update:model-value="handleTimeChange"
        />
    </v-menu>
</template>

<script>
export default {
    name: "TimePicker",
    inheritAttrs: false,
    props: {
        // Giá trị của time picker (dạng HH:mm hoặc HH:mm:ss)
        modelValue: {
            type: [String, null],
            default: null,
        },
        // Label của input
        label: {
            type: String,
            default: "",
        },
        // Error messages từ VeeValidate
        errorMessages: {
            type: [String, Array],
            default: "",
        },
        // Readonly state
        readonly: {
            type: Boolean,
            default: true,
        },
        // Disabled state
        disabled: {
            type: Boolean,
            default: false,
        },
        // Variant của v-text-field
        variant: {
            type: String,
            default: "outlined",
        },
        // Density của v-text-field
        density: {
            type: String,
            default: "default",
        },
        // Icon bên trái
        prependInnerIcon: {
            type: String,
            default: "mdi-clock-outline",
        },
        // Icon bên phải
        appendInnerIcon: {
            type: String,
            default: "",
        },
        // Cho phép clear
        clearable: {
            type: Boolean,
            default: false,
        },
        // Có hiển thị giây không
        useSeconds: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            menuOpen: false,
        };
    },
    computed: {
        // Giá trị nội bộ để truyền vào v-time-picker
        internalValue() {
            if (!this.modelValue) return null;
            // v-time-picker chấp nhận HH:mm hoặc HH:mm:ss
            return this.modelValue;
        },
        // Giá trị hiển thị trong text field
        displayValue() {
            if (!this.modelValue) return "";
            return this.formatTime(this.modelValue);
        },
    },
    methods: {
        // Format time theo useSeconds prop
        formatTime(time) {
            if (!time) return "";

            // Nếu time là HH:mm:ss nhưng không cần giây
            if (!this.useSeconds && time.match(/^\d{2}:\d{2}:\d{2}$/)) {
                return time.substring(0, 5); // Chỉ lấy HH:mm
            }

            return time;
        },
        // Xử lý khi chọn giờ từ time picker
        handleTimeChange(newTime) {
            if (!newTime) {
                this.$emit("update:modelValue", "");
                this.menuOpen = false;
                return;
            }

            // v-time-picker trả về string HH:mm hoặc HH:mm:ss
            let timeString = newTime;

            // Nếu không cần giây và có giây, bỏ phần giây
            if (!this.useSeconds && timeString.length > 5) {
                timeString = timeString.substring(0, 5);
            }

            // Emit giá trị mới
            this.$emit("update:modelValue", timeString);
            // Đóng menu
            this.menuOpen = false;
        },
        // Xử lý khi clear
        handleClear() {
            this.$emit("update:modelValue", "");
        },
    },
};
</script>

<style scoped></style>
