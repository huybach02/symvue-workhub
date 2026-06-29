<template>
    <div>
        <VeeForm
            v-if="mode === 'create' || (mode === 'update' && item)"
            ref="formRef"
            v-slot="{ values, errors }"
            as="form"
            :validation-schema="validationSchema"
            :initial-values="initialValues"
            @submit="handleSubmit"
        >
            <v-tabs
                v-model="activeTab"
                color="primary"
                class="mb-4"
                style="pointer-events: none;"
            >
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
                    <FormUnitInfo :item="item" :show-errors="showUnitErrors" />
                </v-window-item>

                <v-window-item value="provider">
                    <FormProviderInfo :item="item" :form-values="values" />
                </v-window-item>
            </v-window>

            <!-- Nút cancel và create/update -->
            <v-row class="mt-4">
                <v-col cols="12">
                    <div class="d-flex justify-end ga-2">
                        <v-btn
                            v-if="activeTab !== 'provider'"
                            color="primary"
                            @click="handleNextTab(values, errors)"
                        >
                            {{ $t("button.next") }}
                        </v-btn>
                        <v-btn
                            v-else
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
import FormUnitInfo from "./components/FormUnitInfo.vue";
import FormProviderInfo from "./components/FormProviderInfo.vue";
import { merchandiseSchema } from "@/utils/schemas/merchandise";

export default {
    name: "FormIngredient",
    components: {
        LoadingForm,
        FormGeneralInfo,
        FormUnitInfo,
        FormProviderInfo,
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
            showUnitErrors: false,
            initialValues: {
                code: "",
                name: "",
                categoryId: null,
                profit: null,
                stockAlertQuantity: 0,
                description: "",
                notes: "",
                status: 1,
                baseUnitId: null,
                conversions: [],
                providers: [],
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
        activeTab() {
            this.showUnitErrors = false;
        },
    },
    methods: {
        handleSubmit(values) {
            this.$emit("submit", values);
        },
        handleCancel() {
            this.$emit("cancel");
        },
        isCurrentTabValid(values, errors) {
            if (this.activeTab === "info") {
                const hasCode = values.code && values.code.trim().length >= 3;
                const hasName = values.name && values.name.trim().length >= 3;
                const hasStatus = values.status === 0 || values.status === 1;
                return !!(
                    hasCode &&
                    hasName &&
                    hasStatus &&
                    (!errors ||
                        (!errors.code && !errors.name && !errors.status))
                );
            }
            if (this.activeTab === "unit") {
                const conversions = values.conversions || [];
                if (conversions.length === 0) return false;
                const allConversionsValid = conversions.every(
                    (c) =>
                        c.fromUnitId &&
                        c.toUnitId &&
                        c.fromValue > 0 &&
                        c.toValue > 0,
                );
                return !!(allConversionsValid && values.baseUnitId);
            }
            return true;
        },
        async handleNextTab(values, errors) {
            if (this.activeTab === "info") {
                const { valid } = await this.$refs.formRef.validate();
                if (!valid) {
                    return;
                }
                this.activeTab = "unit";
            } else if (this.activeTab === "unit") {
                if (!this.isCurrentTabValid(values, errors)) {
                    this.showUnitErrors = true;
                    return;
                }
                this.showUnitErrors = false;
                this.activeTab = "provider";
            }
        },
    },
};
</script>

<style scoped></style>
