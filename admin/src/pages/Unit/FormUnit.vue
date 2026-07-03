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
                        <v-col cols="12" md="3">
                            <VeeField v-slot="{ field, errorMessage, handleChange }" name="name">
                                <div class="mb-2">
                                    {{ $t("field.name") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.name')}`"
                                    @input="onNameChange($event, handleChange)"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField v-slot="{ field, errorMessage }" name="code">
                                <div class="mb-2">
                                    {{ $t("unit.columns.code") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    readonly
                                    :placeholder="`${$t('base.enter')} ${$t('unit.columns.code')}`"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField v-slot="{ field, errorMessage }" name="symbol">
                                <div class="mb-2">
                                    {{ $t("unit.columns.symbol") }}
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('unit.columns.symbol')}`"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
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
                    </v-row>
                </v-col>
            </v-row>

            <!-- Nút cancel và create/update -->
            <div class="sticky-actions-bar">
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
            </div>
        </VeeForm>
        <LoadingForm v-if="mode === 'update' && !item" :is-loading="true" />
    </div>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { constant } from "@/utils/constants/constant";
import LoadingForm from "@/components/LoadingForm.vue";
import { unitSchema } from "@/utils/schemas/unit";
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
            validationSchema: unitSchema,
            initialValues: {
                name: "",
                code: "",
                symbol: "",
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
                const symbol = (name || "").toLowerCase();
                
                this.$refs.formRef.setFieldValue("code", code);
                this.$refs.formRef.setFieldValue("symbol", symbol);
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
