<template>
    <div>
        <VeeForm
            v-if="dataLoaded"
            as="form"
            :validation-schema="cauHinhChungSchema"
            :initial-values="initialValues"
            @submit="onSubmit"
        >
            <v-row v-if="!isEditing">
                <v-col cols="12">
                    <div class="d-flex ga-2 justify-end">
                        <v-btn
                            color="primary"
                            class="d-flex align-center"
                            @click="isEditing = !isEditing"
                        >
                            <v-icon icon="mdi-pencil" size="18" class="mr-1" />
                            {{ $t("system_config.edit_button") }}
                        </v-btn>
                    </div>
                </v-col>
            </v-row>
            <v-row v-else>
                <v-col cols="12">
                    <div class="d-flex ga-2 justify-end">
                        <v-btn
                            variant="tonal"
                            class="d-flex align-center"
                            @click="isEditing = !isEditing"
                        >
                            <v-icon icon="mdi-close" size="18" class="mr-1" />
                            {{ $t("system_config.cancel_button") }}
                        </v-btn>
                        <v-btn
                            :loading="this.$store.state.isLoading"
                            color="primary"
                            class="d-flex align-center"
                            type="submit"
                        >
                            <v-icon icon="mdi-check" size="18" class="mr-1" />
                            {{ $t("system_config.save_button") }}
                        </v-btn>
                    </div>
                </v-col>
            </v-row>

            <v-divider class="my-5" />

            <div class="text-h6 font-weight-bold mb-5 d-flex align-center ga-2">
                <v-icon icon="mdi-wrench-outline" size="22" />
                <p>{{ $t("system_config.login_fail_setting") }}</p>
            </div>
            <v-row>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="soLanDangNhapSai"
                    >
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        >
                            <template #label>
                                {{ $t("system_config.max_login_fail") }}
                                <span class="text-red">*</span>
                            </template>
                        </v-text-field>
                    </VeeField>
                </v-col>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="thoiGianTamKhoaTaiKhoan"
                    >
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        >
                            <template #label>
                                {{ $t("system_config.lock_account_time") }}
                                <span class="text-red">*</span>
                            </template>
                        </v-text-field>
                    </VeeField>
                </v-col>
            </v-row>

            <v-divider class="my-5" />

            <div class="text-h6 font-weight-bold mb-5 d-flex align-center ga-2">
                <v-icon icon="mdi-shield-check-outline" size="22" />
                <p>{{ $t("system_config.two_factor_setting") }}</p>
            </div>
            <v-row>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="xacThuc2YeuTo"
                    >
                        <v-switch
                            :model-value="field.value"
                            :error-messages="errorMessage"
                            :label="$t('system_config.enable_two_factor')"
                            color="primary"
                            :readonly="!isEditing"
                            @update:model-value="field['onChange']($event)"
                        />
                    </VeeField>
                </v-col>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="thoiGianHetHanMaOtp"
                    >
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        >
                            <template #label>
                                {{ $t("system_config.otp_expire_time") }}
                                <span class="text-red">*</span>
                            </template>
                        </v-text-field>
                    </VeeField>
                </v-col>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="thoiHanXacThucLaiThietBi"
                    >
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        >
                            <template #label>
                                {{ $t("system_config.device_verify_time") }}
                                <span class="text-red">*</span>
                            </template>
                        </v-text-field>
                    </VeeField>
                </v-col>
            </v-row>

            <v-divider class="my-5" />

            <div class="text-h6 font-weight-bold mb-5 d-flex align-center ga-2">
                <v-icon icon="mdi-clock-outline" size="22" />
                <p>{{ $t("system_config.working_time_setting") }}</p>
            </div>
            <v-row>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="kiemTraThoiGianLamViec"
                    >
                        <v-switch
                            :model-value="field.value"
                            :error-messages="errorMessage"
                            :label="
                                $t('system_config.enable_working_time_check')
                            "
                            color="primary"
                            :readonly="!isEditing"
                            @update:model-value="field['onChange']($event)"
                        />
                    </VeeField>
                </v-col>
            </v-row>
        </VeeForm>
    </div>
</template>

<script>
import { cauHinhChungService } from "@/services/cauHinhChungService";
import { cauHinhChungSchema } from "@/utils/schemas/cauHinhChung";
import { Field, Form } from "vee-validate";

export default {
    components: {
        VeeField: Field,
        VeeForm: Form,
    },
    data() {
        return {
            isEditing: false,
            dataLoaded: false,
            cauHinhChungSchema,
            initialValues: {
                soLanDangNhapSai: 0,
                thoiGianTamKhoaTaiKhoan: 0,
                xacThuc2YeuTo: false,
                thoiGianHetHanMaOtp: 0,
                thoiHanXacThucLaiThietBi: 0,
                kiemTraThoiGianLamViec: false,
            },
        };
    },
    created() {
        this.getAll();
    },
    methods: {
        async getAll() {
            this.$store.commit("setIsLoading");
            const response = await cauHinhChungService.getAll();

            const mapping = {
                SO_LAN_DANG_NHAP_SAI_TOI_DA: "soLanDangNhapSai",
                THOI_GIAN_KHOA_TAI_KHOAN: "thoiGianTamKhoaTaiKhoan",
                XAC_THUC_2_YEU_TO: "xacThuc2YeuTo",
                THOI_GIAN_HET_HAN_OTP: "thoiGianHetHanMaOtp",
                THOI_HAN_XAC_THUC_LAI_THIET_BI: "thoiHanXacThucLaiThietBi",
                CHECK_THOI_GIAN_LAM_VIEC: "kiemTraThoiGianLamViec",
            };

            const mappedData = {};
            response.data.forEach((item) => {
                const key = mapping[item.tenCauHinh];
                if (key) {
                    if (
                        key === "xacThuc2YeuTo" ||
                        key === "kiemTraThoiGianLamViec"
                    ) {
                        mappedData[key] = item.giaTri === "1";
                    } else {
                        mappedData[key] = parseInt(item.giaTri);
                    }
                }
            });

            this.initialValues = mappedData;
            this.dataLoaded = true;
            this.$store.commit("unsetIsLoading");
        },
        async onSubmit(values) {
            this.$store.commit("setIsLoading");
            const response = await cauHinhChungService.update(values);
            if (response.success) {
                this.isEditing = false;
            }
            this.$store.commit("unsetIsLoading");
        },
    },
};
</script>

<style></style>
