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
            <div v-if="branchName" class="mb-3">
                <v-chip color="primary" variant="tonal" class="font-weight-medium">
                    <v-icon start size="small">mdi-store-outline</v-icon>
                    {{ branchName }}
                </v-chip>
            </div>

            <template v-if="mode === 'create'">
                <v-alert
                    type="info"
                    variant="tonal"
                    density="compact"
                    class="mb-4 text-caption"
                >
                    {{ $t("dining_table.create_range_hint") }}
                </v-alert>

                <v-row dense>
                    <v-col cols="12" sm="6">
                        <VeeField v-slot="{ field, errorMessage }" name="from">
                            <div class="mb-1 font-weight-medium">
                                {{ $t("dining_table.from_table") }}
                                <span class="text-red">*</span>
                            </div>
                            <v-text-field
                                v-bind="field"
                                :error-messages="errorMessage"
                                type="number"
                                min="1"
                                variant="outlined"
                                density="comfortable"
                                :placeholder="
                                    $t('dining_table.placeholder_from')
                                "
                            />
                        </VeeField>
                    </v-col>

                    <v-col cols="12" sm="6">
                        <VeeField v-slot="{ field, errorMessage }" name="to">
                            <div class="mb-1 font-weight-medium">
                                {{ $t("dining_table.to_table") }}
                                <span class="text-red">*</span>
                            </div>
                            <v-text-field
                                v-bind="field"
                                :error-messages="errorMessage"
                                type="number"
                                min="1"
                                variant="outlined"
                                density="comfortable"
                                :placeholder="$t('dining_table.placeholder_to')"
                            />
                        </VeeField>
                    </v-col>
                </v-row>
            </template>

            <template v-else>
                <v-row>
                    <v-col cols="12" md="6">
                        <VeeField
                            v-slot="{ field, errorMessage }"
                            name="tableNumber"
                        >
                            <div class="mb-2 font-weight-medium">
                                {{ $t("dining_table.columns.table_number") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-text-field
                                v-bind="field"
                                :error-messages="errorMessage"
                                type="number"
                                min="1"
                                variant="outlined"
                                :placeholder="`${$t('base.enter')} ${$t('dining_table.columns.table_number')}`"
                            />
                        </VeeField>
                    </v-col>
                    <v-col cols="12" md="6">
                        <VeeField
                            v-slot="{
                                field,
                                errorMessage,
                                handleChange,
                                handleBlur,
                            }"
                            name="status"
                        >
                            <div class="mb-2 font-weight-medium">
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
            </template>

            <!-- Nút cancel và create/update -->
            <div class="sticky-actions-bar">
                <div class="d-flex justify-end ga-2">
                    <v-btn color="grey" @click="handleCancel">
                        {{ $t("button.cancel") }}
                    </v-btn>
                    <v-btn
                        color="primary"
                        type="submit"
                        :loading="$store.state.isLoading"
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
import { diningTableRangeSchema } from "@/utils/schemas/diningTableRange";
import { constant } from "@/utils/constants/constant";
import LoadingForm from "@/components/LoadingForm.vue";
import * as yup from "yup";

export default {
    name: "FormDiningTable",
    components: {
        LoadingForm,
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
        branchId: {
            type: [Number, String],
            default: null,
        },
        branchName: {
            type: String,
            default: "",
        },
    },
    emits: ["submit", "cancel"],
    data() {
        return {
            initialValues:
                this.mode === "create"
                    ? { from: 1, to: 20 }
                    : { tableNumber: 1, status: 1 },
        };
    },
    computed: {
        validationSchema() {
            if (this.mode === "create") {
                return diningTableRangeSchema;
            }
            return yup.object({
                tableNumber: yup.number().required().min(1),
                status: yup.number().required(),
            });
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
            this.$emit("submit", {
                ...values,
                branchId: this.branchId || values.branchId || null,
            });
        },
        handleCancel() {
            this.$emit("cancel");
        },
    },
};
</script>

<style scoped></style>
