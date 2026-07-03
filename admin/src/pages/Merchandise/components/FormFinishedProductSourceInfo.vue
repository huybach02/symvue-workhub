<template>
    <div>
        <VeeField
            v-slot="{ field: fieldSource, handleChange: onChangeSource }"
            name="finishedProductSource"
        >
            <div class="mb-4">
                <div class="text-subtitle-1 font-weight-bold mb-2">
                    {{ $t("merchandise.tabs.finished_product_source") }}
                </div>
                <v-row class="mt-1">
                    <!-- Option 1: Nhập từ nhà cung cấp -->
                    <v-col cols="12" md="6">
                        <v-card
                            variant="outlined"
                            :class="[
                                'pa-4 cursor-pointer transition-all border-2 rounded-lg d-flex align-center ga-4',
                                fieldSource.value === 'supplier'
                                    ? 'border-primary bg-primary-lighten-5 text-primary'
                                    : 'border-grey-lighten-2 hover-border-primary',
                            ]"
                            @click="
                                handleSourceChange('supplier', onChangeSource)
                            "
                        >
                            <div
                                class="d-flex align-center justify-center rounded-circle pa-3 bg-white border"
                            >
                                <v-icon
                                    icon="mdi-truck-delivery-outline"
                                    size="30"
                                    :color="
                                        fieldSource.value === 'supplier'
                                            ? 'primary'
                                            : 'grey-darken-1'
                                    "
                                />
                            </div>
                            <div class="text-left flex-grow-1">
                                <div
                                    class="font-weight-bold text-subtitle-1 text-grey-darken-4"
                                >
                                    {{
                                        $t(
                                            "merchandise.finished_product_source.supplier",
                                        ) || "Nhập từ nhà cung cấp"
                                    }}
                                </div>
                                <div
                                    class="text-caption text-grey mt-0.5 line-height-tight"
                                >
                                    {{
                                        $t(
                                            "merchandise.finished_product_source.supplier_desc",
                                        )
                                    }}
                                </div>
                            </div>
                            <v-icon
                                :icon="
                                    fieldSource.value === 'supplier'
                                        ? 'mdi-radiobox-marked'
                                        : 'mdi-radiobox-blank'
                                "
                                :color="
                                    fieldSource.value === 'supplier'
                                        ? 'primary'
                                        : 'grey-lighten-1'
                                "
                            />
                        </v-card>
                    </v-col>

                    <!-- Option 2: Sản xuất nội bộ -->
                    <v-col cols="12" md="6">
                        <v-card
                            variant="outlined"
                            :class="[
                                'pa-4 cursor-pointer transition-all border-2 rounded-lg d-flex align-center ga-4',
                                fieldSource.value === 'production'
                                    ? 'border-primary bg-primary-lighten-5 text-primary'
                                    : 'border-grey-lighten-2 hover-border-primary',
                            ]"
                            @click="
                                handleSourceChange('production', onChangeSource)
                            "
                        >
                            <div
                                class="d-flex align-center justify-center rounded-circle pa-3 bg-white border"
                            >
                                <v-icon
                                    icon="mdi-chef-hat"
                                    size="30"
                                    :color="
                                        fieldSource.value === 'production'
                                            ? 'primary'
                                            : 'grey-darken-1'
                                    "
                                />
                            </div>
                            <div class="text-left flex-grow-1">
                                <div
                                    class="font-weight-bold text-subtitle-1 text-grey-darken-4"
                                >
                                    {{
                                        $t(
                                            "merchandise.finished_product_source.production",
                                        ) || "Sản xuất nội bộ"
                                    }}
                                </div>
                                <div
                                    class="text-caption text-grey mt-0.5 line-height-tight"
                                >
                                    {{
                                        $t(
                                            "merchandise.finished_product_source.production_desc",
                                        )
                                    }}
                                </div>
                            </div>
                            <v-icon
                                :icon="
                                    fieldSource.value === 'production'
                                        ? 'mdi-radiobox-marked'
                                        : 'mdi-radiobox-blank'
                                "
                                :color="
                                    fieldSource.value === 'production'
                                        ? 'primary'
                                        : 'grey-lighten-1'
                                "
                            />
                        </v-card>
                    </v-col>
                </v-row>
            </div>
        </VeeField>

        <div
            v-show="formValues.finishedProductSource === 'supplier'"
            class="mt-4"
        >
            <FormProviderInfo :item="item" :form-values="formValues" />
        </div>

        <div
            v-show="formValues.finishedProductSource === 'production'"
            class="mt-4"
        >
            <FormRecipeInfo :item="item" :form-values="formValues" />
        </div>
    </div>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import FormProviderInfo from "./FormProviderInfo.vue";
import FormRecipeInfo from "./FormRecipeInfo.vue";

export default {
    name: "FormFinishedProductSourceInfo",
    components: {
        VeeField,
        FormProviderInfo,
        FormRecipeInfo,
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
    methods: {
        handleSourceChange(value, onChangeSource) {
            onChangeSource(value);
        },
    },
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer !important;
}
.transition-all {
    transition: all 0.2s ease-in-out !important;
}
.border-2 {
    border-width: 2px !important;
}
.border-primary {
    border-color: rgb(var(--v-theme-primary)) !important;
}
.bg-primary-lighten-5 {
    background-color: #f5f9ff !important;
}
.border-grey-lighten-2 {
    border-color: #e0e0e0 !important;
}
.hover-border-primary:hover {
    border-color: rgb(var(--v-theme-primary)) !important;
    background-color: #fafcff !important;
}
.line-height-tight {
    line-height: 1.25 !important;
}
</style>
