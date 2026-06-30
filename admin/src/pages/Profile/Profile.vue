<template>
    <div>
        <!-- Header đẹp mắt với background gradient nhẹ -->
        <div class="profile-header d-flex align-center pa-6 text-white rounded-lg mb-6">
            <v-avatar size="80" class="mr-4 border-lg border-white">
                <v-img
                    v-if="currentUser?.avatarUrl"
                    :src="currentUser?.avatarUrl"
                    alt="Avatar"
                />
                <v-icon v-else size="50">mdi-account</v-icon>
            </v-avatar>
            <div>
                <h2 class="text-h5 font-weight-bold">
                    {{ currentUser?.name || "Chưa cập nhật" }}
                </h2>
                <p class="text-subtitle-1 opacity-80 mb-0">
                    {{ currentUser?.email }}
                </p>
            </div>
        </div>

        <v-tabs v-model="tab" color="primary" class="mb-6">
            <v-tab value="info" class="text-capitalize">
                <v-icon start>mdi-account-circle</v-icon>
                {{ $t("auth.personal_info") }}
            </v-tab>
            <v-tab value="security" class="text-capitalize">
                <v-icon start>mdi-shield-lock</v-icon>
                {{ $t("auth.security") }}
            </v-tab>
        </v-tabs>

        <v-window v-model="tab" :touch="false">
            <!-- TAB 1: THÔNG TIN CÁ NHÂN -->
            <v-window-item value="info">
                <VeeForm
                    ref="formRef"
                    as="form"
                    :validation-schema="userSchema"
                    @submit="handleUpdateProfile"
                >
                    <v-row>
                        <!-- ===== THÔNG TIN CÁ NHÂN ===== -->
                        <v-col cols="12">
                            <div class="form-section">
                                <div class="form-section__title">
                                    {{ $t("auth.personal_info") }}
                                </div>
                                <v-row>
                                    <!-- Ảnh đại diện -->
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

                                    <!-- Mã nhân viên (Disabled) -->
                                    <v-col cols="12" md="4">
                                        <VeeField
                                            v-slot="{ field, errorMessage }"
                                            name="maNhanVien"
                                        >
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.ma_nhan_vien") }}
                                            </div>
                                            <v-text-field
                                                v-bind="field"
                                                :error-messages="errorMessage"
                                                type="text"
                                                variant="outlined"
                                                disabled
                                                bg-color="grey-lighten-4"
                                            />
                                        </VeeField>
                                    </v-col>

                                    <!-- Họ và tên -->
                                    <v-col cols="12" md="4">
                                        <VeeField
                                            v-slot="{ field, errorMessage }"
                                            name="name"
                                        >
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.ho_va_ten") }}
                                                <span class="text-red">*</span>
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
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.gioi_tinh") }}
                                                <span class="text-red">*</span>
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
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.ngay_sinh") }}
                                                <span class="text-red">*</span>
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
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.cmnd") }}
                                                <span class="text-red">*</span>
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

                                    <!-- Ngày cấp CMND -->
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
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.ngay_cap_cmnd") }}
                                                <span class="text-red">*</span>
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

                                    <!-- Nơi cấp CMND -->
                                    <v-col cols="12" md="4">
                                        <VeeField
                                            v-slot="{ field, errorMessage }"
                                            name="noiCapCmnd"
                                        >
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.noi_cap_cmnd") }}
                                                <span class="text-red">*</span>
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
                                <div class="form-section__title">
                                    Thông tin liên hệ
                                </div>
                                <v-row>
                                    <!-- Email (Disabled) -->
                                    <v-col cols="12" md="4">
                                        <VeeField
                                            v-slot="{ field, errorMessage }"
                                            name="email"
                                        >
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.email") }}
                                            </div>
                                            <v-text-field
                                                v-bind="field"
                                                :error-messages="errorMessage"
                                                type="text"
                                                variant="outlined"
                                                disabled
                                                bg-color="grey-lighten-4"
                                            />
                                        </VeeField>
                                    </v-col>

                                    <!-- Số điện thoại -->
                                    <v-col cols="12" md="4">
                                        <VeeField
                                            v-slot="{ field, errorMessage }"
                                            name="phone"
                                        >
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.so_dien_thoai") }}
                                                <span class="text-red">*</span>
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
                                            v-slot="{ field, errorMessage, handleChange }"
                                            name="province"
                                        >
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.tinh_thanh_pho") }}
                                                <span class="text-red">*</span>
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
                                            v-slot="{ field, errorMessage, handleChange }"
                                            name="ward"
                                        >
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.xa_phuong") }}
                                                <span class="text-red">*</span>
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
                                            <div class="mb-2 font-weight-medium text-body-2">
                                                {{ $t("field.dia_chi") }}
                                                <span class="text-red">*</span>
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

                        <!-- Trạng thái (Disabled) -->
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
                                <div class="mb-2 font-weight-medium text-body-2">
                                    {{ $t("field.trang_thai") }}
                                </div>
                                <v-select
                                    :model-value="field.value"
                                    :items="statusOptions"
                                    item-title="text"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    disabled
                                    bg-color="grey-lighten-4"
                                    @update:model-value="handleChange"
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>

                        <v-col cols="12">
                            <div class="d-flex justify-end mt-4">
                                <v-btn
                                    color="primary"
                                    type="submit"
                                    :loading="$store.state.isLoading"
                                    class="px-6"
                                >
                                    {{ $t("auth.save_changes") }}
                                </v-btn>
                            </div>
                        </v-col>
                    </v-row>
                </VeeForm>
            </v-window-item>

            <!-- TAB 2: BẢO MẬT (ĐỔI MẬT KHẨU) -->
            <v-window-item value="security">
                <VeeForm
                    ref="securityFormRef"
                    as="form"
                    :validation-schema="changePasswordSchema"
                    @submit="handleChangePassword"
                >
                    <v-row>
                        <v-col cols="12" md="6">
                            <!-- Mật khẩu hiện tại -->
                            <div class="mb-4">
                                <VeeField
                                    v-slot="{ field, errorMessage }"
                                    name="currentPassword"
                                >
                                    <div class="mb-2 font-weight-medium text-body-2">
                                        {{ $t("auth.current_password") }}
                                        <span class="text-red">*</span>
                                    </div>
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        type="password"
                                        variant="outlined"
                                        :placeholder="$t('auth.enter_current_password')"
                                    />
                                </VeeField>
                            </div>

                            <!-- Mật khẩu mới -->
                            <div class="mb-4">
                                <VeeField
                                    v-slot="{ field, errorMessage }"
                                    name="newPassword"
                                >
                                    <div class="mb-2 font-weight-medium text-body-2">
                                        {{ $t("auth.new_password") }}
                                        <span class="text-red">*</span>
                                    </div>
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        type="password"
                                        variant="outlined"
                                        :placeholder="$t('auth.enter_new_password')"
                                    />
                                </VeeField>
                            </div>

                            <!-- Xác nhận mật khẩu mới -->
                            <div class="mb-6">
                                <VeeField
                                    v-slot="{ field, errorMessage }"
                                    name="confirmPassword"
                                >
                                    <div class="mb-2 font-weight-medium text-body-2">
                                        {{ $t("auth.confirm_password") }}
                                        <span class="text-red">*</span>
                                    </div>
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        type="password"
                                        variant="outlined"
                                        :placeholder="$t('auth.enter_confirm_password')"
                                    />
                                </VeeField>
                            </div>

                            <div class="d-flex justify-end">
                                <v-btn
                                    color="primary"
                                    type="submit"
                                    :loading="$store.state.isLoading"
                                    class="px-6"
                                >
                                    {{ $t("auth.change_password_btn") }}
                                </v-btn>
                            </div>
                        </v-col>
                    </v-row>
                </VeeForm>
            </v-window-item>
        </v-window>
    </div>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import DatePicker from "@/components/DatePicker.vue";
