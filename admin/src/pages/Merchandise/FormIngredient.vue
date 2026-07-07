<template>
    <div>
        <VeeForm
            v-if="mode === 'create' || (mode === 'update' && item)"
            ref="formRef"
            v-slot="{ values, errors, submitCount }"
            as="form"
            :validation-schema="validationSchema"
            :initial-values="initialValues"
            @submit="handleSubmit"
        >
            <v-tabs
                v-model="activeTab"
                color="primary"
                class="mb-4"
                style="pointer-events: none"
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

            <v-window v-model="activeTab" :touch="false">
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
            <div class="sticky-actions-bar">
                <div class="d-flex align-center justify-space-between w-100">
                    <div style="flex-grow: 1; min-width: 0;" class="pr-4">
                        <v-alert
                            v-if="submitCount > 0 && errors.providers"
                            type="error"
                            variant="tonal"
                            density="compact"
                            class="ma-0 py-1 text-truncate"
                            style="max-width: 500px;"
                        >
                            {{ errors.providers }}
                        </v-alert>
                    </div>
                    <div class="d-flex ga-2 flex-shrink-0">
                        <v-btn
                            v-if="activeTab !== 'info'"
                            color="grey"
                            variant="outlined"
                            @click="handlePrevTab"
                        >
                            {{ $t("base.back") || "Quay lại" }}
                        </v-btn>
                        <v-btn
                            v-if="activeTab !== 'provider'"
                            color="primary"
                            @click="handleNextTab(values)"
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
                </div>
            </div>
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
                isSingleUnit: false,
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
            immediate: true,
        },
        activeTab() {
            this.showUnitErrors = false;
            if (this.$refs.formRef) {
                this.$refs.formRef.setFieldError("providers", undefined);
            }
        },
    },
    methods: {
        handleSubmit(values) {
            this.$emit("submit", values);
        },
        handleCancel() {
            this.$emit("cancel");
        },
        isCurrentTabValid(values) {
            if (this.activeTab === "unit") {
                if (values.isSingleUnit) {
                    return !!values.baseUnitId;
                }
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
        async handleNextTab(values) {
            if (this.activeTab === "info") {
                await this.$refs.formRef.validate();
                const errors = this.$refs.formRef.errors;
                const hasInfoErrors = ["code", "name", "categoryId", "profit", "stockAlertQuantity", "description", "notes", "status"].some(field => !!errors[field]);
                if (hasInfoErrors) return;
                this.activeTab = "unit";
            } else if (this.activeTab === "unit") {
                if (!this.isCurrentTabValid(values)) {
                    this.showUnitErrors = true;
                    return;
                }
                this.showUnitErrors = false;
                this.activeTab = "provider";
            }
        },
        handlePrevTab() {
            if (this.activeTab === "provider") {
                this.activeTab = "unit";
            } else if (this.activeTab === "unit") {
                this.activeTab = "info";
            }
        },
    },
};
</script>

<style scoped></style>
