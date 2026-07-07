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
                <v-tab value="source">
                    {{ $t("merchandise.tabs.finished_product_source") }}
                </v-tab>
            </v-tabs>

            <v-window v-model="activeTab" :touch="false">
                <v-window-item value="info">
                    <FormGeneralInfo type="finished_product" :item="item" />
                </v-window-item>

                <v-window-item value="unit">
                    <FormUnitInfo :item="item" :show-errors="showUnitErrors" />
                </v-window-item>

                <v-window-item value="source">
                    <FormFinishedProductSourceInfo :item="item" :form-values="values" />
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
                            v-if="activeTab !== 'source'"
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
import FormFinishedProductSourceInfo from "./components/FormFinishedProductSourceInfo.vue";
import { merchandiseSchema } from "@/utils/schemas/merchandise";

export default {
    name: "FormFinishedProduct",
    components: {
        LoadingForm,
        FormGeneralInfo,
        FormUnitInfo,
        FormFinishedProductSourceInfo,
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
            showRecipeErrors: false,
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
                finishedProductSource: "supplier",
                recipe: {
                    outputQuantity: 1,
                    outputUnitId: null,
                    items: [],
                },
            },
        };
    },
    watch: {
        item: {
            handler(value) {
                if (value) {
                    this.$nextTick(() => {
                        if (this.$refs.formRef) {
                            // Backup recipe nếu database trả về null
                            const data = {
                                ...value,
                                finishedProductSource: value.finishedProductSource || "supplier",
                                recipe: value.recipe || {
                                    outputQuantity: 1,
                                    outputUnitId: null,
                                    items: [],
                                },
                            };
                            this.$refs.formRef.setValues(data);
                        }
                    });
                }
            },
            deep: true,
            immediate: true,
        },
        activeTab() {
            this.showUnitErrors = false;
            this.showRecipeErrors = false;
            if (this.$refs.formRef) {
                this.$refs.formRef.setFieldError("providers", undefined);
            }
        },
    },
    methods: {
        handleSubmit(values) {
            if (!this.isCurrentTabValid(values)) {
                if (this.activeTab === "unit") {
                    this.showUnitErrors = true;
                } else if (this.activeTab === "source") {
                    this.showRecipeErrors = true;
                }
                return;
            }
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
            if (this.activeTab === "source") {
                if (values.finishedProductSource === "production") {
                    const recipe = values.recipe;
                    if (!recipe || !recipe.outputUnitId) return false;
                    const items = recipe.items || [];
                    if (items.length === 0) return false;
                    const allItemsValid = items.every(
                        (item) =>
                            item.ingredientId &&
                            item.quantity > 0 &&
                            item.unitId &&
                            item.wasteRate >= 0,
                    );
                    return allItemsValid;
                }
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
                this.activeTab = "source";
            }
        },
        handlePrevTab() {
            if (this.activeTab === "source") {
                this.activeTab = "unit";
            } else if (this.activeTab === "unit") {
                this.activeTab = "info";
            }
        },
    },
};
</script>

<style scoped></style>
