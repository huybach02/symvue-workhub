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
                <!-- ===== THÔNG TIN CÁ NHÂN ===== -->
                <v-col cols="12">
                    <div class="form-section">
                        <div class="form-section__title">Thông tin cá nhân</div>
                        <v-row>
                            <!-- Ảnh đại diện - nằm riêng 1 dòng -->
                            <v-col cols="12" md="4">
                                <VeeField
                                    v-slot="{
                                        handleChange,
                                        errorMessage,
                                        value,
                                    }"
                                    name="avatar"
                                >
                                    <ImageSelector
                                        :label="$t('field.anh_dai_dien')"
                                        :required="false"
                                        :is-multiple="false"
                                        :model-value="value"
                                        :error-message="errorMessage"
                                        @selected="
                                            handleChange($event?.path || null)
                                        "
                                    />
                                </VeeField>
                            </v-col>

                            <!-- Các trường thông tin cá nhân -->

                            <!-- Mã nhân viên -->
                            <v-col cols="12" md="4">
                                <VeeField
                                    v-slot="{ field, errorMessage }"
                                    name="maNhanVien"
                                >
                                    <div class="mb-2">
                                        {{ $t("field.ma_nhan_vien") }}
                                        <span class="text-red"> * </span>
                                    </div>
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        type="text"
                                        variant="outlined"
                                        :placeholder="`${$t('base.enter')} ${$t('field.ma_nhan_vien')}`"
                                    />
                                </VeeField>
                            </v-col>

                            <!-- Họ và tên -->
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

                            <!-- Giới tính -->
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

                            <!-- Ngày sinh -->
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

                            <!-- CMND/CCCD -->
                            <v-col cols="12" md="4">
                                <VeeField
                                    v-slot="{ field, errorMessage }"
                                    name="cmnd"
                                >
                                    <div class="mb-2">
                                        {{ $t("field.cmnd") }}
                                    </div>
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        type="text"
                                        variant="outlined"
                                        :placeholder="`${$t('base.enter')} ${$t('field.cmnd')}`"
                                    />
                                </VeeField>
                            </v-col>

                            <!-- Ngày cấp CMND/CCCD -->
                            <v-col cols="12" md="4">
                                <VeeField
                                    v-slot="{
                                        field,
                                        errorMessage,
                                        handleChange,
                                        handleBlur,
                                    }"
                                    name="ngayCapCmnd"
                                >
                                    <div class="mb-2">
                                        {{ $t("field.ngay_cap_cmnd") }}
                                    </div>
                                    <DatePicker
                                        :model-value="field.value"
                                        :error-messages="errorMessage"
                                        :placeholder="`${$t('base.enter')} ${$t('field.ngay_cap_cmnd')}`"
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

                            <!-- Nơi cấp CMND/CCCD -->
                            <v-col cols="12" md="4">
                                <VeeField
                                    v-slot="{ field, errorMessage }"
                                    name="noiCapCmnd"
                                >
                                    <div class="mb-2">
                                        {{ $t("field.noi_cap_cmnd") }}
                                    </div>
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        type="text"
                                        variant="outlined"
                                        :placeholder="`${$t('base.enter')} ${$t('field.noi_cap_cmnd')}`"
                                    />
                                </VeeField>
                            </v-col>
                        </v-row>
                    </div>
                </v-col>

                <!-- ===== THÔNG TIN LIÊN HỆ ===== -->
                <v-col cols="12">
                    <div class="form-section">
                        <div class="form-section__title">Thông tin liên hệ</div>
                        <v-row>
                            <!-- Email -->
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

                            <!-- Số điện thoại -->
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

                            <!-- Tỉnh/Thành phố -->
                            <v-col cols="12" md="4">
                                <VeeField
                                    v-slot="{
                                        field,
                                        errorMessage,
                                        handleChange,
                                    }"
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

                            <!-- Xã/Phường -->
                            <v-col cols="12" md="4">
                                <VeeField
                                    ref="wardField"
                                    v-slot="{
                                        field,
                                        errorMessage,
                                        handleChange,
                                    }"
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

                            <!-- Địa chỉ -->
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
                    </div>
                </v-col>

                <v-col cols="12">
                    <v-row>
                        <!-- Trạng thái làm việc -->
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
                                    :placeholder="`${$t('base.enter')} ${$t('field.trang_thai_lam_viec')}`"
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
import { getAllData } from "@/services/bases/getData";
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
                maNhanVien: "",
                name: "",
                gender: "",
                birthday: "",
                cmnd: "",
                ngayCapCmnd: "",
                noiCapCmnd: "",
                status: 1,
                email: "",
                phone: "",
                province: "",
                ward: "",
                address: "",
            },
            provinceData: {},
            wardData: {},
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
        await Promise.all([this.getProvince(), this.getMaNhanVien()]);
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
        async getMaNhanVien() {
            if (this.mode !== "create") return;
            const res = await getAllData(
                API_ROUTES_CONFIG.user + "/get-ma-nhan-vien",
            );
            this.$nextTick(() => {
                if (this.$refs.formRef) {
                    this.$refs.formRef.setFieldValue("maNhanVien", res);
                }
            });
        },
    },
};
</script>

<style scoped>
/* Border bao quanh mỗi phần của form */
.form-section {
    border: 1px solid #1976d2;
    border-radius: 10px;
    padding: 20px 20px 8px;
    position: relative;
    margin-top: 8px;
    box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
    background-color: #fff;
}

/* Tiêu đề nổi lên phía trên đường border */
.form-section__title {
    position: absolute;
    top: -14px;
    left: 14px;
    background-color: #1976d2;
    color: #ffffff;
    padding: 2px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    box-shadow: 0 2px 6px rgba(25, 118, 210, 0.35);
}
</style>
