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
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="parentId"
                            >
                                <div class="mb-2">
                                    {{ $t("category.parent_category") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <TreeAutocomplete
                                    :model-value="field.value"
                                    :items="items"
                                    :exclude-id="item?.id ?? null"
                                    :root-value="rootParentValue"
                                    :error-messages="errorMessage"
                                    :placeholder="$t('category.select_parent_category')"
                                    @update:model-value="handleChange"
                                    @blur="handleBlur"
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
import TreeAutocomplete from "./TreeAutocomplete.vue";
import { mapGetters } from "vuex";
import {
    categorySchema,
    ROOT_PARENT_VALUE,
} from "@/utils/schemas/category";

export default {
    components: {
        LoadingForm,
        TreeAutocomplete,
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
        type: {
            type: String,
            default: "ingredient",
        },
    },
    emits: ["submit", "cancel"],
    data() {
        return {
            validationSchema: categorySchema,
            initialValues: {
                name: "",
                status: 1,
                parentId: null,
            },
        };
    },
    computed: {
        ...mapGetters("category", ["items"]),
        rootParentValue() {
            return ROOT_PARENT_VALUE;
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
                    this.$nextTick(() => {
                        if (this.$refs.formRef) {
                            this.$refs.formRef.setValues({
                                ...value,
                                parentId:
                                    value.parentId ?? ROOT_PARENT_VALUE,
                            });
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
            this.$emit("submit", {
                ...values,
                parentId:
                    values.parentId === ROOT_PARENT_VALUE
                        ? null
                        : values.parentId,
                type: this.type,
            });
        },
        handleCancel() {
            this.$emit("cancel");
        },
    },
};
</script>

<style scoped></style>
