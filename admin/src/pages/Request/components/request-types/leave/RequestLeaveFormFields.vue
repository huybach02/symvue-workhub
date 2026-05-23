<template>
    <v-row>
        <v-col cols="12" md="6">
            <VeeField v-slot="{ field, errorMessage, handleChange }" name="leaveType">
                <div class="mb-2">
                    {{ $t("field.leave_type") }}
                    <span class="text-error">*</span>
                </div>
                <v-select
                    :model-value="field.value"
                    :items="leaveTypeOptions"
                    item-title="title"
                    item-value="value"
                    variant="outlined"
                    :error-messages="errorMessage"
                    @update:model-value="handleChange"
                />
            </VeeField>
        </v-col>

        <v-col cols="12" md="6">
            <VeeField v-slot="{ field, errorMessage, handleChange, handleBlur }" name="startDate">
                <div class="mb-2">
                    {{ $t("field.ngay_bat_dau") }}
                    <span class="text-error">*</span>
                </div>
                <v-text-field
                    :model-value="field.value"
                    type="date"
                    variant="outlined"
                    :error-messages="errorMessage"
                    @update:model-value="handleChange"
                    @blur="handleBlur"
                />
            </VeeField>
        </v-col>

        <v-col cols="12" md="6">
            <VeeField v-slot="{ field, errorMessage, handleChange, handleBlur }" name="endDate">
                <div class="mb-2">
                    {{ $t("field.ngay_ket_thuc") }}
                    <span class="text-error">*</span>
                </div>
                <v-text-field
                    :model-value="field.value"
                    type="date"
                    variant="outlined"
                    :error-messages="errorMessage"
                    @update:model-value="handleChange"
                    @blur="handleBlur"
                />
            </VeeField>
        </v-col>

        <v-col cols="12">
            <VeeField v-slot="{ field, errorMessage }" name="reason">
                <div class="mb-2">
                    {{ $t("field.request_reason") }}
                    <span class="text-error">*</span>
                </div>
                <v-textarea
                    v-bind="field"
                    variant="outlined"
                    :error-messages="errorMessage"
                    rows="4"
                />
            </VeeField>
        </v-col>

        <v-col cols="12">
            <VeeField v-slot="{ field, errorMessage }" name="handoverNote">
                <div class="mb-2">
                    {{ $t("field.handover_note") }}
                </div>
                <v-textarea
                    v-bind="field"
                    variant="outlined"
                    :error-messages="errorMessage"
                    rows="3"
                />
            </VeeField>
        </v-col>
    </v-row>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { LEAVE_TYPE_OPTIONS } from "@/utils/constants/request";

export default {
    name: "RequestLeaveFormFields",
    components: {
        VeeField,
    },
    computed: {
        leaveTypeOptions() {
            return LEAVE_TYPE_OPTIONS;
        },
    },
};
</script>
