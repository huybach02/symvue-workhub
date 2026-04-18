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
                            @update:model-value="handleChange"
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
                            @update:model-value="handleChange"
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
                            @update:model-value="handleChange"
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
                            @update:model-value="handleChange"
                            @blur="handleBlur"
                        />
                    </VeeField>
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
import { postData } from "@/services/bases/postData";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

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
        };
    },
    computed: {
        formKey() {
            return this.userSelected?.id ?? "new";
        },
    },
    methods: {
        async handleSubmit(values) {
            this.valuesToSubmit = values;
            this.loading = true;
            const response = await postData(
                API_ROUTES_CONFIG.workSchedule + "/fulltime/check-override",
                {
                    userId: this.userSelected?.id,
                    startDate: values.startDate,
                    endDate: values.endDate,
                },
                () => {},
                true,
            );

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
            await postData(
                API_ROUTES_CONFIG.workSchedule + "/fulltime/override",
                {
                    userId: this.userSelected?.id,
                    startDate: this.valuesToSubmit.startDate,
                    endDate: this.valuesToSubmit.endDate,
                    startTime: this.valuesToSubmit.startTime,
                    endTime: this.valuesToSubmit.endTime,
                },
            );
            this.$emit("cancel");
            this.valuesToSubmit = null;
            this.loadingConfirm = false;
        },
    },
};
</script>

<style></style>
