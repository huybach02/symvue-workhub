<template>
    <VeeForm
        v-if="mode === 'create' || (mode === 'update' && item)"
        ref="formRef"
        v-slot="{ values }"
        :key="formKey"
        as="form"
        :validation-schema="validationSchema"
        :initial-values="initialValues"
        @submit="handleSubmit"
    >
        <v-row>
            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange }"
                    name="name"
                >
                    <div class="mb-2">
                        {{ $t("field.ten_chuc_vu") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="field"
                        :error-messages="errorMessage"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.ten_chuc_vu')}`"
                        @input="onNameChange($event, handleChange)"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField v-slot="{ field, errorMessage }" name="code">
                    <div class="mb-2">
                        {{ $t("field.ma_chuc_vu") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="field"
                        :error-messages="errorMessage"
                        variant="outlined"
                        readonly
                        :placeholder="`${$t('base.enter')} ${$t('field.ma_chuc_vu')}`"
                    />
                </VeeField>
            </v-col>

            <v-col v-if="showIsManagerField" cols="12">
                <VeeField v-slot="{ field, handleChange }" name="isManager">
                    <v-sheet
                        border
                        rounded="lg"
                        class="px-4 py-3"
                        color="grey-lighten-5"
                    >
                        <div
                            class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between ga-3"
                        >
                            <div>
                                <div class="text-body-1 font-weight-medium">
                                    {{ $t("field.position_is_manager") }}
                                </div>
                                <div class="text-body-2 text-medium-emphasis">
                                    {{
                                        $t(
                                            "bo_phan.text.positionManagerDescription",
                                        )
                                    }}
                                </div>
                            </div>

                            <v-switch
                                :model-value="Boolean(field.value)"
                                color="primary"
                                hide-details
                                inset
                                @update:model-value="
                                    handleChange($event ? 1 : 0)
                                "
                            />
                        </div>
                    </v-sheet>
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange, handleBlur }"
                    name="employmentType"
                >
                    <div class="mb-2">
                        {{ $t("field.hinh_thuc_lam_viec") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-select
                        :model-value="field.value"
                        :items="employmentTypeOptions"
                        item-title="text"
                        item-value="value"
                        :error-messages="errorMessage"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.hinh_thuc_lam_viec')}`"
                        @update:model-value="handleChange"
                        @blur="handleBlur"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange, handleBlur }"
                    name="currency"
                >
                    <div class="mb-2">
                        {{ $t("field.tien_te") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-select
                        :model-value="field.value"
                        :items="currencyOptions"
                        item-title="title"
                        item-value="value"
                        :error-messages="errorMessage"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.tien_te')}`"
                        @update:model-value="handleChange"
                        @blur="handleBlur"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange }"
                    name="minSalary"
                >
                    <div class="mb-2">
                        {{ $t("field.luong_toi_thieu") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="
                            bindFormattedNumberField({
                                fieldName: 'minSalary',
                                field,
                                handleChange,
                            })
                        "
                        :error-messages="errorMessage"
                        :suffix="values.currency || ''"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.luong_toi_thieu')}`"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange }"
                    name="maxSalary"
                >
                    <div class="mb-2">
                        {{ $t("field.luong_toi_da") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="
                            bindFormattedNumberField({
                                fieldName: 'maxSalary',
                                field,
                                handleChange,
                            })
                        "
                        :error-messages="errorMessage"
                        :suffix="values.currency || ''"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.luong_toi_da')}`"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage }"
                    name="probationMonths"
                >
                    <div class="mb-2">
                        {{ $t("field.so_thang_thu_viec") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="field"
                        :error-messages="errorMessage"
                        type="number"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.so_thang_thu_viec')}`"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage }"
                    name="probationSalaryRate"
                >
                    <div class="mb-2">
                        {{ $t("field.ty_le_luong_thu_viec") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="field"
                        :error-messages="errorMessage"
                        type="number"
                        suffix="%"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.ty_le_luong_thu_viec')}`"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage }"
                    name="annualLeaveDays"
                >
                    <div class="mb-2">
                        {{ $t("field.so_ngay_nghi_phep_nam") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="field"
                        :error-messages="errorMessage"
                        type="number"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.so_ngay_nghi_phep_nam')}`"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage }"
                    name="reviewCycleMonths"
                >
                    <div class="mb-2">
                        {{ $t("field.chu_ky_danh_gia_thang") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="field"
                        :error-messages="errorMessage"
                        type="number"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.chu_ky_danh_gia_thang')}`"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage }"
                    name="noticePeriodDays"
                >
                    <div class="mb-2">
                        {{ $t("field.so_ngay_bao_truoc") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-text-field
                        v-bind="field"
                        :error-messages="errorMessage"
                        type="number"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.so_ngay_bao_truoc')}`"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ field, errorMessage, handleChange, handleBlur }"
                    name="status"
                >
                    <div class="mb-2">
                        {{ $t("field.trang_thai") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-select
                        :model-value="field.value"
                        :items="statusOptions"
                        item-title="text"
                        item-value="value"
                        :error-messages="errorMessage"
                        variant="outlined"
                        :placeholder="`${$t('base.enter')} ${$t('field.trang_thai')}`"
                        @update:model-value="handleChange"
                        @blur="handleBlur"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12">
                <VeeField v-slot="{ field, errorMessage }" name="description">
                    <div class="mb-2">
                        {{ $t("field.mo_ta") }}
                    </div>
                    <v-textarea
                        v-bind="field"
                        :error-messages="errorMessage"
                        variant="outlined"
                        rows="3"
                        :placeholder="`${$t('base.enter')} ${$t('field.mo_ta')}`"
                    />
                </VeeField>
            </v-col>

            <v-col cols="12">
                <div class="d-flex align-center justify-space-between mb-2">
                    <div>{{ $t("field.phu_cap") }}</div>
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
                            :key="`allowance-${index}`"
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
                                        :placeholder="`${$t('base.enter')} ${$t('field.ten_phu_cap')}`"
                                    />
                                </v-col>
                                <v-col cols="12" md="5">
                                    <div class="mb-2 text-body-2">
                                        {{ $t("field.so_tien_phu_cap") }}
                                    </div>
                                    <v-text-field
                                        v-bind="
                                            bindFormattedNumberModel({
                                                fieldName: `allowanceAmount${index}`,
                                                value: allowance.amount,
                                                onChange: (value) =>
                                                    (allowance.amount = value),
                                            })
                                        "
                                        :suffix="values.currency || ''"
                                        variant="outlined"
                                        inputmode="numeric"
                                        hide-details
                                        :placeholder="`${$t('base.enter')} ${$t('field.so_tien_phu_cap')}`"
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
                <div class="d-flex justify-end ga-2">
                    <v-btn color="grey" @click="handleCancel">
                        {{ $t("button.cancel") }}
                    </v-btn>
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
import { constant } from "@/utils/constants/constant";
import { functionHelper } from "@/helpers/functionHelper";
import { useFormatInputNumber } from "@/hooks/useFormatInputNumber";
import { positionSchema } from "@/utils/schemas/position";

export default {
    components: {
        VeeForm,
        VeeField,
    },
    props: {
        submitButtonText: {
            type: String,
            default: "",
        },
        item: {
            type: Object,
            default: null,
        },
        mode: {
            type: String,
            default: "create",
        },
        loading: {
            type: Boolean,
            default: false,
        },
        department: {
            type: Object,
            default: null,
        },
    },
    emits: ["submit", "cancel"],
    data() {
        const { bindFormattedNumberField, bindFormattedNumberModel } =
            useFormatInputNumber();

        return {
            bindFormattedNumberField,
            bindFormattedNumberModel,
            validationSchema: positionSchema,
            initialValues: {
                name: "",
                code: "",
                employmentType: "FULL_TIME",
                minSalary: null,
                maxSalary: null,
                currency: "VND",
                isManager: 0,
                probationMonths: 2,
                probationSalaryRate: 85,
                annualLeaveDays: 12,
                reviewCycleMonths: 12,
                noticePeriodDays: 30,
                status: 1,
                description: "",
            },
            allowanceItems: [this.createEmptyAllowance()],
        };
    },
    computed: {
        formKey() {
            return `${this.mode}-${this.item?.id ?? "new"}`;
        },
        showIsManagerField() {
            return !this.department?.positionManager || Boolean(this.item?.isManager);
        },
        statusOptions() {
            return constant.STATUS.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
        currencyOptions() {
            return constant.CURRENCY_OPTIONS;
        },
        employmentTypeOptions() {
            return [
                {
                    value: "FULL_TIME",
                    text: this.$t("bo_phan.employmentType.fullTime"),
                },
                {
                    value: "PART_TIME",
                    text: this.$t("bo_phan.employmentType.partTime"),
                },
                {
                    value: "INTERN",
                    text: this.$t("bo_phan.employmentType.intern"),
                },
                {
                    value: "CONTRACTOR",
                    text: this.$t("bo_phan.employmentType.contractor"),
                },
            ];
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
        mode() {
            this.syncFormState();
        },
    },
    methods: {
        syncFormState() {
            this.$nextTick(() => {
                if (!this.$refs.formRef) {
                    return;
                }

                if (!this.item) {
                    this.$refs.formRef.resetForm({
                        values: {
                            ...this.initialValues,
                            code: this.buildPositionCode(this.initialValues.name),
                        },
                    });
                    this.allowanceItems = [this.createEmptyAllowance()];
                    return;
                }

                this.$refs.formRef.resetForm({
                    values: {
                        ...this.initialValues,
                        ...this.item,
                        code: this.item.code,
                    },
                });
                this.allowanceItems = this.normalizeAllowances(
                    this.item.allowances,
                );
            });
        },
        onNameChange(event, handleChange) {
            const name = event.target.value;
            handleChange(name);

            if (!this.$refs.formRef) {
                return;
            }

            if (this.item) {
                return;
            }

            this.$refs.formRef.setFieldValue(
                "code",
                this.buildPositionCode(name),
            );
        },
        buildPositionCode(name) {
            const localCode = functionHelper.generateMa(name ?? "");
            const departmentCode = this.department?.maBoPhan;

            return departmentCode && localCode
                ? `${departmentCode}_${localCode}`
                : localCode;
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

            return allowances.map((item) => {
                if (typeof item === "string") {
                    return {
                        name: item,
                        amount: null,
                    };
                }

                return {
                    name: item?.name ?? "",
                    amount: item?.amount ?? null,
                };
            });
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
                isManager: values.isManager ? 1 : 0,
                allowances,
            });
        },
        handleCancel() {
            this.$emit("cancel");
        },
    },
};
</script>
