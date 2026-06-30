<template>
    <v-row class="pt-2">
        <v-col cols="12" md="4">
            <VeeField v-slot="{ field, errorMessage }" name="code">
                <div class="mb-2">
                    {{ $t("field.merchandise_code") }}
                    <span class="text-red"> * </span>
                </div>
                <v-text-field
                    v-bind="field"
                    :error-messages="errorMessage"
                    type="text"
                    variant="outlined"
                    :placeholder="`${$t('base.enter')} ${$t('field.merchandise_code')}`"
                />
            </VeeField>
        </v-col>

        <v-col cols="12" md="4">
            <VeeField v-slot="{ field, errorMessage }" name="name">
                <div class="mb-2">
                    {{ $t("field.merchandise_name") }}
                    <span class="text-red"> * </span>
                </div>
                <v-text-field
                    v-bind="field"
                    :error-messages="errorMessage"
                    type="text"
                    variant="outlined"
                    :placeholder="`${$t('base.enter')} ${$t('field.merchandise_name')}`"
                />
            </VeeField>
        </v-col>

        <v-col cols="12" md="4">
            <VeeField
                v-slot="{ field, errorMessage, handleChange, handleBlur }"
                name="categoryId"
            >
                <div class="mb-2">
                    {{ $t("category.title") }}
                    <span class="text-red"> * </span>
                </div>
                <TreeAutocomplete
                    :model-value="field.value"
                    :items="categories"
                    :exclude-id="null"
                    :root-value="null"
                    :hide-root="true"
                    :error-messages="errorMessage"
                    :placeholder="$t('category.select_category')"
                    @update:model-value="handleChange"
                    @blur="handleBlur"
                />
            </VeeField>
        </v-col>

        <v-col cols="12" md="4">
            <VeeField v-slot="{ field, errorMessage }" name="profit">
                <div class="mb-2">
                    {{ $t("field.merchandise_profit") }}
                </div>
                <v-text-field
                    v-bind="field"
                    :error-messages="errorMessage"
                    type="number"
                    variant="outlined"
                    :placeholder="`${$t('base.enter')} ${$t('field.merchandise_profit')}`"
                />
            </VeeField>
        </v-col>

        <v-col cols="12" md="4">
            <VeeField v-slot="{ field, errorMessage }" name="stockAlertQuantity">
                <div class="mb-2">
                    {{ $t("field.merchandise_stock_alert_quantity") }}
                </div>
                <v-text-field
                    v-bind="field"
                    :error-messages="errorMessage"
                    type="number"
                    variant="outlined"
                    :placeholder="`${$t('base.enter')} ${$t('field.merchandise_stock_alert_quantity')}`"
                />
            </VeeField>
        </v-col>

        <v-col cols="12" md="4">
            <VeeField
                v-slot="{ field, errorMessage, handleChange, handleBlur }"
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

        <v-col cols="12" md="12">
            <VeeField
                v-slot="{ field, errorMessage, handleChange, handleBlur }"
                name="description"
            >
                <RichEditor
                    :model-value="field.value"
                    :error-messages="errorMessage"
                    :label="$t('field.merchandise_description')"
                    :placeholder="`${$t('base.enter')} ${$t('field.merchandise_description')}`"
                    @update:model-value="handleChange"
                    @blur="handleBlur"
                />
            </VeeField>
        </v-col>

        <v-col cols="12" md="12">
            <VeeField v-slot="{ field, errorMessage }" name="notes">
                <div class="mb-2">
                    {{ $t("field.merchandise_notes") }}
                </div>
                <v-textarea
                    v-bind="field"
                    :error-messages="errorMessage"
                    rows="3"
                    variant="outlined"
                    :placeholder="`${$t('base.enter')} ${$t('field.merchandise_notes')}`"
                />
            </VeeField>
        </v-col>
    </v-row>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { constant } from "@/utils/constants/constant";
import TreeAutocomplete from "@/pages/Category/TreeAutocomplete.vue";
import RichEditor from "@/components/RichEditor.vue";
import { mapActions, mapGetters } from "vuex";

export default {
    name: "FormGeneralInfo",
    components: {
        VeeField,
        TreeAutocomplete,
        RichEditor,
    },
    props: {
        type: {
            type: String,
            default: "ingredient",
        },
        item: {
            type: Object,
            default: null,
        },
    },
    computed: {
        ...mapGetters("category", { categories: "items" }),
        statusOptions() {
            return constant.STATUS.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
    },
    mounted() {
        this.fetchCategories({
            f: [
                {
                    field: "type",
                    operator: "equal",
                    value: this.type,
                },
            ],
        });
    },
    methods: {
        ...mapActions("category", { fetchCategories: "fetchItems" }),
    },
};
</script>

<style scoped></style>
