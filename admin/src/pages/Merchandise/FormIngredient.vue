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
            <v-tabs v-model="activeTab" color="primary" class="mb-4">
                <v-tab value="info">
                    {{ $t("merchandise.tabs.info") }}
                </v-tab>
                <v-tab value="unit">
                    {{ $t("merchandise.tabs.unit") }}
                </v-tab>
                <v-tab value="provider">
                    {{ $t("merchandise.tabs.provider") }}
                </v-tab>
            </v-tabs>

            <v-window v-model="activeTab">
                <v-window-item value="info">
                    <FormGeneralInfo type="ingredient" :item="item" />
                </v-window-item>

                <v-window-item value="unit">
                    <!-- Tạm để trống -->
                </v-window-item>

                <v-window-item value="provider">
                    <!-- Tạm để trống -->
                </v-window-item>
            </v-window>

            <!-- Nút cancel và create/update -->
            <v-row class="mt-4">
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
import { Form as VeeForm } from "vee-validate";
import LoadingForm from "@/components/LoadingForm.vue";
import FormGeneralInfo from "./components/FormGeneralInfo.vue";
import { merchandiseSchema } from "@/utils/schemas/merchandise";

export default {
    name: "FormIngredient",
    components: {
        LoadingForm,
        FormGeneralInfo,
        VeeForm,
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
            activeTab: "info",
            validationSchema: merchandiseSchema,
            initialValues: {
                code: "",
                name: "",
                categoryId: null,
                profit: null,
                stockAlertQuantity: 0,
                description: "",
                notes: "",
                status: 1,
            },
        };
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
