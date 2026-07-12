<template>
    <div>
        <VeeField
            v-slot="{ field: fieldVariants, handleChange: onChangeVariants }"
            name="variants"
        >
            <div v-if="fieldVariants.value && fieldVariants.value.length > 0">
                <v-row>
                    <v-col
                        v-for="(variant, vIdx) in fieldVariants.value"
                        :key="vIdx"
                        cols="12"
                        md="6"
                    >
                        <v-card class="border rounded-lg shadow-sm">
                            <v-card-item class="bg-light py-3 border-bottom">
                                <div class="d-flex align-center ga-2">
                                    <v-icon color="primary">
                                        mdi-tag-multiple
                                    </v-icon>
                                    <v-card-title
                                        class="text-subtitle-1 font-weight-bold"
                                    >
                                        {{
                                            variant.name || variant.code
                                                ? `[${variant.code || "--"}] ${variant.name || "--"}`
                                                : `#${vIdx + 1}`
                                        }}
                                    </v-card-title>
                                    <v-chip
                                        v-if="variant.isDefault"
                                        color="primary"
                                        size="x-small"
                                        variant="flat"
                                    >
                                        {{ $t("base.default") || "Mặc định" }}
                                    </v-chip>
                                </div>
                            </v-card-item>

                            <v-card-text class="pt-4">
                                <!-- Gợi ý giá bán -->
                                <v-sheet
                                    v-if="
                                        previews[vIdx] &&
                                        (previews[vIdx].totalCost > 0 ||
                                            previews[vIdx].loading)
                                    "
                                    color="blue-lighten-5"
                                    class="mb-4 pa-3 rounded-lg border-sm border-blue-lighten-4 d-flex justify-space-between align-center"
                                >
                                    <div class="d-flex align-center ga-3">
                                        <v-avatar
                                            color="blue-lighten-4"
                                            size="36"
                                            class="rounded-lg"
                                        >
                                            <v-icon size="20" color="primary">
                                                mdi-calculator-variant
                                            </v-icon>
                                        </v-avatar>
                                        <div>
                                            <div
                                                class="text-body-2 font-weight-bold text-grey-darken-3"
                                            >
                                                {{
                                                    $t(
                                                        "business_product.price.formula_suggestion",
                                                    )
                                                }}
                                            </div>
                                            <div
                                                class="text-caption text-grey-darken-1"
                                            >
                                                {{
                                                    $t(
                                                        "business_product.price.formula_suggestion_desc",
                                                        {
                                                            margin:
                                                                formValues.targetProfitMargin ||
                                                                0,
                                                        },
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <v-progress-circular
                                        v-if="previews[vIdx].loading"
                                        indeterminate
                                        size="20"
                                        width="2"
                                        color="primary"
                                    ></v-progress-circular>
                                    <div v-else class="text-right">
                                        <div
                                            class="d-flex align-center justify-end ga-2 mb-1"
                                        >
                                            <span
                                                class="text-caption text-grey-darken-2"
                                                >{{
                                                    $t(
                                                        "business_product.price.cost_price",
                                                    )
                                                }}
                                            </span>
                                            <span
                                                class="text-subtitle-2 font-weight-bold text-red-darken-2"
                                            >
                                                {{
                                                    previews[
                                                        vIdx
                                                    ].totalCost.toLocaleString()
                                                }}
                                                <span
                                                    class="text-caption font-weight-normal text-grey-darken-1"
                                                    >{{ currency }}
                                                </span>
                                            </span>
                                        </div>
                                        <div
                                            class="d-flex align-center justify-end ga-2 mb-1"
                                        >
                                            <span
                                                class="text-caption text-grey-darken-2"
                                                >{{
                                                    $t(
                                                        "business_product.price.target_profit_margin",
                                                    )
                                                }}
                                            </span>
                                            <span
                                                class="text-subtitle-2 font-weight-bold text-red-darken-1"
                                            >
                                                {{
                                                    formValues.targetProfitMargin ||
                                                    0
                                                }}%
                                            </span>
                                        </div>
                                        <div
                                            class="d-flex align-center justify-end ga-2"
                                        >
                                            <span
                                                class="text-caption text-grey-darken-2"
                                                >{{
                                                    $t(
                                                        "business_product.price.suggested_price",
                                                    )
                                                }}
                                            </span>
                                            <span
                                                class="text-subtitle-1 font-weight-black text-success"
                                            >
                                                {{
                                                    previews[
                                                        vIdx
                                                    ].suggestedPrice.toLocaleString()
                                                }}
                                                <span
                                                    class="text-caption font-weight-normal text-grey-darken-1"
                                                    >{{ currency }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </v-sheet>

                                <v-row>
                                    <!-- Giá bán -->
                                    <v-col cols="12" md="6">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t(
                                                    "business_product.price.selling_price",
                                                ) || "Giá bán"
                                            }}
                                            <span class="text-red">*</span>
                                        </div>
                                        <v-text-field
                                            v-bind="
                                                bindFormattedNumberModel({
                                                    fieldName: `selling_price_${vIdx}`,
                                                    value: variant.priceConfig.price,
                                                    onChange: (val) => {
                                                        variant.priceConfig.price = val;
                                                        onChangeVariants(fieldVariants.value);
                                                    },
                                                })
                                            "
                                            type="text"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :error="
                                                showErrors &&
                                                (!variant.priceConfig.price ||
                                                    variant.priceConfig.price <=
                                                        0)
                                            "
                                            :placeholder="
                                                $t(
                                                    'business_product.price.placeholder_price',
                                                ) || 'Nhập giá bán'
                                            "
                                        />
                                        <div
                                            v-if="
                                                showErrors &&
                                                (!variant.priceConfig.price ||
                                                    variant.priceConfig.price <=
                                                        0)
                                            "
                                            class="text-caption text-error mt-1"
                                        >
                                            {{
                                                $t(
                                                    "validation.mixed.required",
                                                    {
                                                        field:
                                                            $t(
                                                                "business_product.price.selling_price",
                                                            ) || "Giá bán",
                                                    },
                                                )
                                            }}
                                        </div>
                                    </v-col>

                                    <!-- Tiền tệ -->
                                    <v-col cols="12" md="6">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t(
                                                    "business_product.price.currency",
                                                ) || "Tiền tệ"
                                            }}
                                        </div>
                                        <v-text-field
                                            :model-value="
                                                currency ||
                                                (variant.priceConfig &&
                                                    variant.priceConfig
                                                        .currency) ||
                                                'VND'
                                            "
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            readonly
                                            disabled
                                        />
                                    </v-col>

                                    <!-- Hiệu lực từ -->
                                    <v-col cols="12" md="6">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t(
                                                    "business_product.price.effective_from",
                                                ) || "Hiệu lực từ"
                                            }}
                                        </div>
                                        <DatePicker
                                            v-model="
                                                variant.priceConfig
                                                    .effectiveFrom
                                            "
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            @update:model-value="
                                                onChangeVariants(
                                                    fieldVariants.value,
                                                )
                                            "
                                        />
                                    </v-col>

                                    <!-- Hiệu lực đến -->
                                    <v-col cols="12" md="6">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t(
                                                    "business_product.price.effective_to",
                                                ) || "Hiệu lực đến"
                                            }}
                                        </div>
                                        <DatePicker
                                            v-model="
                                                variant.priceConfig.effectiveTo
                                            "
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            @update:model-value="
                                                onChangeVariants(
                                                    fieldVariants.value,
                                                )
                                            "
                                        />
                                    </v-col>

                                    <!-- Giá hiện tại đang áp dụng -->
                                    <v-col cols="12" md="12">
                                        <div class="d-flex align-center">
                                            <v-switch
                                                v-model="
                                                    variant.priceConfig
                                                        .isCurrent
                                                "
                                                color="primary"
                                                density="compact"
                                                hide-details
                                                @update:model-value="
                                                    onChangeVariants(
                                                        fieldVariants.value,
                                                    )
                                                "
                                            />
                                            <span
                                                class="ml-2 font-weight-medium"
                                            >
                                                {{
                                                    $t(
                                                        "business_product.price.is_current",
                                                    ) ||
                                                    "Giá hiện tại đang áp dụng"
                                                }}
                                            </span>
                                        </div>
                                    </v-col>

                                    <!-- Ghi chú -->
                                    <v-col cols="12">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t(
                                                    "business_product.price.note",
                                                ) || "Ghi chú"
                                            }}
                                        </div>
                                        <v-textarea
                                            v-model="variant.priceConfig.note"
                                            variant="outlined"
                                            density="compact"
                                            rows="2"
                                            hide-details="auto"
                                            :placeholder="
                                                $t(
                                                    'business_product.price.placeholder_note',
                                                ) || 'Nhập ghi chú giá'
                                            "
                                            @update:model-value="
                                                onChangeVariants(
                                                    fieldVariants.value,
                                                )
                                            "
                                        />
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </div>
            <div
                v-else
                class="d-flex flex-column align-center justify-center border border-dashed rounded-lg py-12 text-grey bg-white w-100"
            >
                <v-icon
                    icon="mdi-package-variant-closed"
                    size="48"
                    class="mb-2"
                />
                <span class="text-body-1 font-weight-medium">
                    {{
                        $t("business_product.empty_variants") ||
                        "Sản phẩm chưa thiết lập phiên bản nào"
                    }}
                </span>
                <span class="text-caption mt-1">
                    {{
                        $t("business_product.price.empty_variants_desc") ||
                        "Vui lòng quay lại tab trước để thêm mới phiên bản."
                    }}
                </span>
            </div>
        </VeeField>
    </div>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { mapActions, mapGetters } from "vuex";
