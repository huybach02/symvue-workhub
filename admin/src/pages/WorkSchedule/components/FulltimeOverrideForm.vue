<template>
    <div>
        <VeeForm
            ref="formRef"
            :key="formKey"
            as="form"
            :validation-schema="validationSchema"
            :initial-values="initialValues"
            @submit="handleSubmit"
        >
            <v-row>
                <v-col cols="12" md="6">
                    <VeeField
                        v-slot="{
                            field,
                            errorMessage,
                            handleChange,
                            handleBlur,
                        }"
                        name="startDate"
                    >
                        <div class="mb-2">
                            {{ $t("field.ngay_bat_dau") }}
                            <span class="text-red"> * </span>
                        </div>
                        <DatePicker
                            :model-value="field.value"
                            :error-messages="errorMessage"
                            :placeholder="`${$t('base.enter')} ${$t('field.ngay_bat_dau')}`"
                            @update:model-value="
                                (val) => {
                                    handleChange(val);
                                    this.formValues.startDate = val;
                                }
                            "
                            @blur="handleBlur"
                        />
                    </VeeField>
                </v-col>

                <v-col cols="12" md="6">
                    <VeeField
                        v-slot="{
                            field,
                            errorMessage,
                            handleChange,
                            handleBlur,
                        }"
                        name="endDate"
                    >
                        <div class="mb-2">
                            {{ $t("field.ngay_ket_thuc") }}
                            <span class="text-red"> * </span>
                        </div>
                        <DatePicker
                            :model-value="field.value"
                            :error-messages="errorMessage"
                            :placeholder="`${$t('base.enter')} ${$t('field.ngay_ket_thuc')}`"
                            @update:model-value="
                                (val) => {
                                    handleChange(val);
                                    this.formValues.endDate = val;
                                }
                            "
                            @blur="handleBlur"
                        />
                    </VeeField>
                </v-col>

                <v-col cols="12" md="6">
                    <VeeField
                        v-slot="{
                            field,
                            errorMessage,
                            handleChange,
                            handleBlur,
                        }"
                        name="startTime"
                    >
                        <div class="mb-2">
                            {{ $t("field.thoi_gian_bat_dau") }}
                            <span class="text-red"> * </span>
                        </div>
                        <TimePicker
                            :model-value="field.value"
                            :error-messages="errorMessage"
                            :placeholder="`${$t('base.enter')} ${$t('field.thoi_gian_bat_dau')}`"
                            @update:model-value="
                                (val) => {
                                    handleChange(val);
                                    this.formValues.startTime = val;
                                }
                            "
                            @blur="handleBlur"
                        />
                    </VeeField>
                </v-col>

                <v-col cols="12" md="6">
                    <VeeField
                        v-slot="{
                            field,
                            errorMessage,
                            handleChange,
                            handleBlur,
                        }"
                        name="endTime"
                    >
                        <div class="mb-2">
                            {{ $t("field.thoi_gian_ket_thuc") }}
                            <span class="text-red"> * </span>
                        </div>
                        <TimePicker
                            :model-value="field.value"
                            :error-messages="errorMessage"
                            :placeholder="`${$t('base.enter')} ${$t('field.thoi_gian_ket_thuc')}`"
                            @update:model-value="
                                (val) => {
                                    handleChange(val);
                                    this.formValues.endTime = val;
                                }
                            "
                            @blur="handleBlur"
                        />
                    </VeeField>
                </v-col>

                <!-- Special Days Section -->
                <v-col v-if="hasSpecialDays" cols="12">
                    <v-divider class="my-4" />

                    <div class="text-subtitle-1 font-weight-bold mb-3">
                        {{ $t("work_schedule.special_days_title") }}
                    </div>

                    <!-- Radio Options -->
                    <v-radio-group v-model="weekendOption" class="mb-4">
                        <v-radio value="keep" color="primary">
                            <template #label>
                                <span class="text-body-2">
                                    {{
                                        $t("work_schedule.keep_weekend_holiday")
                                    }}
                                </span>
                            </template>
                        </v-radio>
                        <v-radio value="work" color="primary">
                            <template #label>
                                <span class="text-body-2">
                                    {{
                                        $t("work_schedule.work_weekend_holiday")
                                    }}
                                </span>
                            </template>
                        </v-radio>
                    </v-radio-group>

                    <div class="text-caption text-grey mb-2">
                        {{
                            weekendOption === "keep"
                                ? $t("work_schedule.days_will_keep_off")
                                : $t("work_schedule.select_days_to_work")
                        }}
                    </div>

                    <v-row dense>
                        <v-col
                            v-for="day in specialDays"
                            :key="day.date"
                            cols="6"
                            sm="4"
                            md="3"
                        >
                            <v-card
                                :variant="
                                    isDateSelected(day.date)
                                        ? 'flat'
                                        : 'outlined'
                                "
                                :color="
                                    isDateSelected(day.date)
                                        ? 'primary'
                                        : undefined
                                "
                                class="special-day-card"
                                :class="{
                                    'disabled-card': isWorkWeekendDisabled,
                                }"
                                @click="toggleDate(day.date)"
                            >
                                <v-card-text class="pa-2">
                                    <div class="d-flex align-center">
                                        <v-checkbox
                                            :model-value="
                                                isDateSelected(day.date)
                                            "
                                            :disabled="isWorkWeekendDisabled"
                                            hide-details
                                            density="compact"
                                            class="ma-0 pa-0"
                                            @click.stop
                                        />
                                        <div class="ms-2">
                                            <div
                                                class="text-caption font-weight-medium"
                                            >
                                                {{ day.dayLabel }}
                                            </div>
                                            <div class="text-caption">
                                                {{ formatDate(day.date) }}
                                            </div>
                                            <div
                                                v-if="day.holidayName"
                                                class="text-caption text-warning"
                                            >
                                                {{ day.holidayName }}
                                            </div>
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <div
                        v-if="
                            weekendOption === 'work' &&
                            selectedSpecialDates.length === 0
                        "
                        class="text-caption text-error mt-2"
                    >
                        {{ $t("work_schedule.please_select_at_least_one_day") }}
                    </div>
                </v-col>

                <v-col cols="12">
                    <div class="d-flex justify-end ga-2">
                        <v-btn color="primary" type="submit" :loading="loading">
                            {{ submitButtonText }}
                        </v-btn>
                    </div>
                </v-col>
            </v-row>
        </VeeForm>

        <ConfirmDialog
            v-model="showConfirm"
            :message="messageConfirm"
            :loading="loadingConfirm"
            @confirm="submitForm"
            @cancel="showConfirm = false"
        />
    </div>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { workScheduleOverrideSchema } from "@/utils/schemas/workScheduleOverride";
