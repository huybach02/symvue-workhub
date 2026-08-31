<template>
    <div>
        <VeeForm
            v-if="mode === 'create' || (mode === 'update' && item)"
            ref="formRef"
            as="form"
            :validation-schema="validationSchema"
            :initial-values="initialValues"
            @submit="handleSubmit"
        >
            <v-row>
                <v-col cols="12">
                    <v-row>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
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
                                    :loading="branchOptionsLoading"
                                    :disabled="mode === 'update'"
                                    variant="outlined"
                                    clearable
                                    :placeholder="`${$t('base.enter')} ${$t('field.branch')}`"
                                    @update:model-value="
                                        onBranchChange($event, handleChange)
                                    "
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>
                        <!-- <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="quanLyBoPhanId"
                            >
                                <div class="mb-2">
                                    {{ $t("field.quan_ly_bo_phan") }}
                                    <span class="text-red">* </span>
                                </div>
                                <v-autocomplete
                                    :key="selectedQuanLyBoPhan"
                                    :model-value="field.value"
                                    :items="nguoiDungOptions"
                                    item-title="label"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.quan_ly_bo_phan')}`"
                                    clearable
                                    @update:model-value="handleChange"
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col> -->
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage, handleChange }"
                                name="tenBoPhan"
                            >
                                <div class="mb-2">
                                    {{ $t("field.ten_bo_phan") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.ten_bo_phan')}`"
                                    @input="
                                        onTenBoPhanChange($event, handleChange)
                                    "
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="maBoPhan"
                            >
                                <div class="mb-2">
                                    {{ $t("field.ma_bo_phan") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    readonly
                                    :placeholder="`${$t('base.enter')} ${$t('field.ma_bo_phan')}`"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="status"
                            >
                                <div class="mb-2">
                                    {{ $t("field.trang_thai") }}
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
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="ghiChu"
                            >
                                <div class="mb-2">
                                    {{ $t("field.ghi_chu") }}
                                </div>
                                <v-textarea
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.ghi_chu')}`"
                                />
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>

            <div class="sticky-actions-bar">
                <div class="d-flex justify-end ga-2">
                    <v-btn color="grey" @click="handleCancel">
                        {{ $t("button.cancel") }}
                    </v-btn>
                    <v-btn
                        color="primary"
                        type="submit"
                        :loading="$store.state.isLoading"
                    >
                        {{ submitButtonText }}
                    </v-btn>
                </div>
            </div>
        </VeeForm>
        <LoadingForm v-if="mode === 'update' && !item" :is-loading="true" />
    </div>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { constant } from "@/utils/constants/constant";
import LoadingForm from "@/components/LoadingForm.vue";
import { functionHelper } from "@/helpers/functionHelper";
import { boPhanSchema } from "@/utils/schemas/boPhan";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        LoadingForm,
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
    },
    emits: ["submit", "cancel"],
    data() {
        return {
            validationSchema: boPhanSchema,
            initialValues: {
                branchId: null,
                tenBoPhan: "",
                maBoPhan: "",
                status: 1,
                ghiChu: "",
            },
            permissionsData: [],
            selectedQuanLyBoPhan: null,
            selectedBranchId: null,
            departmentName: "",
        };
    },
    computed: {
        ...mapGetters("department", ["userOptions"]),
        ...mapGetters("branch", {
            branchOptions: "options",
            branchOptionsLoading: "optionsLoading",
        }),
        nguoiDungOptions() {
            return this.userOptions;
        },
        statusOptions() {
            return constant.STATUS.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
    },
    watch: {
        item: {
            handler(value) {
                if (value) {
                    this.selectedQuanLyBoPhan = value.quanLyBoPhan;
                    this.selectedBranchId = value.branchId;
                    this.departmentName = value.tenBoPhan ?? "";
                    this.$nextTick(() => {
                        if (this.$refs.formRef) {
                            this.$refs.formRef.setValues(value);
                        }
                    });
                }
            },
            deep: true,
            immediate: true,
        },
    },
    created() {
        this.getUser();
        this.fetchBranchOptions();
    },
    methods: {
        ...mapActions("department", ["fetchUserOptions"]),
        ...mapActions("branch", { fetchBranchOptions: "fetchOptions" }),
        onBranchChange(value, handleChange) {
            this.selectedBranchId = value;
            handleChange(value);

            if (this.mode === "create") {
                this.updateDepartmentCode();
            }
        },
        onTenBoPhanChange(event, handleChange) {
            const tenBoPhan = event.target.value;
            this.departmentName = tenBoPhan;
            handleChange(tenBoPhan);

            this.updateDepartmentCode();
        },
        updateDepartmentCode() {
            if (this.mode !== "create" || !this.$refs.formRef) {
                return;
            }

            const branch = this.branchOptions.find(
                (item) => item.value === this.selectedBranchId,
            );
            const localCode = functionHelper.generateMa(this.departmentName);
            const departmentCode =
                branch?.code && localCode ? `${branch.code}_${localCode}` : localCode;

            this.$refs.formRef.setFieldValue("maBoPhan", departmentCode);
        },
        handleSubmit(values) {
            const submitData = {
                ...values,
                permissions: this.permissionsData,
            };

            this.$emit("submit", submitData);
        },
        handleCancel() {
            this.$emit("cancel");
        },
        async getUser() {
            await this.fetchUserOptions();
        },
    },
};
</script>
