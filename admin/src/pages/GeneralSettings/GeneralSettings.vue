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

            <v-sheet
                color="primary"
                theme="dark"
                class="pa-3 mb-5 rounded d-flex align-center ga-2"
                elevation="1"
            >
                <v-icon icon="mdi-clock-outline" size="22" />
                <p class="text-h6 font-weight-bold ma-0">
                    {{ $t("system_config.notification_test") }}
                </p>
            </v-sheet>
            <v-row>
                <v-col cols="12">
                    <v-btn
                        color="primary"
                        :loading="isSending"
                        prepend-icon="mdi-bell-ring"
                        @click="sendTestNotification"
                    >
                        {{ $t("dashboard.actions.send_test_notification") }}
                    </v-btn>
                </v-col>
            </v-row>

            <v-divider class="my-5" />

            <v-sheet
                color="primary"
                theme="dark"
                class="pa-3 mb-5 rounded d-flex align-center ga-2 section-title"
                elevation="1"
            >
                <v-icon icon="mdi-wrench-outline" size="22" />
                <p class="text-h6 font-weight-bold ma-0">
                    {{ $t("system_config.login_fail_setting") }}
                </p>
            </v-sheet>
            <v-row>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="soLanDangNhapSai"
                    >
                        <div class="mb-2">
                            {{ $t("system_config.max_login_fail") }}
                            <span class="text-red"> * </span>
                        </div>
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        />
                    </VeeField>
                </v-col>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="thoiGianTamKhoaTaiKhoan"
                    >
                        <div class="mb-2">
                            {{ $t("system_config.lock_account_time") }}
                            <span class="text-red"> * </span>
                        </div>
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        />
                    </VeeField>
                </v-col>
            </v-row>

            <v-divider class="my-5" />

            <v-sheet
                color="primary"
                theme="dark"
                class="pa-3 mb-5 rounded d-flex align-center ga-2"
                elevation="1"
            >
                <v-icon icon="mdi-shield-check-outline" size="22" />
                <p class="text-h6 font-weight-bold ma-0">
                    {{ $t("system_config.two_factor_setting") }}
                </p>
            </v-sheet>
            <v-row align="center">
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
                        <div class="mb-2">
                            {{ $t("system_config.otp_expire_time") }}
                            <span class="text-red"> * </span>
                        </div>
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        />
                    </VeeField>
                </v-col>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="soThietBiDangNhapToiDa"
                    >
                        <div class="mb-2">
                            {{ $t("system_config.max_device_login") }}
                            <span class="text-red"> * </span>
                        </div>
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        />
                    </VeeField>
                </v-col>
                <v-col cols="12" md="3">
                    <VeeField
                        v-slot="{ field, errorMessage }"
                        name="thoiHanXacThucLaiThietBi"
                    >
                        <div class="mb-2">
                            {{ $t("system_config.device_verify_time") }}
                            <span class="text-red"> * </span>
                        </div>
                        <v-text-field
                            v-bind="field"
                            :error-messages="errorMessage"
                            type="number"
                            variant="outlined"
                            :readonly="!isEditing"
                            persistent-placeholder
                        />
                    </VeeField>
                </v-col>
            </v-row>

            <v-divider class="my-5" />

            <v-sheet
                color="primary"
                theme="dark"
                class="pa-3 mb-5 rounded d-flex align-center ga-2"
                elevation="1"
            >
                <v-icon icon="mdi-clock-outline" size="22" />
                <p class="text-h6 font-weight-bold ma-0">
                    {{ $t("system_config.working_time_setting") }}
                </p>
            </v-sheet>
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

            <v-divider class="my-5" />

            <v-sheet
                color="primary"
                theme="dark"
                class="pa-3 mb-5 rounded d-flex align-center ga-2"
                elevation="1"
            >
                <v-icon icon="mdi-calendar-check-outline" size="22" />
                <p class="text-h6 font-weight-bold ma-0">
                    {{ $t("system_config.check_in_setting") }}
                </p>
            </v-sheet>

            <v-card variant="outlined" class="mb-4">
                <v-card-item>
                    <v-card-title
                        class="text-subtitle-1 d-flex align-center ga-2 pa-0"
                    >
                        <v-icon icon="mdi-clock-time-five-outline" size="20" />
                        {{ $t("system_config.group_time_setting") }}
                    </v-card-title>
                </v-card-item>
                <v-card-text>
                    <v-row align="center">
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="checkInGraceMinutes"
                            >
                                <div class="mb-2">
                                    {{
                                        $t(
                                            "system_config.check_in_grace_minutes",
                                        )
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="lateLimitMinutes"
                            >
                                <div class="mb-2">
                                    {{ $t("system_config.late_limit_minutes") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="checkInEarliestMinutes"
                            >
                                <div class="mb-2">
                                    {{
                                        $t(
                                            "system_config.check_in_earliest_minutes",
                                        )
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="checkOutGraceMinutes"
                            >
                                <div class="mb-2">
                                    {{
                                        $t(
                                            "system_config.check_out_grace_minutes",
                                        )
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="checkOutLatestMinutes"
                            >
                                <div class="mb-2">
                                    {{
                                        $t(
                                            "system_config.check_out_latest_minutes",
                                        )
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>

            <v-card variant="outlined" class="mb-4">
                <v-card-item>
                    <v-card-title
                        class="text-subtitle-1 d-flex align-center ga-2 pa-0"
                    >
                        <v-icon icon="mdi-map-marker-outline" size="20" />
                        {{ $t("system_config.group_location_setting") }}
                    </v-card-title>
                </v-card-item>
                <v-card-text>
                    <v-row align="center">
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="latitude"
                            >
                                <div class="mb-2">
                                    {{ $t("system_config.latitude") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <div class="d-flex align-center ga-2">
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        type="number"
                                        variant="outlined"
                                        :readonly="!isEditing"
                                        persistent-placeholder
                                        class="flex-grow-1"
                                    />
                                    <v-btn
                                        icon
                                        variant="tonal"
                                        color="primary"
                                        class="mb-5"
                                        :loading="locating"
                                        :disabled="!isEditing"
                                        @click="getCurrentLocation"
                                    >
                                        <v-icon icon="mdi-crosshairs-gps" />
                                        <v-tooltip
                                            activator="parent"
                                            location="top"
                                        >
                                            {{
                                                $t(
                                                    "system_config.get_current_location",
                                                )
                                            }}
                                        </v-tooltip>
                                    </v-btn>
                                </div>
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="longitude"
                            >
                                <div class="mb-2">
                                    {{ $t("system_config.longitude") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <div class="d-flex align-center ga-2">
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        type="number"
                                        variant="outlined"
                                        :readonly="!isEditing"
                                        persistent-placeholder
                                        class="flex-grow-1"
                                    />
                                    <v-btn
                                        icon
                                        variant="tonal"
                                        color="primary"
                                        class="mb-5"
                                        :loading="locating"
                                        :disabled="!isEditing"
                                        @click="getCurrentLocation"
                                    >
                                        <v-icon icon="mdi-crosshairs-gps" />
                                        <v-tooltip
                                            activator="parent"
                                            location="top"
                                        >
                                            {{
                                                $t(
                                                    "system_config.get_current_location",
                                                )
                                            }}
                                        </v-tooltip>
                                    </v-btn>
                                </div>
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="radiusMeters"
                            >
                                <div class="mb-2">
                                    {{ $t("system_config.radius_meters") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="addressDisplay"
                            >
                                <div class="mb-2">
                                    {{ $t("system_config.address_display") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="ipAddress"
                            >
                                <div class="mb-2">
                                    {{ $t("system_config.ip_address") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <div class="d-flex align-center ga-2">
                                    <v-text-field
                                        v-bind="field"
                                        :error-messages="errorMessage"
                                        variant="outlined"
                                        :readonly="!isEditing"
                                        persistent-placeholder
                                        class="flex-grow-1"
                                    />
                                    <v-btn
                                        icon
                                        variant="tonal"
                                        color="primary"
                                        class="mb-5"
                                        :loading="detectingIp"
                                        :disabled="!isEditing"
                                        @click="detectIpAddress"
                                    >
                                        <v-icon icon="mdi-ip-network" />
                                        <v-tooltip
                                            activator="parent"
                                            location="top"
                                        >
                                            {{
                                                $t(
                                                    "system_config.get_current_ip",
                                                )
                                            }}
                                        </v-tooltip>
                                    </v-btn>
                                </div>
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>

            <v-card variant="outlined" class="mb-4">
                <v-card-item>
                    <v-card-title
                        class="text-subtitle-1 d-flex align-center ga-2 pa-0"
                    >
                        <v-icon icon="mdi-cellphone-link" size="20" />
                        {{ $t("system_config.group_device_setting") }}
                    </v-card-title>
                </v-card-item>
                <v-card-text>
                    <v-row align="center">
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="qrTtlSeconds"
                            >
                                <div class="mb-2">
                                    {{ $t("system_config.qr_ttl_seconds") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="photoRetentionDays"
                            >
                                <div class="mb-2">
                                    {{
                                        $t("system_config.photo_retention_days")
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="maxDevicesPerEmployee"
                            >
                                <div class="mb-2">
                                    {{
                                        $t(
                                            "system_config.max_devices_per_employee",
                                        )
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="sameDeviceMaxEmployees"
                            >
                                <div class="mb-2">
                                    {{
                                        $t(
                                            "system_config.same_device_max_employees",
                                        )
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>

            <v-card variant="outlined" class="mb-4">
                <v-card-item>
                    <v-card-title
                        class="text-subtitle-1 d-flex align-center ga-2 pa-0"
                    >
                        <v-icon icon="mdi-bell-outline" size="20" />
                        {{ $t("system_config.group_reminder_setting") }}
                    </v-card-title>
                </v-card-item>
                <v-card-text>
                    <v-row align="center">
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="remindMissingCheckIn"
                            >
                                <v-switch
                                    :model-value="field.value"
                                    :error-messages="errorMessage"
                                    :label="
                                        $t(
                                            'system_config.remind_missing_check_in',
                                        )
                                    "
                                    color="primary"
                                    :readonly="!isEditing"
                                    @update:model-value="
                                        field['onChange']($event)
                                    "
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="checkInReminderMinutesBefore"
                            >
                                <div class="mb-2">
                                    {{
                                        $t(
                                            "system_config.check_in_reminder_minutes_before",
                                        )
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="remindMissingCheckOut"
                            >
                                <v-switch
                                    :model-value="field.value"
                                    :error-messages="errorMessage"
                                    :label="
                                        $t(
                                            'system_config.remind_missing_check_out',
                                        )
                                    "
                                    color="primary"
                                    :readonly="!isEditing"
                                    @update:model-value="
                                        field['onChange']($event)
                                    "
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="3">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="checkOutReminderMinutesBefore"
                            >
                                <div class="mb-2">
                                    {{
                                        $t(
                                            "system_config.check_out_reminder_minutes_before",
                                        )
                                    }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="number"
                                    variant="outlined"
                                    :readonly="!isEditing"
                                    persistent-placeholder
                                />
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>
        </VeeForm>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { usePermission } from "@/hooks/usePermission";
import { cauHinhChungSchema } from "@/utils/schemas/cauHinhChung";
import { Field, Form } from "vee-validate";
import { mapActions, mapGetters } from "vuex";
import { toast } from "@/main";
import axiosInstance from "@/configs/axios";

export default {
    components: {
        VeeField: Field,
        VeeForm: Form,
    },
    data() {
        return {
            isEditing: false,
            cauHinhChungSchema,
            locating: false,
            detectingIp: false,
            isSending: false,
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
        async sendTestNotification() {
            this.isSending = true;
            try {
                const res = await axiosInstance.get("/mercure/test");
                console.log("[Mercure] API publish response:", res);
            } catch (error) {
                console.error(
                    this.$t("dashboard.logs.send_notification_error"),
                    error,
                );
            } finally {
                this.isSending = false;
            }
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
        getCurrentLocation() {
            if (!navigator.geolocation) {
                toast.error(this.$t("system_config.geolocation_not_supported"));
                return;
            }

            this.locating = true;
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    const form = this.$refs.formRef;
                    if (form) {
                        form.setFieldValue("latitude", latitude);
                        form.setFieldValue("longitude", longitude);
                    }
                    this.locating = false;
                },
                (err) => {
                    this.locating = false;
                    const messages = {
                        1: this.$t(
                            "system_config.geolocation_permission_denied",
                        ),
                        2: this.$t("system_config.geolocation_unavailable"),
                        3: this.$t("system_config.geolocation_timeout"),
                    };
                    toast.error(messages[err.code] || err.message);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                },
            );
        },
        async detectIpAddress() {
            this.detectingIp = true;
            try {
                const response = await fetch(
                    "https://api.ipify.org?format=json",
                );
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const data = await response.json();
                const form = this.$refs.formRef;
                if (form && data.ip) {
                    form.setFieldValue("ipAddress", data.ip);
                }
            } catch (err) {
                toast.error(
                    this.$t("system_config.detect_ip_failed", {
                        message: err.message,
                    }),
                );
            } finally {
                this.detectingIp = false;
            }
        },
    },
};
</script>

<style></style>
