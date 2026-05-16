<template>
    <v-dialog v-model="model" max-width="900" persistent scrollable>
        <v-card class="position-relative">
            <v-btn
                icon="mdi-close"
                variant="text"
                size="small"
                class="position-absolute"
                style="top: 8px; right: 8px"
                @click="model = false"
            />

            <v-card-title class="pr-12">Them chuc vu tam</v-card-title>

            <v-divider />

            <v-card-text>
                <VeeForm
                    :initial-values="initialValues"
                    :validation-schema="validationSchema"
                    as="div"
                    v-slot="{ setFieldValue, handleSubmit }"
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
                                name="departmentId"
                            >
                                <div class="mb-2">
                                    {{ $t("field.bo_phan") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-autocomplete
                                    :model-value="field.value"
                                    :items="departmentOptions"
                                    item-title="label"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    clearable
                                    :loading="departmentOptionsLoading"
                                    :placeholder="`${$t('base.enter')} ${$t('field.bo_phan')}`"
                                    @update:model-value="
                                        handleDepartmentChange(
                                            $event,
                                            handleChange,
                                            handleBlur,
                                            setFieldValue,
                                        )
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
                                name="positionId"
                            >
                                <div class="mb-2">
                                    {{ $t("field.chuc_vu") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-select
                                    :model-value="field.value"
                                    :items="positionOptions"
                                    item-title="name"
                                    item-value="id"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    clearable
                                    :loading="positionOptionsLoading"
                                    :disabled="!selectedDepartmentId"
                                    :placeholder="`${$t('base.enter')} ${$t('field.chuc_vu')}`"
                                    @update:model-value="
                                        handlePositionChange(
                                            $event,
                                            handleChange,
                                            handleBlur,
                                        )
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
                                name="startTempDate"
                            >
                                <div class="mb-2">
                                    {{ $t("field.start_temp_date") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <DatePicker
                                    :model-value="field.value"
                                    :error-messages="errorMessage"
                                    :placeholder="`${$t('base.enter')} ${$t('field.start_temp_date')}`"
                                    @update:model-value="
                                        (value) => {
                                            handleChange(value);
                                            handleBlur();
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
                                name="startTempTime"
                            >
                                <div class="mb-2">
                                    {{ $t("field.start_temp_time") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <TimePicker
                                    :model-value="field.value"
                                    :error-messages="errorMessage"
                                    :placeholder="`${$t('base.enter')} ${$t('field.start_temp_time')}`"
                                    @update:model-value="
                                        (value) => {
                                            handleChange(value);
                                            handleBlur();
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
                                name="endTempDate"
                            >
                                <div class="mb-2">
                                    {{ $t("field.end_temp_date") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <DatePicker
                                    :model-value="field.value"
                                    :error-messages="errorMessage"
                                    :placeholder="`${$t('base.enter')} ${$t('field.end_temp_date')}`"
                                    @update:model-value="
                                        (value) => {
                                            handleChange(value);
                                            handleBlur();
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
                                name="endTempTime"
                            >
                                <div class="mb-2">
                                    {{ $t("field.end_temp_time") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <TimePicker
                                    :model-value="field.value"
                                    :error-messages="errorMessage"
                                    :placeholder="`${$t('base.enter')} ${$t('field.end_temp_time')}`"
                                    @update:model-value="
                                        (value) => {
                                            handleChange(value);
                                            handleBlur();
                                        }
                                    "
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>
                    </v-row>

                    <div class="d-flex justify-end mt-4">
                        <v-btn
                            color="primary"
                            variant="flat"
                            :loading="loading"
                            :disabled="loading"
                            @click="handleSubmit(submitTemporaryPosition)()"
                        >
                            {{ $t("position.add_temp_position") }}
                        </v-btn>
                    </div>
                </VeeForm>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import DatePicker from "@/components/DatePicker.vue";
import TimePicker from "@/components/TimePicker.vue";
import { userTemporaryPositionSchema } from "@/utils/schemas/userTemporaryPosition";
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        DatePicker,
        TimePicker,
        VeeForm,
        VeeField,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:modelValue", "submit"],
    data() {
        return {
            initialValues: {
                departmentId: null,
                positionId: null,
                startTempDate: "",
                startTempTime: "",
                endTempDate: "",
                endTempTime: "",
            },
            selectedDepartmentId: null,
            validationSchema: userTemporaryPositionSchema,
        };
    },
    computed: {
        ...mapGetters("user", [
            "departmentOptions",
            "departmentOptionsLoading",
            "positionOptionsByDepartment",
            "positionOptionsLoadingByDepartment",
        ]),
        model: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
        positionOptions() {
            return this.positionOptionsByDepartment(this.selectedDepartmentId);
        },
        positionOptionsLoading() {
            return this.positionOptionsLoadingByDepartment(
                this.selectedDepartmentId,
            );
        },
    },
    created() {
        this.loadDepartmentOptions();
    },
    methods: {
        ...mapActions("user", [
            "fetchDepartmentOptions",
            "fetchDepartmentPositions",
        ]),
        async loadDepartmentOptions() {
            await this.fetchDepartmentOptions();
        },
        async loadPositionOptions(departmentId) {
            if (!departmentId) {
                return;
            }

            await this.fetchDepartmentPositions({ departmentId });
        },
        async handleDepartmentChange(
            value,
            handleChange,
            handleBlur,
            setFieldValue,
        ) {
            this.selectedDepartmentId = value;
            handleChange(value);
            handleBlur();
            setFieldValue("positionId", null);
            await this.loadPositionOptions(value);
        },
        handlePositionChange(value, handleChange, handleBlur) {
            handleChange(value);
            handleBlur();
        },
        submitTemporaryPosition(values) {
            this.$emit("submit", values);
        },
    },
};
</script>
