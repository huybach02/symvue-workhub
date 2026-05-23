<template>
    <div>
        <VeeForm
            v-if="dataLoaded"
            ref="formRef"
            as="form"
            :validation-schema="cauHinhChungSchema"
            :initial-values="initialValues"
            @submit="onSubmit"
        >
            <div v-if="permission?.edit">
                <v-row v-if="!isEditing">
                    <v-col cols="12">
                        <div class="d-flex ga-2 justify-end">
                            <v-btn
                                color="primary"
                                class="d-flex align-center"
                                @click="isEditing = !isEditing"
                            >
                                <v-icon
                                    icon="mdi-pencil"
                                    size="18"
                                    class="mr-1"
                                />
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
                                @click="cancelEdit"
                            >
                                <v-icon
                                    icon="mdi-close"
                                    size="18"
                                    class="mr-1"
                                />
                                {{ $t("system_config.cancel_button") }}
                            </v-btn>
                            <v-btn
                                :loading="saving"
                                color="primary"
                                class="d-flex align-center"
                                type="submit"
                            >
                                <v-icon
                                    icon="mdi-check"
                                    size="18"
                                    class="mr-1"
                                />
                                {{ $t("system_config.save_button") }}
                            </v-btn>
                        </div>
                    </v-col>
                </v-row>
            </div>

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
                        name="soThietBiDangNhapToiDa"
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
                                {{ $t("system_config.max_device_login") }}
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
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { usePermission } from "@/hooks/usePermission";
import { cauHinhChungSchema } from "@/utils/schemas/cauHinhChung";
import { Field, Form } from "vee-validate";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        VeeField: Field,
        VeeForm: Form,
    },
    data() {
        return {
            isEditing: false,
            cauHinhChungSchema,
        };
    },
    computed: {
        ...mapGetters("generalSettings", [
            "dataLoaded",
            "initialValues",
            "saving",
        ]),
        permission() {
            return usePermission(API_ROUTES_CONFIG.generalSettings);
        },
    },
    created() {
        this.getAll();
    },
    methods: {
        ...mapActions("generalSettings", ["fetchSettings", "updateSettings"]),
        async getAll() {
            await this.fetchSettings();
        },
        async onSubmit(values) {
            const response = await this.updateSettings(values);

            if (response) {
                this.isEditing = false;
            }
        },
        cancelEdit() {
            this.isEditing = false;

            if (this.$refs.formRef) {
                this.$refs.formRef.resetForm();
            }
        },
    },
};
</script>

<style></style>
