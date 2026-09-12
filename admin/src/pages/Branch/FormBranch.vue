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
                                v-slot="{ field, errorMessage }"
                                name="name"
                            >
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
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="email"
                            >
                                <div class="mb-2">
                                    {{ $t("field.email") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.email')}`"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="phone"
                            >
                                <div class="mb-2">
                                    {{ $t("field.so_dien_thoai") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.so_dien_thoai')}`"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="address"
                            >
                                <div class="mb-2">
                                    {{ $t("field.dia_chi") }}
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.dia_chi')}`"
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
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ handleChange, errorMessage, value }"
                                name="image"
                            >
                                <ImageSelector
                                    :label="$t('field.image')"
                                    :required="false"
                                    :is-multiple="false"
                                    :model-value="value"
                                    :error-message="errorMessage"
                                    @selected="
                                        handleChange(
                                            $event?.path || $event || null,
                                        )
                                    "
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="note"
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
import { branchSchema } from "@/utils/schemas/branch";
import LoadingForm from "@/components/LoadingForm.vue";
import ImageSelector from "@/components/ImageSelector.vue";

export default {
    components: {
        LoadingForm,
        VeeForm,
        VeeField,
        ImageSelector,
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
            validationSchema: branchSchema,
            initialValues: {
                name: "",
                email: "",
                phone: "",
                address: "",
                image: null,
                status: 1,
                note: "",
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
