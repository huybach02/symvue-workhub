<template>
    <VeeForm
        ref="formRef"
        :key="formKey"
        as="form"
        :validation-schema="validationSchema"
        :initial-values="initialValues"
        @submit="handleSubmit"
    >
        <v-row>
            <v-col cols="12" md="4">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange, handleBlur }"
                    name="branchId"
                >
                    <div class="mb-2">
                        {{ $t("field.branch") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-autocomplete
                        :model-value="field.value"
                        :items="branchOptions"
                        item-title="label"
                        item-value="value"
                        :error-messages="errorMessage"
                        variant="outlined"
                        clearable
                        :placeholder="`${$t('base.enter')} ${$t('field.branch')}`"
                        @update:model-value="
                            handleBranchChange($event, handleChange)
                        "
                        @blur="handleBlur"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="4">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange, handleBlur }"
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
                        :disabled="!selectedBranchId"
                        :placeholder="`${$t('base.enter')} ${$t('field.bo_phan')}`"
                        @update:model-value="
                            handleDepartmentChange($event, handleChange)
                        "
                        @blur="handleBlur"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="4">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange, handleBlur }"
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
                        :disabled="!selectedDepartmentId"
                        :placeholder="`${$t('base.enter')} ${$t('field.chuc_vu')}`"
                        @update:model-value="
                            handlePositionChange($event, handleChange)
                        "
                        @blur="handleBlur"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange }"
                    name="salary"
                >
                    <div class="mb-2">
                        {{ $t("position.salary") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="
                            bindFormattedNumberField({
                                fieldName: 'salary',
                                field,
                                handleChange,
                            })
                        "
                        :error-messages="errorMessage"
                        :suffix="currency"
                        variant="outlined"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6" />

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange }"
                    name="insuranceSalary"
                >
                    <div class="mb-2">
                        {{ $t("position.insurance_salary") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="
                            bindFormattedNumberField({
                                fieldName: 'insuranceSalary',
                                field,
                                handleChange,
                            })
                        "
                        :error-messages="errorMessage"
                        :suffix="currency"
                        variant="outlined"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField v-slot="{ field, errorMessage }" name="insuranceCode">
                    <div class="mb-2">
                        {{ $t("position.insurance_code") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="field"
                        :error-messages="errorMessage"
                        variant="outlined"
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
                    name="probationFrom"
                >
                    <div class="mb-2">
                        {{ $t("position.probation_from") }}
                        <span class="text-red"> * </span>
                    </div>
                    <DatePicker
                        :model-value="field.value"
                        :error-messages="errorMessage"
                        :placeholder="`${$t('base.enter')} ${$t('position.probation_from')}`"
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
                    name="probationTo"
                >
                    <div class="mb-2">
                        {{ $t("position.probation_to") }}
                        <span class="text-red"> * </span>
                    </div>
                    <DatePicker
                        :model-value="field.value"
                        :error-messages="errorMessage"
                        :placeholder="`${$t('base.enter')} ${$t('position.probation_to')}`"
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
                    name="effectiveFrom"
                >
                    <div class="mb-2">
                        {{ $t("position.effective_from") }}
                        <span class="text-red"> * </span>
                    </div>
                    <DatePicker
                        :model-value="field.value"
                        :error-messages="errorMessage"
                        :placeholder="`${$t('base.enter')} ${$t('position.effective_from')}`"
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
                    name="effectiveTo"
                >
                    <div class="mb-2">
                        {{ $t("position.effective_to") }}
                        <span class="text-red"> * </span>
                    </div>
                    <DatePicker
                        :model-value="field.value"
                        :error-messages="errorMessage"
                        :placeholder="`${$t('base.enter')} ${$t('position.effective_to')}`"
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

            <v-col cols="12">
                <div class="d-flex align-center justify-space-between mb-2">
                    <div>{{ $t("position.allowances_optional") }}</div>
                    <v-btn
                        color="primary"
                        variant="outlined"
                        size="small"
                        prepend-icon="mdi-plus"
                        @click="addAllowance"
                    >
                        {{ $t("bo_phan.button.addPositionAllowance") }}
                    </v-btn>
                </div>

                <v-sheet border rounded="lg" class="pa-4">
                    <div class="d-flex flex-column ga-3">
                        <div
                            v-for="(allowance, index) in allowanceItems"
                            :key="`user-position-allowance-${index}`"
                        >
                            <v-row align="center">
                                <v-col cols="12" md="5">
                                    <div class="mb-2 text-body-2">
                                        {{ $t("field.ten_phu_cap") }}
                                    </div>
                                    <v-text-field
                                        v-model="allowance.name"
                                        variant="outlined"
                                        hide-details
                                    />
                                </v-col>

                                <v-col cols="12" md="5">
                                    <div class="mb-2 text-body-2">
                                        {{ $t("field.so_tien_phu_cap") }}
                                    </div>
                                    <v-text-field
                                        v-bind="
                                            bindFormattedNumberModel({
                                                fieldName: `allowance-${index}`,
                                                value: allowance.amount,
                                                onChange: (value) =>
                                                    (allowance.amount = value),
                                            })
                                        "
                                        :suffix="currency"
                                        variant="outlined"
                                        hide-details
                                    />
                                </v-col>

                                <v-col cols="12" md="2" class="text-md-right">
                                    <v-btn
                                        color="error"
                                        variant="text"
                                        icon="mdi-delete-outline"
                                        :disabled="allowanceItems.length === 1"
                                        @click="removeAllowance(index)"
                                    />
                                </v-col>
                            </v-row>

                            <v-divider
                                v-if="index < allowanceItems.length - 1"
                                class="mt-3"
                            />
                        </div>
                    </div>
                </v-sheet>
            </v-col>

            <v-col cols="12">
                <VeeField v-slot="{ field, errorMessage }" name="note">
                    <div class="mb-2">{{ $t("field.ghi_chu") }}</div>
                    <v-textarea
                        v-bind="field"
                        :error-messages="errorMessage"
                        variant="outlined"
                        rows="3"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12">
                <div class="d-flex justify-end">
                    <v-btn color="primary" type="submit" :loading="loading">
                        {{ submitButtonText }}
                    </v-btn>
                </div>
            </v-col>
        </v-row>
    </VeeForm>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { useFormatInputNumber } from "@/hooks/useFormatInputNumber";
import { userPositionSchema } from "@/utils/schemas/userPosition";
import DatePicker from "@/components/DatePicker.vue";

export default {
    components: {
        VeeForm,
        VeeField,
        DatePicker,
    },
    props: {
        item: {
            type: Object,
            default: null,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        submitButtonText: {
            type: String,
            default: "Cập nhật",
        },
        currency: {
            type: String,
            default: "VND",
        },
        branchOptions: {
            type: Array,
            default: () => [],
        },
        departmentOptions: {
            type: Array,
            default: () => [],
        },
        positionOptions: {
            type: Array,
            default: () => [],
        },
    },
    emits: ["submit", "branch-change", "department-change", "position-change"],
    data() {
        const { bindFormattedNumberField, bindFormattedNumberModel } =
            useFormatInputNumber();

        return {
            bindFormattedNumberField,
            bindFormattedNumberModel,
            validationSchema: userPositionSchema,
            initialValues: this.getInitialValues(this.item),
            allowanceItems: this.normalizeAllowances(this.item?.allowances),
            selectedBranchId: this.item?.branchId ?? null,
            selectedDepartmentId: this.item?.departmentId ?? null,
        };
    },
    computed: {
        formKey() {
            return [
                this.item?.branchId ?? "new",
                this.item?.departmentId ?? "new",
                this.item?.positionId ?? "new",
                this.item?.probationFrom ?? "empty",
            ].join("-");
        },
    },
    watch: {
        item: {
            handler() {
                this.syncFormState();
            },
            deep: true,
            immediate: true,
        },
    },
    methods: {
        getInitialValues(item = null) {
            return {
                branchId: item?.branchId ?? null,
                departmentId: item?.departmentId ?? null,
                positionId: item?.positionId ?? null,
                salary: item?.salary ?? null,
                insuranceSalary: item?.insuranceSalary ?? null,
                insuranceCode: item?.insuranceCode ?? "",
                effectiveFrom: item?.effectiveFrom ?? "",
                effectiveTo: item?.effectiveTo ?? "",
                probationFrom: item?.probationFrom ?? "",
                probationTo: item?.probationTo ?? "",
                note: item?.note ?? "",
            };
        },
        createEmptyAllowance() {
            return {
                name: "",
                amount: null,
            };
        },
        normalizeAllowances(allowances) {
            if (!Array.isArray(allowances) || allowances.length === 0) {
                return [this.createEmptyAllowance()];
            }

            return allowances.map((item) => ({
                name: item?.name ?? "",
                amount: item?.amount ?? null,
            }));
        },
        syncFormState() {
            this.initialValues = this.getInitialValues(this.item);
            this.allowanceItems = this.normalizeAllowances(
                this.item?.allowances,
            );
            this.selectedBranchId = this.item?.branchId ?? null;
            this.selectedDepartmentId = this.item?.departmentId ?? null;

            this.$nextTick(() => {
                if (!this.$refs.formRef) {
                    return;
                }

                this.$refs.formRef.resetForm({
                    values: this.initialValues,
                });
            });
        },
        handleBranchChange(value, handleChange) {
            this.selectedBranchId = value;
            this.selectedDepartmentId = null;
            handleChange(value);

            if (this.$refs.formRef) {
                this.$refs.formRef.setFieldValue("departmentId", null);
                this.$refs.formRef.setFieldValue("positionId", null);
            }

            this.$emit("branch-change", value);
        },
        handleDepartmentChange(value, handleChange) {
            this.selectedDepartmentId = value;
            handleChange(value);

            if (this.$refs.formRef) {
                this.$refs.formRef.setFieldValue("positionId", null);
            }

            this.$emit("department-change", value);
        },
        handlePositionChange(value, handleChange) {
            handleChange(value);
            this.$emit("position-change", value);
        },
        addAllowance() {
            this.allowanceItems.push(this.createEmptyAllowance());
        },
        removeAllowance(index) {
            if (this.allowanceItems.length === 1) {
                this.allowanceItems = [this.createEmptyAllowance()];
                return;
            }

            this.allowanceItems.splice(index, 1);
        },
        handleSubmit(values) {
            const allowances = this.allowanceItems
                .map((item) => ({
                    name: String(item.name || "").trim(),
                    amount:
                        item.amount === null ||
                        item.amount === undefined ||
                        item.amount === ""
                            ? null
                            : Number(item.amount),
                }))
                .filter((item) => item.name);

            this.$emit("submit", {
                ...values,
                allowances,
            });
        },
    },
};
</script>
