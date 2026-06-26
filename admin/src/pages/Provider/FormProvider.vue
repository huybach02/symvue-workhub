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
                        <!-- Tên nhà cung cấp -->
                        <v-col cols="12" md="6">
                            <VeeField v-slot="{ field, errorMessage, handleChange }" name="name">
                                <div class="mb-2">
                                    {{ $t("provider.columns.name") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.name')}`"
                                    @input="onNameChange($event, handleChange)"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Mã nhà cung cấp -->
                        <v-col cols="12" md="6">
                            <VeeField v-slot="{ field, errorMessage }" name="code">
                                <div class="mb-2">
                                    {{ $t("provider.columns.code") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :readonly="mode === 'update'"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.code')}`"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Số điện thoại -->
                        <v-col cols="12" md="4">
                            <VeeField v-slot="{ field, errorMessage }" name="phone">
                                <div class="mb-2">
                                    {{ $t("provider.columns.phone") }}
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.phone')}`"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Email -->
                        <v-col cols="12" md="4">
                            <VeeField v-slot="{ field, errorMessage }" name="email">
                                <div class="mb-2">
                                    {{ $t("provider.columns.email") }}
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.email')}`"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Trạng thái -->
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
                                    {{ $t("provider.columns.status") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-select
                                    :model-value="field.value"
                                    :items="statusOptions"
                                    item-title="text"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.status')}`"
                                    @update:model-value="handleChange"
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Địa chỉ -->
                        <v-col cols="12" md="12">
                            <VeeField v-slot="{ field, errorMessage }" name="address">
                                <div class="mb-2">
                                    {{ $t("provider.columns.address") }}
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.address')}`"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Mã số thuế -->
                        <v-col cols="12" md="4">
                            <VeeField v-slot="{ field, errorMessage }" name="taxNumber">
                                <div class="mb-2">
                                    {{ $t("provider.columns.taxNumber") }}
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.taxNumber')}`"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Tên ngân hàng -->
                        <v-col cols="12" md="4">
                            <VeeField v-slot="{ field, errorMessage }" name="bankName">
                                <div class="mb-2">
                                    {{ $t("provider.columns.bankName") }}
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.bankName')}`"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Số tài khoản -->
                        <v-col cols="12" md="4">
                            <VeeField v-slot="{ field, errorMessage }" name="bankNumber">
                                <div class="mb-2">
                                    {{ $t("provider.columns.bankNumber") }}
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.bankNumber')}`"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Ghi chú -->
                        <v-col cols="12" md="12">
                            <VeeField v-slot="{ field, errorMessage }" name="note">
                                <div class="mb-2">
                                    {{ $t("provider.columns.note") }}
                                </div>
                                <v-textarea
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    rows="3"
                                    :placeholder="`${$t('base.enter')} ${$t('provider.columns.note')}`"
                                />
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-col>

                <!-- Nút cancel và create/update -->
                <v-col cols="12">
                    <div class="d-flex justify-end ga-2">
                        <v-btn color="grey" @click="handleCancel">
                            {{ $t("button.cancel") }}
                        </v-btn>
                        <v-btn
                            color="primary"
                            type="submit"
                            :loading="this.$store.state.isLoading"
                        >
                            {{ submitButtonText }}
                        </v-btn>
                    </div>
                </v-col>
            </v-row>
        </VeeForm>
        <LoadingForm v-if="mode === 'update' && !item" :is-loading="true" />
    </div>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { constant } from "@/utils/constants/constant";
import LoadingForm from "@/components/LoadingForm.vue";
import { providerSchema } from "@/utils/schemas/provider";
import { functionHelper } from "@/helpers/functionHelper";

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
            validationSchema: providerSchema,
            initialValues: {
                code: "",
                name: "",
                phone: "",
                email: "",
                address: "",
                taxNumber: "",
                bankName: "",
                bankNumber: "",
                note: "",
                status: 1,
            },
        };
    },
    computed: {
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
    methods: {
        onNameChange(event, handleChange) {
            const name = event.target.value;
            handleChange(name);

            if (this.$refs.formRef && this.mode === "create") {
                const code = functionHelper.generateMa(name || "");
                this.$refs.formRef.setFieldValue("code", code);
            }
        },
        handleSubmit(values) {
            this.$emit("submit", values);
        },
        handleCancel() {
            this.$emit("cancel");
        },
    },
};
</script>

<style scoped></style>
