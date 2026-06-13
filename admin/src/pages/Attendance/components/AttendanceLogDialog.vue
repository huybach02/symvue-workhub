<template>
    <v-dialog
        :model-value="modelValue"
        max-width="980"
        width="calc(100vw - 24px)"
        scrollable
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <v-card>
            <div
                class="d-flex align-start justify-space-between ga-4 px-6 py-5"
            >
                <div class="d-flex align-start ga-3 flex-grow-1">
                    <v-avatar color="primary" variant="tonal" size="44">
                        <v-icon icon="mdi-history" size="24" />
                    </v-avatar>

                    <div class="flex-grow-1">
                        <div class="text-h6 font-weight-bold">
                            {{ $t("attendance.logs.title") }}
                        </div>

                        <div class="d-flex flex-wrap align-center ga-2 mt-2">
                            <v-chip
                                v-if="actionLabel"
                                color="primary"
                                size="small"
                                variant="tonal"
                            >
                                {{ actionLabel }}
                            </v-chip>

                            <v-chip
                                v-if="employeeName"
                                color="info"
                                size="small"
                                variant="tonal"
                            >
                                {{ employeeName }}
                            </v-chip>

                            <v-chip
                                v-if="shiftLabel"
                                color="secondary"
                                size="small"
                                variant="tonal"
                            >
                                {{ shiftLabel }}
                            </v-chip>
                        </div>
                    </div>
                </div>

                <v-btn
                    icon="mdi-close"
                    variant="text"
                    @click="$emit('update:modelValue', false)"
                />
            </div>

            <v-divider />

            <v-card-text class="pa-6">
                <template v-if="normalizedLogs.length">
                    <v-row v-if="hasCommonSection" class="mb-4">
                        <v-col
                            v-if="commonFields.length || commonQrToken"
                            cols="12"
                            md="12"
                        >
                            <v-card
                                variant="flat"
                                bg-color="white"
                                elevation="2"
                            >
                                <v-card-text class="pa-4 pa-md-5">
                                    <v-row>
                                        <v-col
                                            v-if="commonFields.length"
                                            cols="12"
                                        >
                                            <v-row>
                                                <v-col
                                                    v-for="field in commonFields"
                                                    :key="field.key"
                                                    cols="12"
                                                    sm="6"
                                                    md="4"
                                                >
                                                    <v-card
                                                        variant="flat"
                                                        elevation="1"
                                                        class="h-100"
                                                    >
                                                        <v-card-text
                                                            class="pa-4"
                                                        >
                                                            <div
                                                                class="d-flex align-center ga-3"
                                                            >
                                                                <v-avatar
                                                                    size="36"
                                                                    color="primary"
                                                                    variant="tonal"
                                                                >
                                                                    <v-icon
                                                                        :icon="
                                                                            field.icon
                                                                        "
                                                                        size="18"
                                                                    />
                                                                </v-avatar>

                                                                <div>
                                                                    <div
                                                                        class="text-caption text-medium-emphasis"
                                                                    >
                                                                        {{
                                                                            field.label
                                                                        }}
                                                                    </div>
                                                                    <div
                                                                        class="text-body-2 font-weight-medium"
                                                                    >
                                                                        {{
                                                                            field.value
                                                                        }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </v-card-text>
                                                    </v-card>
                                                </v-col>
                                            </v-row>
                                        </v-col>

                                        <v-col v-if="commonQrToken" cols="12">
                                            <v-card
                                                variant="flat"
                                                elevation="1"
                                            >
                                                <v-card-text class="pa-4">
                                                    <div
                                                        class="text-body-2 font-weight-bold text-center mb-3"
                                                    >
                                                        {{
                                                            $t(
                                                                "attendance.logs.fields.qr_token",
                                                            )
                                                        }}
                                                    </div>

                                                    <div
                                                        v-if="commonQrCodeUrl"
                                                        class="d-flex justify-center mt-3"
                                                    >
                                                        <img
                                                            :src="
                                                                commonQrCodeUrl
                                                            "
                                                            :alt="
                                                                $t(
                                                                    'attendance.logs.fields.qr_token',
                                                                )
                                                            "
                                                            width="104"
                                                        />
                                                    </div>

                                                    <div
                                                        v-else
                                                        class="text-body-2 font-weight-medium mt-3"
                                                    >
                                                        {{
                                                            $t(
                                                                "base.not_available",
                                                            )
                                                        }}
                                                    </div>

                                                    <div
                                                        class="text-center text-body-2 font-weight-medium mt-3"
                                                    >
                                                        {{ commonQrToken }}
                                                    </div>
                                                </v-card-text>
                                            </v-card>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-card variant="flat" bg-color="white" elevation="2">
                        <v-list bg-color="white" class="py-2">
                            <template
                                v-for="(log, index) in normalizedLogs"
                                :key="log.panelKey"
                            >
                                <v-list-item class="px-5 py-4">
                                    <template #prepend>
                                        <v-avatar
                                            :color="
                                                getValidationStatusColor(
                                                    log.validationStatus,
                                                )
                                            "
                                            size="38"
                                            variant="tonal"
                                        >
                                            <v-icon
                                                :icon="
                                                    getValidationStatusIcon(
                                                        log.validationStatus,
                                                    )
                                                "
                                                size="18"
                                            />
                                        </v-avatar>
                                    </template>

                                    <v-list-item-title>
                                        <div
                                            class="d-flex align-center flex-wrap ga-2"
                                        >
                                            <v-chip
                                                size="x-small"
                                                variant="tonal"
                                                color="default"
                                            >
                                                {{
                                                    $t(
                                                        "attendance.logs.labels.log_id",
                                                        {
                                                            id: log.id || "--",
                                                        },
                                                    )
                                                }}
                                            </v-chip>
                                        </div>
                                    </v-list-item-title>

                                    <v-list-item-subtitle>
                                        <div
                                            class="text-body-2 text-high-emphasis mt-1"
                                        >
                                            {{
                                                getValidationReasonLabel(
                                                    log.validationReason,
                                                )
                                            }}
                                        </div>

                                        <div
                                            v-if="
                                                buildLogMetaChips(log).length >
                                                0
                                            "
                                            class="d-flex flex-wrap ga-2 mt-2"
                                        >
                                            <v-chip
                                                v-for="chip in buildLogMetaChips(
                                                    log,
                                                )"
                                                :key="`${log.panelKey}-${chip.key}`"
                                                size="x-small"
                                                :color="chip.color"
                                                variant="tonal"
                                            >
                                                {{ chip.label }}
                                            </v-chip>
                                        </div>
                                    </v-list-item-subtitle>

                                    <template #append>
                                        <v-chip
                                            :color="
                                                getValidationStatusColor(
                                                    log.validationStatus,
                                                )
                                            "
                                            size="small"
                                            variant="flat"
                                        >
                                            {{
                                                getValidationStatusLabel(
                                                    log.validationStatus,
                                                )
                                            }}
                                        </v-chip>
                                    </template>
                                </v-list-item>

                                <v-divider
                                    v-if="index < normalizedLogs.length - 1"
                                    inset
                                />
                            </template>
                        </v-list>
                    </v-card>
                </template>

                <v-card v-else elevation="1">
                    <v-card-text class="py-8 text-center text-medium-emphasis">
                        {{ $t("attendance.logs.empty") }}
                    </v-card-text>
                </v-card>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import QRCode from "qrcode";