import DatePicker from "@/components/DatePicker.vue";
import { useFormatInputNumber } from "@/hooks/useFormatInputNumber.js";

export default {
    name: "FormPricesInfo",
    components: {
        VeeField,
        DatePicker,
    },
    props: {
        item: {
            type: Object,
            default: null,
        },
        formValues: {
            type: Object,
            required: true,
        },
    },
    setup() {
        const { bindFormattedNumberModel } = useFormatInputNumber();
        return { bindFormattedNumberModel };
    },
    data() {
        return {
            previews: {},
            showErrors: false,
        };
    },
    computed: {
        ...mapGetters("generalSettings", ["currency"]),
        /**
         * Key duy nhất chỉ thay đổi khi recipe items thay đổi (không phụ thuộc price config).
         * Dùng để trigger lại price preview.
         */
        recipeKey() {
            const variants = this.formValues.variants || [];
            return JSON.stringify(
                variants.map((v) => ({
                    items: (v.recipe?.items || []).map((item) => ({
                        finishedProductId: item.finishedProductId,
                        quantity: item.quantity,
                        unitId: item.unitId,
                        wasteRate: item.wasteRate,
                    })),
                })),
            );
        },
    },
    watch: {
        recipeKey: {
            handler() {
                this.loadPricePreviews();
            },
            immediate: true,
        },
        "formValues.targetProfitMargin": {
            handler() {
                this.loadPricePreviews();
            },
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
        ...mapActions("businessProduct", ["fetchPricePreview"]),

        validate() {
            this.showErrors = true;
            const variants = this.formValues.variants || [];

            if (variants.length === 0) {
                return true; // Đã validate ở tab unit, ở đây chỉ check giá
            }

            let isValid = true;
            for (const variant of variants) {
                if (
                    !variant.priceConfig?.price ||
                    variant.priceConfig.price <= 0
                ) {
                    isValid = false;
                }
            }

            return isValid;
        },

        resetErrors() {
            this.showErrors = false;
        },

        async loadPricePreviews() {
            const variants = this.formValues.variants || [];
            const targetProfitMargin = this.formValues.targetProfitMargin || 0;

            // Chuẩn bị các promises chạy song song
            const promises = variants.map(async (variant, idx) => {
                const items = variant.recipe?.items || [];
                const validItems = items.filter(
                    (item) =>
                        item.finishedProductId &&
                        item.unitId &&
                        item.quantity > 0,
                );

                if (validItems.length === 0) {
                    return { idx, totalCost: 0, suggestedPrice: 0 };
                }

                const payload = validItems.map((item) => ({
                    merchandiseId: item.finishedProductId,
                    quantity: item.quantity,
                    unitId: item.unitId,
                    wasteRate: item.wasteRate ?? 0,
                    targetProfitMargin,
                }));

                const response = await this.fetchPricePreview(payload);
                return {
                    idx,
                    totalCost: response?.totalCost || 0,
                    suggestedPrice: response?.suggestedPrice || 0,
                };
            });

            // Set trạng thái loading cho các variant có công thức hợp lệ
            const tempPreviews = { ...this.previews };
            variants.forEach((v, idx) => {
                const hasRecipe = (v.recipe?.items || []).some(
                    (item) =>
                        item.finishedProductId &&
                        item.unitId &&
                        item.quantity > 0,
                );
                if (hasRecipe) {
                    tempPreviews[idx] = {
                        ...tempPreviews[idx],
                        loading: true,
                    };
                }
            });
            this.previews = tempPreviews;

            // Chờ tất cả kết quả phản hồi
            const results = await Promise.all(promises);

            // Cập nhật kết quả hiển thị đồng loạt một lần duy nhất
            const newPreviews = {};
            results.forEach(({ idx, totalCost, suggestedPrice }) => {
                newPreviews[idx] = {
                    totalCost,
                    suggestedPrice,
                    loading: false,
                };
            });
            this.previews = newPreviews;
        },
    },
};
</script>

<style scoped>
.border-bottom {
    border-bottom: 1px solid rgba(0, 0, 0, 0.12) !important;
}
.bg-light {
    background-color: #f8f9fa !important;
}
</style>
