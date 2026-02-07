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
        <v-date-picker
            :model-value="internalValue"
            no-title
            scrollable
            @update:model-value="handleDateChange"
        />
    </v-menu>
</template>

<script>
export default {
    name: "DatePicker",
    inheritAttrs: false,
    props: {
        // Giá trị của date picker (dạng YYYY-MM-DD hoặc Date object)
        modelValue: {
            type: [String, Date, null],
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
            default: "mdi-calendar",
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
        // Format hiển thị (dd/mm/yyyy hoặc yyyy/mm/dd)
        displayFormat: {
            type: String,
            default: "dd/mm/yyyy",
            validator: (value) => ["dd/mm/yyyy", "yyyy/mm/dd"].includes(value),
        },
    },
    emits: ["update:modelValue", "blur"],
    data() {
        return {
            menuOpen: false,
        };
    },
    computed: {
        // Giá trị nội bộ để truyền vào v-date-picker
        internalValue() {
            if (!this.modelValue) return null;
            // Nếu đã là string YYYY-MM-DD thì return luôn
            if (
                typeof this.modelValue === "string" &&
                this.modelValue.match(/^\d{4}-\d{2}-\d{2}$/)
            ) {
                return this.modelValue;
            }
            // Nếu là Date object, convert sang YYYY-MM-DD
            const d = new Date(this.modelValue);
            if (isNaN(d.getTime())) return null;
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, "0");
            const day = String(d.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        },
        // Giá trị hiển thị trong text field
        displayValue() {
            if (!this.modelValue) return "";
            return this.formatDate(this.modelValue);
        },
    },
    watch: {
        // Watch menuOpen để emit blur khi menu đóng
        menuOpen(newVal, oldVal) {
            // Khi menu đóng (từ true -> false), emit blur event
            if (oldVal === true && newVal === false) {
                this.$emit("blur");
            }
        },
    },
    methods: {
        // Format date theo displayFormat prop
        formatDate(date) {
            if (!date) return "";

            let d;
            // Xử lý nếu date là string dạng ISO (từ v-date-picker)
            if (typeof date === "string") {
                // Nếu là dạng YYYY-MM-DD
                if (date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    const [year, month, day] = date.split("-");
                    return this.displayFormat === "dd/mm/yyyy"
                        ? `${day}/${month}/${year}`
                        : `${year}/${month}/${day}`;
                }
                // Parse các dạng khác
                d = new Date(date);
            } else {
                // Nếu là Date object hoặc timestamp
                d = new Date(date);
            }

            // Kiểm tra date có hợp lệ không
            if (isNaN(d.getTime())) return "";

            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, "0");
            const day = String(d.getDate()).padStart(2, "0");

            return this.displayFormat === "dd/mm/yyyy"
                ? `${day}/${month}/${year}`
                : `${year}/${month}/${day}`;
        },
        // Xử lý khi chọn ngày từ date picker
        handleDateChange(newDate) {
            // Convert Date object sang string YYYY-MM-DD
            let dateString = "";
            if (newDate) {
                const d = new Date(newDate);
                if (!isNaN(d.getTime())) {
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, "0");
                    const day = String(d.getDate()).padStart(2, "0");
                    dateString = `${year}-${month}-${day}`;
                }
            }
            // Emit giá trị mới
            this.$emit("update:modelValue", dateString);
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