const COMMON_LOG_FIELD_CONFIGS = Object.freeze([
    {
        key: "ipAddress",
        labelKey: "attendance.logs.fields.ip_address",
        icon: "mdi-ip-network-outline",
    },
    {
        key: "deviceId",
        labelKey: "attendance.logs.fields.device_id",
        icon: "mdi-cellphone-link",
    },
    {
        key: "latitude",
        labelKey: "attendance.logs.fields.latitude",
        icon: "mdi-latitude",
    },
    {
        key: "longtitude",
        labelKey: "attendance.logs.fields.longtitude",
        icon: "mdi-longitude",
    },
    {
        key: "gpsAccuracyMeter",
        labelKey: "attendance.logs.fields.gps_accuracy_meter",
        icon: "mdi-crosshairs-gps",
    },
]);

export default {
    name: "AttendanceLogDialog",

    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        logs: {
            type: Array,
            default: () => [],
        },
        employeeName: {
            type: String,
            default: "",
        },
        actionLabel: {
            type: String,
            default: "",
        },
        shiftLabel: {
            type: String,
            default: "",
        },
    },

    emits: ["update:modelValue"],

    data() {
        return {
            commonQrCodeUrl: "",
        };
    },

    computed: {
        normalizedLogs() {
            return (this.logs ?? []).map((log, index) => ({
                ...log,
                panelKey: `${log?.id ?? "attendance-log"}-${index}`,
            }));
        },
        commonFieldKeys() {
            return COMMON_LOG_FIELD_CONFIGS.filter((field) =>
                this.isCommonLogField(field.key),
            ).map((field) => field.key);
        },
        commonFields() {
            if (!this.normalizedLogs.length) {
                return [];
            }

            return COMMON_LOG_FIELD_CONFIGS.filter((field) =>
                this.commonFieldKeys.includes(field.key),
            ).map((field) => ({
                key: field.key,
                icon: field.icon,
                label: this.$t(field.labelKey),
                value: this.getLogFieldDisplayValue(
                    this.normalizedLogs[0],
                    field.key,
                ),
            }));
        },
        commonQrToken() {
            if (!this.isCommonLogField("qrToken")) {
                return "";
            }

            return this.normalizedLogs[0]?.qrToken ?? "";
        },
        hasCommonSection() {
            return this.commonFields.length > 0 || Boolean(this.commonQrToken);
        },
    },

    watch: {
        modelValue(value) {
            if (value) {
                this.renderCommonQrCode();
                return;
            }

            this.commonQrCodeUrl = "";
        },
        logs() {
            if (this.modelValue) {
                this.renderCommonQrCode();
            }
        },
    },

    methods: {
        async renderCommonQrCode() {
            this.commonQrCodeUrl = await this.renderQrCodeValue(
                this.commonQrToken,
            );
        },
        async renderQrCodeValue(token) {
            if (!token) {
                return "";
            }

            try {
                return await QRCode.toDataURL(token, {
                    width: 120,
                    margin: 1,
                    errorCorrectionLevel: "M",
                });
            } catch (error) {
                console.error("Failed to render attendance log QR:", error);

                return "";
            }
        },
        isCommonLogField(key) {
            if (!this.normalizedLogs.length) {
                return false;
            }

            const firstValue = this.getComparableLogValue(
                this.normalizedLogs[0],
                key,
            );

            if (firstValue === "") {
                return false;
            }

            return this.normalizedLogs.every(
                (log) => this.getComparableLogValue(log, key) === firstValue,
            );
        },
        getComparableLogValue(log, key) {
            const value = log?.[key];

            if (value === null || value === undefined) {
                return "__NULL__";
            }

            return String(value).trim();
        },
        getLogFieldDisplayValue(log, key) {
            if (key === "gpsAccuracyMeter") {
                return this.getGpsAccuracyValue(log?.[key]);
            }

            return this.getDisplayValue(log?.[key]);
        },
        buildLogMetaChips(log) {
            const chips = [];

            if (!this.commonFieldKeys.includes("ipAddress") && log?.ipAddress) {
                chips.push({
                    key: "ip",
                    label: `IP: ${log.ipAddress}`,
                    color: "info",
                });
            }

            if (
                !this.commonFieldKeys.includes("gpsAccuracyMeter") &&
                log?.gpsAccuracyMeter !== null &&
                log?.gpsAccuracyMeter !== undefined
            ) {
                chips.push({
                    key: "gps",
                    label: this.$t(
                        "attendance.logs.labels.gps_accuracy_meter",
                        {
                            value: log.gpsAccuracyMeter,
                        },
                    ),
                    color: "warning",
                });
            }

            if (
                (!this.commonFieldKeys.includes("latitude") ||
                    !this.commonFieldKeys.includes("longtitude")) &&
                (log?.latitude || log?.longtitude)
            ) {
                chips.push({
                    key: "location",
                    label: `${this.getDisplayValue(log?.latitude)}, ${this.getDisplayValue(log?.longtitude)}`,
                    color: "secondary",
                });
            }

            if (!this.commonFieldKeys.includes("deviceId")) {
                chips.push({
                    key: "device",
                    label: `${this.$t("attendance.logs.fields.device_id")}: ${this.getDisplayValue(log?.deviceId)}`,
                    color: "grey",
                });
            }

            return chips;
        },
        getDisplayValue(value) {
            if (value === null || value === undefined || value === "") {
                return this.$t("base.not_available");
            }

            return String(value);
        },
        getGpsAccuracyValue(value) {
            if (value === null || value === undefined || value === "") {
                return this.$t("base.not_available");
            }

            return `${value} m`;
        },
        getValidationStatusLabel(status) {
            const labels = {
                valid: this.$t("attendance.logs.validation_status.valid"),
                invalid: this.$t("attendance.logs.validation_status.invalid"),
            };

            return labels[status] ?? this.$t("base.not_available");
        },
        getValidationStatusColor(status) {
            const colors = {
                valid: "success",
                invalid: "error",
            };

            return colors[status] ?? "grey";
        },
        getValidationStatusIcon(status) {
            const icons = {
                valid: "mdi-check-decagram-outline",
                invalid: "mdi-alert-circle-outline",
            };

            return icons[status] ?? "mdi-help-circle-outline";
        },
        getValidationReasonLabel(reason) {
            const labels = {
                qr_code_valid: this.$t(
                    "attendance.logs.validation_reasons.qr_code_valid",
                ),
                qr_code_invalid: this.$t(
                    "attendance.logs.validation_reasons.qr_code_invalid",
                ),
                ip_address_valid: this.$t(
                    "attendance.logs.validation_reasons.ip_address_valid",
                ),
                ip_address_invalid: this.$t(
                    "attendance.logs.validation_reasons.ip_address_invalid",
                ),
                location_valid: this.$t(
                    "attendance.logs.validation_reasons.location_valid",
                ),
                location_invalid: this.$t(
                    "attendance.logs.validation_reasons.location_invalid",
                ),
                on_time: this.$t("attendance.status.on_time"),
                late: this.$t("attendance.status.late"),
                early_leave: this.$t("attendance.status.early_leave"),
                absent: this.$t("attendance.status.absent"),
                early_check_in: this.$t("attendance.status.early_check_in"),
                late_check_out: this.$t("attendance.status.late_check_out"),
            };

            return labels[reason] ?? this.getDisplayValue(reason);
        },
    },
};
</script>