import DatePicker from "@/components/DatePicker.vue";
import TimePicker from "@/components/TimePicker.vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { mapActions } from "vuex";

export default {
    components: {
        VeeForm,
        VeeField,
        DatePicker,
        TimePicker,
        ConfirmDialog,
    },
    props: {
        userSelected: {
            type: Object,
            default: null,
        },
        submitButtonText: {
            type: String,
            default: "Lưu",
        },
    },
    emits: ["submit", "cancel"],
    data() {
        return {
            validationSchema: workScheduleOverrideSchema,
            initialValues: {
                startDate: "",
                endDate: "",
                startTime: "",
                endTime: "",
            },
            loading: false,
            loadingConfirm: false,
            showConfirm: false,
            valuesToSubmit: null,
            messageConfirm: "",
            formValues: {
                startDate: "",
                endDate: "",
                startTime: "",
                endTime: "",
            },
            specialDays: [],
            weekendOption: "keep",
            selectedSpecialDates: [],
            loadingSpecialDays: false,
        };
    },
    computed: {
        formKey() {
            return this.userSelected?.id ?? "new";
        },
        hasSpecialDays() {
            return this.specialDays.length > 0;
        },
        isWorkWeekendDisabled() {
            return this.weekendOption === "keep";
        },
    },
    watch: {
        "formValues.startDate"(newVal) {
            if (newVal && this.formValues.endDate) {
                this.fetchSpecialDays();
            }
        },
        "formValues.endDate"(newVal) {
            if (newVal && this.formValues.startDate) {
                this.fetchSpecialDays();
            }
        },
    },
    mounted() {
        this.formValues = { ...this.initialValues };
        if (this.formValues.startDate && this.formValues.endDate) {
            this.fetchSpecialDays();
        }
    },
    methods: {
        ...mapActions("workSchedule", {
            checkFulltimeOverride: "checkFulltimeOverride",
            createFulltimeOverride: "createFulltimeOverride",
            fetchSpecialDaysAction: "fetchSpecialDays",
        }),
        async handleSubmit(values) {
            this.valuesToSubmit = values;
            this.loading = true;
            const response = await this.checkFulltimeOverride({
                userId: this.userSelected?.id,
                startDate: values.startDate,
                endDate: values.endDate,
            });

            if (response) {
                if (!response.hasOverlap && !response.message) {
                    await this.submitForm();
                } else {
                    this.showConfirm = true;
                    this.messageConfirm = response.message;
                }
            }

            this.loading = false;
        },
        handleCancel() {
            this.$emit("cancel");
        },
        async submitForm() {
            this.loadingConfirm = true;
            await this.createFulltimeOverride({
                userId: this.userSelected?.id,
                startDate: this.valuesToSubmit.startDate,
                endDate: this.valuesToSubmit.endDate,
                startTime: this.valuesToSubmit.startTime,
                endTime: this.valuesToSubmit.endTime,
                weekendOption: this.weekendOption,
                selectedDates:
                    this.weekendOption === "work"
                        ? this.selectedSpecialDates
                        : [],
            });
            this.$emit("cancel");
            this.valuesToSubmit = null;
            this.loadingConfirm = false;
        },
        async fetchSpecialDays() {
            this.loadingSpecialDays = true;
            const data = await this.fetchSpecialDaysAction({
                startDate: this.formValues.startDate,
                endDate: this.formValues.endDate,
            });
            this.specialDays = data;

            this.selectedSpecialDates = [];
            this.loadingSpecialDays = false;
        },
        isDateSelected(date) {
            return this.selectedSpecialDates.includes(date);
        },
        toggleDate(date) {
            if (this.isWorkWeekendDisabled) return;

            const index = this.selectedSpecialDates.indexOf(date);
            if (index > -1) {
                this.selectedSpecialDates.splice(index, 1);
            } else {
                this.selectedSpecialDates.push(date);
            }
        },
        formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString("vi-VN", {
                day: "2-digit",
                month: "2-digit",
            });
        },
    },
};
</script>

<style scoped>
.special-day-card {
    cursor: pointer;
    transition: all 0.2s ease;
}

.special-day-card:hover:not(.disabled-card) {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.disabled-card {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