import ImageSelector from "@/components/ImageSelector.vue";
import { userSchema } from "@/utils/schemas/user";
import { changePasswordSchema } from "@/utils/schemas/changePassword";
import { constant } from "@/utils/constants/constant";
import { addressHelper } from "@/helpers/addressHelper";
import { mapActions, mapGetters } from "vuex";

export default {
    name: "ProfilePage",
    components: {
        VeeForm,
        VeeField,
        DatePicker,
        ImageSelector,
    },
    data() {
        return {
            tab: "info",
            userSchema,
            changePasswordSchema,
            selectedProvince: "",
        };
    },
    computed: {
        ...mapGetters("auth", ["currentUser"]),
        ...mapGetters("user", ["provinceData", "wardDataByProvince"]),
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
            return addressHelper.getWardOptions(
                this.wardDataByProvince(this.selectedProvince),
            );
        },
    },
    watch: {
        currentUser: {
            handler(value) {
                if (value) {
                    if (value.province) {
                        this.selectedProvince = value.province;
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
        await this.getProvince();
    },
    methods: {
        ...mapActions("user", ["fetchProvince", "fetchWard"]),
        ...mapActions("auth", ["updateProfile", "changePasswordProfile"]),
        async getProvince() {
            await this.fetchProvince();
        },
        async getWard(provinceId) {
            await this.fetchWard({ provinceId });
        },
        handleProvinceChange(value) {
            this.selectedProvince = value;
            const wardField = this.$refs.wardField;
            if (wardField) {
                wardField.setValue("");
            }
        },
        async handleUpdateProfile(values) {
            await this.updateProfile({
                data: values,
                callback: () => {},
            });
        },
        async handleChangePassword(values, { resetForm }) {
            await this.changePasswordProfile({
                data: values,
                callback: () => {
                    resetForm();
                },
            });
        },
    },
};
</script>

<style scoped>
.profile-header {
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
}

/* Border bao quanh mỗi phần của form */
.form-section {
    border: 1px solid #1976d2;
    border-radius: 10px;
    padding: 20px 20px 8px;
    position: relative;
    margin-top: 20px;
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
