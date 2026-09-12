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
                <v-tab value="price">
                    {{
                        $t("business_product.tabs.price_setting") ||
                        "Thiết lập giá"
                    }}
                </v-tab>
            </v-tabs>

            <v-window v-model="activeTab" :touch="false">
                <v-window-item value="info">
                    <FormGeneralInfo type="business_product" :item="item" />
                </v-window-item>

                <v-window-item value="unit">
                    <FormVariantsInfo
                        ref="variantsInfoRef"
                        :item="item"
                        :form-values="values"
                    />
                </v-window-item>

                <v-window-item value="price">
                    <FormPricesInfo
                        ref="pricesInfoRef"
                        :item="item"
                        :form-values="values"
                    />
                </v-window-item>
            </v-window>

            <!-- Nút cancel và create/update -->
            <div class="sticky-actions-bar">
                <div class="d-flex justify-end ga-2">
                    <v-btn
                        v-if="activeTab === 'info'"
                        color="grey"
                        variant="outlined"
                        @click="handleCancel"
                    >
                        {{ $t("button.cancel") }}
                    </v-btn>
                    <v-btn
                        v-else
                        color="grey"
                        variant="outlined"
                        @click="handlePrevTab"
                    >
                        {{ $t("base.back") || "Quay lại" }}
                    </v-btn>
                    <v-btn
                        v-if="activeTab !== 'price'"
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
        </VeeForm>
        <LoadingForm v-if="mode === 'update' && !item" :is-loading="true" />
    </div>
</template>

<script>
import { Form as VeeForm } from "vee-validate";
import LoadingForm from "@/components/LoadingForm.vue";
import FormGeneralInfo from "./components/FormGeneralInfo.vue";
import FormVariantsInfo from "./components/FormVariantsInfo.vue";
import FormPricesInfo from "./components/FormPricesInfo.vue";
import { businessProductSchema } from "@/utils/schemas/businessProduct";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        LoadingForm,
        FormGeneralInfo,
        FormVariantsInfo,
        FormPricesInfo,
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
            validationSchema: businessProductSchema,
            initialValues: {
                code: "",
                name: "",
                categoryId: null,
                targetProfitMargin: null,
                description: "",
                notes: "",
                image: null,
                status: 1,
                variants: [],
            },
        };
    },
    computed: {
        ...mapGetters("generalSettings", ["currency"]),
    },
    watch: {
        item: {
            handler(value) {
                if (value) {
                    this.$nextTick(() => {
                        if (this.$refs.formRef) {
                            const data = {
                                ...value,
                                image: value.image || null,
                                variants: (value.variants || []).map((v) => ({
                                    ...v,
                                    priceConfig: v.priceConfig || {
                                        price: v.sellingPrice || null,
                                        currency:
                                            v.currency ||
                                            this.currency ||
                                            "VND",
                                        effectiveFrom: null,
                                        effectiveTo: null,
                                        isCurrent: true,
                                        note: "",
                                    },
                                })),
                            };
                            this.$refs.formRef.setValues(data);
                        }
                    });
                }
            },
            deep: true,
            immediate: true,
        },
    },
    mounted() {
        this.fetchGeneralSettings();
    },
    methods: {
        ...mapActions("generalSettings", {
            fetchGeneralSettings: "fetchSettings",
        }),
        handleSubmit(values) {
            // Validate tab giá trước khi submit form
            const isValid = this.$refs.pricesInfoRef.validate();
            if (!isValid) return;

            console.log(values);
            this.$emit("submit", values);
        },
        handleCancel() {
            this.$emit("cancel");
        },
        async handleNextTab(values) {
            if (this.activeTab === "info") {
                await this.$refs.formRef.validate();
                const errors = this.$refs.formRef.errors;
                const hasInfoErrors = [
                    "code",
                    "name",
                    "categoryId",
                    "status",
                ].some((field) => !!errors[field]);
                if (hasInfoErrors) return;
                this.activeTab = "unit";
            } else if (this.activeTab === "unit") {
                // Validate tab unit: phiên bản + thành phẩm công thức
                const isValid = this.$refs.variantsInfoRef.validate();
                if (!isValid) return;
                this.activeTab = "price";
            }
        },
        handlePrevTab() {
            if (this.activeTab === "price") {
                this.$refs.pricesInfoRef.resetErrors();
                this.activeTab = "unit";
            } else if (this.activeTab === "unit") {
                this.$refs.variantsInfoRef.resetErrors();
                this.activeTab = "info";
            }
        },
    },
};
</script>

<style scoped></style>
