<template>
    <VeeForm
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
                        <VeeField v-slot="{ field, errorMessage }" name="name">
                            <div class="mb-2">
                                {{ $t("field.ho_va_ten") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-text-field
                                v-bind="field"
                                :error-messages="errorMessage"
                                type="text"
                                variant="outlined"
                                :placeholder="`${$t('base.enter')} ${$t('field.ho_va_ten')}`"
                            />
                        </VeeField>
                    </v-col>
                    <v-col cols="12" md="4">
                        <VeeField v-slot="{ field, errorMessage }" name="email">
                            <div class="mb-2">
                                {{ $t("field.email") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-text-field
                                v-bind="field"
                                :error-messages="errorMessage"
                                type="text"
                                variant="outlined"
                                :placeholder="`${$t('base.enter')} ${$t('field.email')}`"
                            />
                        </VeeField>
                    </v-col>
                    <v-col cols="12" md="4">
                        <VeeField v-slot="{ field, errorMessage }" name="phone">
                            <div class="mb-2">
                                {{ $t("field.so_dien_thoai") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-text-field
                                v-bind="field"
                                :error-messages="errorMessage"
                                type="text"
                                variant="outlined"
                                :placeholder="`${$t('base.enter')} ${$t('field.so_dien_thoai')}`"
                            />
                        </VeeField>
                    </v-col>
                </v-row>
            </v-col>

            <v-col cols="12">
                <v-row>
                    <v-col cols="12" md="4">
                        <VeeField
                            v-slot="{
                                field,
                                errorMessage,
                                handleChange,
                                handleBlur,
                            }"
                            name="birthday"
                        >
                            <div class="mb-2">
                                {{ $t("field.ngay_sinh") }}
                                <span class="text-red"> * </span>
                            </div>
                            <DatePicker
                                :model-value="field.value"
                                :error-messages="errorMessage"
                                :placeholder="`${$t('base.enter')} ${$t('field.ngay_sinh')}`"
                                @update:model-value="
                                    (value) => {
                                        handleChange(value);
                                        handleBlur();
                                    }
                                "
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
                            name="gender"
                        >
                            <div class="mb-2">
                                {{ $t("field.gioi_tinh") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-select
                                :model-value="field.value"
                                :items="genderOptions"
                                item-title="text"
                                item-value="value"
                                :error-messages="errorMessage"
                                variant="outlined"
                                :placeholder="`${$t('base.enter')} ${$t('field.gioi_tinh')}`"
                                @update:model-value="handleChange"
                                @blur="handleBlur"
                            />
                        </VeeField>
                    </v-col>
                </v-row>
            </v-col>

            <v-col cols="12">
                <v-row>
                    <v-col cols="12" md="4">
                        <VeeField
                            v-slot="{ field, errorMessage, handleChange }"
                            name="province"
                        >
                            <div class="mb-2">
                                {{ $t("field.tinh_thanh_pho") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-autocomplete
                                :model-value="field.value"
                                name="province"
                                :items="provinceOptions"
                                item-title="name_with_type"
                                item-value="code"
                                :error-messages="errorMessage"
                                variant="outlined"
                                clearable
                                :placeholder="`${$t('base.enter')} ${$t('field.tinh_thanh_pho')}`"
                                @update:model-value="
                                    (value) => {
                                        handleChange(value);
                                        handleProvinceChange(value);
                                    }
                                "
                                @blur="field.onBlur"
                            />
                        </VeeField>
                    </v-col>
                    <v-col cols="12" md="4">
                        <VeeField
                            ref="wardField"
                            v-slot="{ field, errorMessage, handleChange }"
                            name="ward"
                        >
                            <div class="mb-2">
                                {{ $t("field.xa_phuong") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-autocomplete
                                :key="selectedProvince"
                                :model-value="field.value"
                                name="ward"
                                :items="wardOptions"
                                item-title="name_with_type"
                                item-value="code"
                                :error-messages="errorMessage"
                                variant="outlined"
                                clearable
                                :placeholder="`${$t('base.enter')} ${$t('field.xa_phuong')}`"
                                @update:model-value="handleChange"
                                @blur="field.onBlur"
                            />
                        </VeeField>
                    </v-col>
                    <v-col cols="12" md="4">
                        <VeeField
                            v-slot="{ field, errorMessage }"
                            name="address"
                        >
                            <div class="mb-2">
                                {{ $t("field.dia_chi") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-text-field
                                v-bind="field"
                                :error-messages="errorMessage"
                                type="text"
                                variant="outlined"
                                :placeholder="`${$t('base.enter')} ${$t('field.dia_chi')}`"
                            />
                        </VeeField>
                    </v-col>
                </v-row>
            </v-col>

            <v-col cols="12">
                <v-row>
                    <v-col cols="12" md="4">
                        <VeeField
                            v-slot="{ field, errorMessage }"
                            name="maBoPhan"
                        >
                            <div class="mb-2">
                                {{ $t("field.bo_phan_mac_dinh") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-select
                                v-bind="field"
                                :items="['1', '2']"
                                :error-messages="errorMessage"
                                variant="outlined"
                                :placeholder="`${$t('base.enter')} ${$t('field.bo_phan_mac_dinh')}`"
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

            <!-- Nút cancel và create/update -->
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
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import DatePicker from "@/components/DatePicker.vue";
import { functionHelper } from "@/helpers/functionHelper";
import { addressHelper } from "@/helpers/addressHelper";
import { userSchema } from "@/utils/schemas/user";
import { constant } from "@/utils/constants/constant";

export default {
    components: {
        VeeForm,
        VeeField,
        DatePicker,
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
    },
    emits: ["submit", "cancel"],
    data() {
        return {
            validationSchema: userSchema,
            initialValues: {
                name: "",
                email: "",
                phone: "",
                birthday: "",
                gender: "",
                province: "",
                ward: "",
                address: "",
                maBoPhan: "",
                status: "",
            },
            provinceData: {},
            wardData: {},
            selectedProvince: "",
        };
    },
    computed: {
        genderOptions() {
            return constant.GENDER.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
        statusOptions() {
            return constant.STATUS.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
        provinceOptions() {
            return addressHelper.getProvinceOptions(this.provinceData);
        },
        wardOptions() {
            return addressHelper.getWardOptionsByProvince(
                this.wardData,
                this.provinceData,
                this.selectedProvince,
            );
        },
    },
    watch: {
        item: {
            handler(value) {
                if (value) {
                    if (value.province) {
                        this.selectedProvince = value.province;
                    }
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
    async mounted() {
        await this.loadProvinceData();
        await this.loadWardData();
    },
    methods: {
        handleSubmit(values) {
            this.$emit("submit", values);
        },
        handleCancel() {
            this.$emit("cancel");
        },
        generatePassword() {
            const password = functionHelper.generateRandomString(12);
            const passwordField = this.$refs.passwordField;
            if (passwordField) {
                passwordField.setValue(password);
                passwordField.validate();
            }
        },
        handleProvinceChange(value) {
            this.selectedProvince = value;

            const wardField = this.$refs.wardField;
            if (wardField) {
                wardField.setValue("");
            }
        },
        async loadProvinceData() {
            this.provinceData = await addressHelper.loadProvinceData();
        },
        async loadWardData() {
            this.wardData = await addressHelper.loadWardData();
        },
    },
};
</script>

<style scoped></style>
