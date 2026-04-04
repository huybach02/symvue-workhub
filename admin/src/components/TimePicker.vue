<template>
    <v-menu
        v-model="menu"
        :close-on-content-click="false"
        location="bottom start"
        offset="8"
        :disabled="disabled"
    >
        <template #activator="{ props: activatorProps }">
            <v-text-field
                :model-value="displayValue"
                :placeholder="placeholder"
                :error-messages="errorMessages"
                :disabled="disabled"
                :readonly="true"
                :clearable="clearable && !!modelValue && !readonly"
                :variant="variant"
                :density="density"
                prepend-inner-icon="mdi-clock-time-four-outline"
                v-bind="activatorProps"
                @click="handleActivatorClick"
                @click:clear="clearValue"
                @blur="emitBlur"
            />
        </template>

        <v-card min-width="340" rounded="xl" elevation="12">
            <v-card-item class="pb-3">
                <div
                    class="text-overline text-medium-emphasis font-weight-bold"
                >
                    Time
                </div>
                <div class="text-h6 font-weight-bold">
                    {{ headerTitle }}
                </div>
            </v-card-item>

            <v-divider />

            <v-card-text class="d-grid ga-4">
                <v-text-field
                    :model-value="inputValue"
                    label="HH:mm"
                    variant="outlined"
                    density="compact"
                    hide-details
                    maxlength="5"
                    @update:model-value="updateInputValue"
                    @blur="applyInputValue"
                    @keydown.enter.prevent="applyInputValue"
                />

                <v-row dense>
                    <v-col cols="6">
                        <div
                            class="text-overline text-medium-emphasis font-weight-bold mb-2"
                        >
                            Hour
                        </div>
                        <v-sheet
                            border
                            rounded="lg"
                            class="overflow-y-auto"
                            style="max-height: 240px; overflow-y: auto"
                        >
                            <v-list
                                density="compact"
                                bg-color="transparent"
                                class="py-1"
                                nav
                            >
                                <v-list-item
                                    v-for="hour in hours"
                                    :key="`hour-${hour}`"
                                    :active="selectedHour === hour"
                                    :base-color="
                                        selectedHour === hour
                                            ? 'primary'
                                            : undefined
                                    "
                                    rounded="lg"
                                    class="mx-1 mb-1"
                                    @click="selectHour(hour)"
                                >
                                    <v-list-item-title
                                        class="text-center font-weight-medium"
                                    >
                                        {{ hour }}
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-sheet>
                    </v-col>

                    <v-col cols="6">
                        <div
                            class="text-overline text-medium-emphasis font-weight-bold mb-2"
                        >
                            Minute
                        </div>
                        <v-sheet
                            border
                            rounded="lg"
                            class="overflow-y-auto"
                            style="max-height: 240px; overflow-y: auto"
                        >
                            <v-list
                                density="compact"
                                bg-color="transparent"
                                class="py-1"
                                nav
                            >
                                <v-list-item
                                    v-for="minute in minutes"
                                    :key="`minute-${minute}`"
                                    :active="selectedMinute === minute"
                                    :base-color="
                                        selectedMinute === minute
                                            ? 'primary'
                                            : undefined
                                    "
                                    rounded="lg"
                                    class="mx-1 mb-1"
                                    @click="selectMinute(minute)"
                                >
                                    <v-list-item-title
                                        class="text-center font-weight-medium"
                                    >
                                        {{ minute }}
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-sheet>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider />

            <v-card-actions
                class="justify-space-between align-center px-4 pb-4"
            >
                <div>
                    <v-btn variant="text" color="error" @click="clearValue">
                        Clear
                    </v-btn>
                    <v-btn variant="text" color="info" @click="selectNow">
                        Now
                    </v-btn>
                </div>
                <v-btn variant="text" color="primary" @click="applyAndClose">
                    Apply
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-menu>
</template>

<script>
import dayjs from "dayjs";

