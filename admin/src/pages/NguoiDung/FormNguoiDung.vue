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
            <v-row>
                <v-col cols="3">
                    <VeeField
                        v-slot="{ handleChange, errorMessage, value }"
                        name="avatar"
                    >
                        <ImageSelector
                            :label="$t('field.anh_dai_dien')"
                            :required="false"
                            :is-multiple="false"
                            :model-value="value"
                            :error-message="errorMessage"
                            @selected="handleChange($event?.path || null)"
                        />
                    </VeeField>
                </v-col>
                <v-col cols="12">
                    <v-row>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="name"
                            >
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
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="email"
                            >
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
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="phone"
                            >
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
                                v-slot="{ field, errorMessage, handleChange }"
                                name="boPhanId"
                            >
                                <div class="mb-2">
                                    {{ $t("field.bo_phan_mac_dinh") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-autocomplete
                                    :model-value="field.value"
                                    name="boPhanId"
                                    :items="boPhanData"
                                    item-title="label"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    clearable
                                    :placeholder="`${$t('base.enter')} ${$t('field.bo_phan_mac_dinh')}`"
                                    @update:model-value="handleChange"
                                    @blur="field.onBlur"
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
        <LoadingForm v-if="mode === 'update' && !item" :is-loading="true" />
    </div>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import DatePicker from "@/components/DatePicker.vue";
import { functionHelper } from "@/helpers/functionHelper";
import { addressHelper } from "@/helpers/addressHelper";
import { userSchema } from "@/utils/schemas/user";
import { constant } from "@/utils/constants/constant";
import { getAllData, getDataSelect } from "@/services/bases/getData";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import ImageSelector from "@/components/ImageSelector.vue";
import LoadingForm from "@/components/LoadingForm.vue";

export default {
    components: {
        LoadingForm,
        VeeForm,
        VeeField,
        DatePicker,
        ImageSelector,
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
            validationSchema: userSchema,
            initialValues: {
                avatar: null,
                name: "",
                email: "",
                phone: "",
                birthday: "",
                gender: "",
                province: "",
                ward: "",
                address: "",
                maBoPhan: "",
                status: 1,
            },
            provinceData: {},
            wardData: {},
            boPhanData: [],
            selectedProvince: "",
            selectedBoPhan: null,
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
            return addressHelper.getWardOptions(this.wardData);
        },
    },
    watch: {
        item: {
            handler(value) {
                if (value) {
                    if (value.province) {
                        this.selectedProvince = value.province;
                    }
                    if (value.boPhanId) {
                        this.selectedBoPhan = value.boPhanId;
                    }
                    this.$nextTick(() => {
                        if (this.$refs.formRef) {
                            const formData = {
                                ...value,
                                avatar: value.image || null,
                            };
                            this.$refs.formRef.setValues(formData);
                        }
                    });
                }
            },
            deep: true,
            immediate: true,
        },
        selectedProvince: {
            handler(value) {
                if (value) {
                    this.getWard(value);
                }
            },
            deep: true,
            immediate: true,
        },
    },
    async mounted() {
        await Promise.all([this.getProvince(), this.getBoPhan()]);
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
        async getProvince() {
            const res = await getAllData(API_ROUTES_CONFIG.user + "/province");
            this.provinceData = res;
        },
        async getWard(provinceId) {
            const res = await getAllData(
                API_ROUTES_CONFIG.user + "/ward/" + provinceId,
            );
            this.wardData = res;
        },
        async getBoPhan() {
            const res = await getDataSelect(API_ROUTES_CONFIG.boPhan);
            this.boPhanData = res;
        },
    },
};
</script>

<style scoped></style>
