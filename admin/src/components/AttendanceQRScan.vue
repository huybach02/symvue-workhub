<template>
    <div>
        <v-btn
            color="primary"
            prepend-icon="mdi-qrcode-scan"
            @click="openDialog"
        >
            {{ $t("attendance.qr_scan.open_button") }}
        </v-btn>

        <v-dialog
            v-model="dialog"
            max-width="560"
            persistent
            @after-enter="handleDialogOpened"
            @after-leave="handleDialogClosed"
        >
            <v-card rounded="xl">
                <v-card-title class="d-flex align-center justify-space-between">
                    <div>
                        <div class="text-title-1">
                            {{ $t("attendance.qr_scan.title") }}
                        </div>
                        <div class="text-body-2 text-medium-emphasis">
                            {{ $t("attendance.qr_scan.subtitle") }}
                        </div>
                    </div>

                    <v-chip size="small" variant="tonal" :color="statusColor">
                        {{ statusLabel }}
                    </v-chip>
                </v-card-title>

                <v-divider />

                <v-card-text class="pa-4">
                    <v-list density="comfortable">
                        <v-list-item
                            :title="$t('attendance.qr_scan.permissions.camera')"
                            :subtitle="permissionText(cameraPermission)"
                        >
                            <template #append>
                                <div class="d-flex align-center ga-2">
                                    <v-chip
                                        size="small"
                                        variant="tonal"
                                        :color="
                                            permissionColor(cameraPermission)
                                        "
                                    >
                                        {{ permissionLabel(cameraPermission) }}
                                    </v-chip>

                                    <v-btn
                                        v-if="
                                            canRequestPermission(
                                                cameraPermission,
                                            )
                                        "
                                        size="small"
                                        variant="text"
                                        color="primary"
                                        :loading="requestingCamera"
                                        @click="requestCameraPermission"
                                    >
                                        {{
                                            $t(
                                                "attendance.qr_scan.grant_button",
                                            )
                                        }}
                                    </v-btn>
                                </div>
                            </template>
                        </v-list-item>

                        <v-list-item
                            :title="
                                $t('attendance.qr_scan.permissions.location')
                            "
                            :subtitle="permissionText(locationPermission)"
                        >
                            <template #append>
                                <div class="d-flex align-center ga-2">
                                    <v-chip
                                        size="small"
                                        variant="tonal"
                                        :color="
                                            permissionColor(locationPermission)
                                        "
                                    >
                                        {{
                                            permissionLabel(locationPermission)
                                        }}
                                    </v-chip>

                                    <v-btn
                                        v-if="
                                            canRequestPermission(
                                                locationPermission,
                                            )
                                        "
                                        size="small"
                                        variant="text"
                                        color="primary"
                                        :loading="requestingLocation"
                                        @click="requestLocationPermission"
                                    >
                                        {{
                                            $t(
                                                "attendance.qr_scan.grant_button",
                                            )
                                        }}
                                    </v-btn>
                                </div>
                            </template>
                        </v-list-item>
                    </v-list>

                    <v-responsive :aspect-ratio="4 / 3" class="mt-3">
                        <v-sheet
                            rounded="xl"
                            color="grey-darken-4"
                            class="position-relative overflow-hidden fill-height"
                        >
                            <div :id="readerId" />

                            <v-overlay
                                :model-value="!scannerActive"
                                contained
                                persistent
                                class="align-center justify-center"
                            >
                                <div class="text-center px-6">
                                    <v-progress-circular
                                        v-if="processing || verifying"
                                        indeterminate
                                        color="primary"
                                    />

                                    <v-icon
                                        v-else
                                        icon="mdi-qrcode-scan"
                                        size="48"
                                        color="grey-lighten-1"
                                    />

                                    <div
                                        class="text-subtitle-1 text-white mt-4"
                                    >
                                        {{ scannerMessage }}
                                    </div>

                                    <v-btn
                                        v-if="
                                            canStartScanner &&
                                            !processing &&
                                            !verifying
                                        "
                                        class="mt-4"
                                        color="primary"
                                        prepend-icon="mdi-camera"
                                        @click="startScanner"
                                    >
                                        {{
                                            $t(
                                                "attendance.qr_scan.start_scanner",
                                            )
                                        }}
                                    </v-btn>
                                </div>
                            </v-overlay>
                        </v-sheet>
                    </v-responsive>

                    <v-alert
                        v-if="successMessage"
                        type="success"
                        variant="tonal"
                        class="mt-4"
                    >
                        {{ successMessage }}
                    </v-alert>

                    <v-alert
                        v-if="errorMessage"
                        type="error"
                        variant="tonal"
                        class="mt-4"
                    >
                        {{ errorMessage }}
                    </v-alert>
                </v-card-text>

                <v-divider />

                <v-card-actions>
                    <v-btn
                        variant="tonal"
                        color="primary"
                        prepend-icon="mdi-refresh"
                        :disabled="processing || verifying || !canStartScanner"
                        @click="retryScan"
                    >
                        {{ $t("attendance.qr_scan.retry_button") }}
                    </v-btn>

                    <v-spacer />

                    <v-btn variant="text" color="error" @click="closeDialog">
                        {{ $t("attendance.qr_scan.close_button") }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { postData } from "@/services/bases/postData";
import { Html5Qrcode } from "html5-qrcode";
import { functionHelper } from "@/helpers/functionHelper";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";

export default {
    data() {
        return {
            dialog: false,
            readerId: "qr-reader-" + Date.now(),
            html5QrCode: null,
            scannerActive: false,
            processing: false,
            verifying: false,
            requestingCamera: false,
            requestingLocation: false,
            cameraPermission: "unknown",
            locationPermission: "unknown",
            location: null,
            successMessage: null,
            errorMessage: null,
        };
    },

    computed: {
        addressDisplay() {
            return (
                this.$store.state.generalSettings?.initialValues
                    ?.addressDisplay || ""
            );
        },

        canStartScanner() {
            return (
                this.cameraPermission === "granted" &&
                this.locationPermission === "granted"
            );
        },

        statusLabel() {
            if (this.verifying) {
                return this.$t("attendance.qr_scan.status.verifying");
            }

            if (this.processing) {
                return this.$t("attendance.qr_scan.status.processing");
            }

            if (this.scannerActive) {
                return this.$t("attendance.qr_scan.status.scanning");
            }

            return this.canStartScanner
                ? this.$t("attendance.qr_scan.status.ready")
                : this.$t("attendance.qr_scan.status.waiting_permission");
        },

        statusColor() {
            if (this.verifying || this.processing) {
                return "info";
            }

            if (this.scannerActive || this.canStartScanner) {
                return "success";
            }

            return "warning";
        },

        scannerMessage() {
            if (this.verifying) {
                return this.$t("attendance.qr_scan.messages.verifying");
            }

            if (this.processing) {
                return this.$t("attendance.qr_scan.messages.processing");
            }

            if (!this.canStartScanner) {
                return this.$t("attendance.qr_scan.messages.need_permissions");
            }

            return this.$t("attendance.qr_scan.messages.scanner_inactive");
        },
    },

    beforeUnmount() {
        this.stopScanner();
    },

    methods: {
        async openDialog() {
            this.resetState();
            this.dialog = true;
            await this.ensureGeneralSettingsLoaded();
            await this.syncPermissions();
            await this.autoRequestPermissions();
        },

        closeDialog() {
            this.dialog = false;
        },

        async handleDialogOpened() {
            await this.syncPermissions();
            await this.startScannerIfReady();
        },

        async handleDialogClosed() {
            await this.stopScanner();
        },

        resetState() {
            this.location = null;
            this.successMessage = null;
            this.errorMessage = null;
            this.processing = false;
            this.verifying = false;
        },

        async ensureGeneralSettingsLoaded() {
            if (this.$store.state.generalSettings?.dataLoaded) {
                return;
            }

            await this.$store.dispatch("generalSettings/fetchSettings");
        },

        async syncPermissions() {
            this.cameraPermission = await this.getPermissionStatus(
                "camera",
                Boolean(navigator.mediaDevices?.getUserMedia),
            );
            this.locationPermission = await this.getPermissionStatus(
                "geolocation",
                Boolean(navigator.geolocation),
            );
        },

        async autoRequestPermissions() {
            if (
                this.locationPermission !== "granted" &&
                this.locationPermission !== "unsupported"
            ) {
                await this.requestLocationPermission();
            }

            if (
                this.cameraPermission !== "granted" &&
                this.cameraPermission !== "unsupported"
            ) {
                await this.requestCameraPermission();
            }
        },

        async getPermissionStatus(name, supported) {
            if (!supported) {
                return "unsupported";
            }

            if (!navigator.permissions?.query) {
                return "unknown";
            }

            try {
                const result = await navigator.permissions.query({ name });
                return result.state || "unknown";
            } catch (error) {
                return "unknown";
            }
        },

        permissionLabel(status) {
            const labels = {
                granted: this.$t(
                    "attendance.qr_scan.permission_status.granted",
                ),
                prompt: this.$t("attendance.qr_scan.permission_status.prompt"),
                denied: this.$t("attendance.qr_scan.permission_status.denied"),
                unsupported: this.$t(
                    "attendance.qr_scan.permission_status.unsupported",
                ),
                unknown: this.$t(
                    "attendance.qr_scan.permission_status.unknown",
                ),
            };

            return (
                labels[status] ||
                this.$t("attendance.qr_scan.permission_status.unknown")
            );
        },

        permissionText(status) {
            const labels = {
                granted: this.$t("attendance.qr_scan.permission_text.granted"),
                prompt: this.$t("attendance.qr_scan.permission_text.prompt"),
                denied: this.$t("attendance.qr_scan.permission_text.denied"),
                unsupported: this.$t(
                    "attendance.qr_scan.permission_text.unsupported",
                ),
                unknown: this.$t("attendance.qr_scan.permission_text.unknown"),
            };

            return (
                labels[status] ||
                this.$t("attendance.qr_scan.permission_text.unknown")
            );
        },

        permissionColor(status) {
            const colors = {
                granted: "success",
                prompt: "warning",
                denied: "error",
                unsupported: "grey",
                unknown: "grey",
            };

            return colors[status] || "grey";
        },

        canRequestPermission(status) {
            return status !== "granted" && status !== "unsupported";
        },

        async requestCameraPermission() {
            if (!navigator.mediaDevices?.getUserMedia) {
                this.cameraPermission = "unsupported";
                return;
            }

            this.requestingCamera = true;
            this.errorMessage = null;

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: "environment" },
                });

                stream.getTracks().forEach((track) => track.stop());
                this.cameraPermission = "granted";
            } catch (error) {
                this.cameraPermission = "denied";
                this.errorMessage = this.$t(
                    "attendance.qr_scan.errors.camera_access",
                );
            } finally {
                this.requestingCamera = false;
                await this.syncPermissions();
                await this.startScannerIfReady();
            }
        },

        async requestLocationPermission() {
            if (!navigator.geolocation) {
                this.locationPermission = "unsupported";
                return;
            }

            this.requestingLocation = true;
            this.errorMessage = null;

            try {
                this.location = await functionHelper.fetchCurrentLocation();
                this.locationPermission = "granted";
            } catch (error) {
                this.locationPermission = "denied";
                this.errorMessage = error.message;
            } finally {
                this.requestingLocation = false;
                await this.syncPermissions();
                await this.startScannerIfReady();
            }
        },

        async startScannerIfReady() {
            if (!this.dialog || !this.canStartScanner || this.scannerActive) {
                return;
            }

            await this.startScanner();
        },

        async startScanner() {
            if (
                !this.dialog ||
                !this.canStartScanner ||
                this.scannerActive ||
                this.processing ||
                this.verifying
            ) {
                return;
            }

            try {
                await this.stopScanner();
                this.errorMessage = null;
                this.html5QrCode = new Html5Qrcode(this.readerId);
                await this.html5QrCode.start(
                    { facingMode: "environment" },
                    {
                        fps: 10,
                        qrbox: (viewfinderWidth, viewfinderHeight) => {
                            const minEdge = Math.min(
                                viewfinderWidth,
                                viewfinderHeight,
                            );
                            const size = Math.max(
                                80,
                                Math.min(
                                    250,
                                    Math.floor(minEdge * 0.7),
                                    minEdge - 24,
                                ),
                            );

                            return {
                                width: size,
                                height: size,
                            };
                        },
                    },
                    this.onScanSuccess,
                    () => {},
                );
                this.scannerActive = true;
            } catch (error) {
                this.errorMessage = this.$t(
                    "attendance.qr_scan.errors.scanner_start",
                );
            }
        },

        async stopScanner() {
            if (!this.html5QrCode) {
                this.scannerActive = false;
                return;
            }

            try {
                if (this.html5QrCode.isScanning) {
                    await this.html5QrCode.stop();
                }
                this.html5QrCode.clear();
            } catch (error) {
                console.error("Lỗi khi dừng scanner:", error);
            } finally {
                this.html5QrCode = null;
                this.scannerActive = false;
            }
        },

        async onScanSuccess(decodedText) {
            if (this.processing || this.verifying) {
                return;
            }

            this.processing = true;
            this.successMessage = null;
            this.errorMessage = null;

            await this.stopScanner();

            try {
                this.location = await functionHelper.fetchCurrentLocation();

                await this.verifyAttendance(
                    decodedText,
                    this.location.latitude,
                    this.location.longitude,
                    this.location.accuracy,
                );
            } catch (error) {
                this.errorMessage = this.$t(
                    "attendance.qr_scan.errors.location_after_scan",
                    {
                        message: error.message,
                    },
                );
            } finally {
                this.processing = false;
            }
        },

        async retryScan() {
            this.resetState();
            await this.syncPermissions();
            await this.startScannerIfReady();
        },

        async verifyAttendance(qrCode, latitude, longitude, accuracy) {
            this.verifying = true;

            try {
                const res = await postData(
                    `${API_ROUTES_CONFIG.attendance}/verify`,
                    {
                        qrCode,
                        latitude,
                        longitude,
                        accuracy,
                    },
                    () => {},
                    true,
                );

                if (!res) {
                    this.errorMessage = this.$t(
                        "attendance.qr_scan.errors.verify_failed",
                    );
                    return;
                }

                this.successMessage = this.addressDisplay
                    ? this.$t("attendance.qr_scan.success.with_address", {
                          address: this.addressDisplay,
                      })
                    : this.$t("attendance.qr_scan.success.default");
            } finally {
                this.verifying = false;
            }
        },
    },
};
</script>