export default {
    name: "TimePicker",
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
        minuteStep: {
            type: Number,
            default: 5,
        },
    },
    emits: ["update:model-value", "blur"],
    data() {
        return {
            menu: false,
            selectedHour: "",
            selectedMinute: "",
            inputValue: "",
        };
    },
    computed: {
        displayValue() {
            return this.modelValue || "";
        },
        headerTitle() {
            return this.modelValue || this.placeholder || "Select time";
        },
        hours() {
            return Array.from({ length: 24 }, (_, index) =>
                String(index).padStart(2, "0"),
            );
        },
        minutes() {
            const step = Number(this.minuteStep) || 5;
            const values = [];

            for (let minute = 0; minute < 60; minute += step) {
                values.push(String(minute).padStart(2, "0"));
            }

            return values;
        },
    },
    watch: {
        modelValue: {
            handler() {
                this.syncFromModel();
            },
            immediate: true,
        },
        menu(value) {
            if (value) {
                this.syncFromModel();
                return;
            }

            this.emitBlur();
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
        syncFromModel() {
            const [hour = "", minute = ""] = String(
                this.modelValue || "",
            ).split(":");

            this.selectedHour = hour.padStart(2, "0").slice(0, 2);
            this.selectedMinute = minute.padStart(2, "0").slice(0, 2);
            this.inputValue =
                this.selectedHour && this.selectedMinute
                    ? `${this.selectedHour}:${this.selectedMinute}`
                    : "";
        },
        selectHour(hour) {
            this.selectedHour = hour;
            this.inputValue = this.selectedMinute
                ? `${hour}:${this.selectedMinute}`
                : hour;
            this.emitIfComplete(false);
        },
        selectMinute(minute) {
            this.selectedMinute = minute;
            this.inputValue = this.selectedHour
                ? `${this.selectedHour}:${minute}`
                : minute;
            this.emitIfComplete(true);
        },
        selectNow() {
            const now = dayjs();
            this.selectedHour = now.format("HH");
            this.selectedMinute = now.format("mm");
            this.inputValue = `${this.selectedHour}:${this.selectedMinute}`;
            this.emitValue(true);
        },
        clearValue() {
            this.selectedHour = "";
            this.selectedMinute = "";
            this.inputValue = "";
            this.$emit("update:model-value", "");
            this.menu = false;
            this.emitBlur();
        },
        updateInputValue(value) {
            const digits = String(value || "")
                .replace(/\D/g, "")
                .slice(0, 4);

            if (digits.length <= 2) {
                this.inputValue = digits;
                return;
            }

            this.inputValue = `${digits.slice(0, 2)}:${digits.slice(2)}`;
        },
        applyInputValue() {
            if (!this.inputValue) {
                this.clearValue();
                return false;
            }

            const match = this.inputValue.match(/^(\d{2}):(\d{2})$/);

            if (!match) {
                this.syncFromModel();
                return false;
            }

            const hour = Number(match[1]);
            const minute = Number(match[2]);

            if (hour > 23 || minute > 59) {
                this.syncFromModel();
                return false;
            }

            this.selectedHour = String(hour).padStart(2, "0");
            this.selectedMinute = String(minute).padStart(2, "0");
            this.inputValue = `${this.selectedHour}:${this.selectedMinute}`;
            this.emitValue(false);
            return true;
        },
        applyAndClose() {
            const applied = this.applyInputValue();

            if (applied) {
                this.menu = false;
                return;
            }

            if (!this.inputValue) {
                this.menu = false;
            }
        },
        emitIfComplete(closeMenu) {
            if (!this.selectedHour || !this.selectedMinute) {
                return;
            }

            this.emitValue(closeMenu);
        },
        emitValue(closeMenu) {
            const time = `${this.selectedHour}:${this.selectedMinute}`;
            this.$emit("update:model-value", time);

            if (closeMenu) {
                this.menu = false;
            }
        },
        emitBlur() {
            this.$emit("blur");
        },
    },
};
</script>
