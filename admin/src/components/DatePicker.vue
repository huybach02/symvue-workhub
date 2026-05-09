<template>
    <v-menu
        v-model="menu"
        :close-on-content-click="false"
        location="bottom start"
        offset="8"
        :disabled="disabled"
    >
        <template #activator="{ props: activatorProps }">
            <v-btn
                v-if="iconOnly"
                icon
                :disabled="disabled"
                :variant="iconVariant"
                :density="density"
                v-bind="activatorProps"
                @click="handleActivatorClick"
            >
                <v-icon>{{ icon }}</v-icon>
            </v-btn>

            <v-text-field
                v-else
                :model-value="displayValue"
                :placeholder="placeholder"
                :error-messages="errorMessages"
                :disabled="disabled"
                :readonly="true"
                :clearable="clearable && !!modelValue && !readonly"
                :variant="variant"
                :density="density"
                prepend-inner-icon="mdi-calendar-blank-outline"
                v-bind="activatorProps"
                @click="handleActivatorClick"
                @click:clear="clearValue"
                @blur="emitBlur"
            />
        </template>

        <v-card width="320" rounded="xl" elevation="12">
            <v-card-item class="pb-3">
                <div
                    class="text-overline text-medium-emphasis font-weight-bold"
                >
                    Date
                </div>
                <div class="text-h6 font-weight-bold">
                    {{ headerTitle }}
                </div>
            </v-card-item>

            <v-divider />

            <v-date-picker
                :model-value="pickerValue"
                color="primary"
                hide-header
                show-adjacent-months
                width="320"
                @update:model-value="handleDateSelect"
            />

            <v-divider />

            <v-card-actions class="justify-end px-4 pb-4">
                <v-btn variant="text" color="error" @click="clearValue">
                    Clear
                </v-btn>
                <v-btn variant="text" color="primary" @click="selectToday">
                    Today
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-menu>
</template>

<script>
import dayjs from "dayjs";

export default {
    name: "DatePicker",
    props: {
        modelValue: {
            type: String,
            default: "",
        },
        errorMessages: {
            type: [String, Array],
            default: "",
        },
        placeholder: {
            type: String,
            default: "",
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        readonly: {
            type: Boolean,
            default: false,
        },
        clearable: {
            type: Boolean,
            default: true,
        },
        variant: {
            type: String,
            default: "outlined",
        },
        density: {
            type: String,
            default: "default",
        },
        iconOnly: {
            type: Boolean,
            default: false,
        },
        icon: {
            type: String,
            default: "mdi-calendar-blank-outline",
        },
        iconVariant: {
            type: String,
            default: "text",
        },
    },
    emits: ["update:model-value", "blur"],
    data() {
        return {
            menu: false,
        };
    },
    computed: {
        displayValue() {
            if (!this.modelValue) {
                return "";
            }

            const date = dayjs(this.modelValue);

            if (!date.isValid()) {
                return this.modelValue;
            }

            return date.format("DD/MM/YYYY");
        },
        headerTitle() {
            if (!this.modelValue) {
                return this.placeholder || "Select date";
            }

            const date = dayjs(this.modelValue);

            if (!date.isValid()) {
                return this.modelValue;
            }

            return date.format("dddd, DD MMM YYYY");
        },
        pickerValue() {
            return this.normalizeDateValue(this.modelValue);
        },
    },
    methods: {
        handleActivatorClick(event) {
            if (this.readonly) {
                event.preventDefault();
                return;
            }

            this.menu = true;
        },
        handleDateSelect(value) {
            const normalizedValue = this.normalizeDateValue(value);
            this.$emit("update:model-value", normalizedValue);
            this.menu = false;
            this.emitBlur();
        },
        clearValue() {
            this.$emit("update:model-value", "");
            this.menu = false;
            this.emitBlur();
        },
        selectToday() {
            const today = dayjs().format("YYYY-MM-DD");
            this.$emit("update:model-value", today);
            this.menu = false;
            this.emitBlur();
        },
        normalizeDateValue(value) {
            if (Array.isArray(value)) {
                return this.normalizeDateValue(value[0]);
            }

            if (!value) {
                return "";
            }

            if (typeof value === "string") {
                return dayjs(value).isValid()
                    ? dayjs(value).format("YYYY-MM-DD")
                    : value;
            }

            const date = dayjs(value);

            return date.isValid() ? date.format("YYYY-MM-DD") : "";
        },
        emitBlur() {
            this.$emit("blur");
        },
    },
};
</script>
