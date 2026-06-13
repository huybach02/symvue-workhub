<template>
    <v-container class="">
        <v-row class="ga-0">
            <v-col cols="12" md="4">
                <v-card elevation="3">
                    <v-card-item>
                        <v-card-title>
                            {{ $t("dashboard.attendance_qr.title") }}
                        </v-card-title>
                        <v-card-subtitle>
                            {{ $t("dashboard.attendance_qr.subtitle") }}
                        </v-card-subtitle>
                    </v-card-item>

                    <v-divider />

                    <v-card-text>
                        <v-btn
                            v-if="isAdmin"
                            color="success"
                            :loading="isOpeningQr"
                            prepend-icon="mdi-qrcode"
                            class="mb-4"
                            @click="openQrAttendance"
                        >
                            {{ $t("dashboard.actions.show_attendance_qr") }}
                        </v-btn>

                        <AttendanceQRScan />
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import AttendanceQRScan from "@/components/AttendanceQRScan.vue";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";
import { mapGetters } from "vuex";

export default {
    components: {
        AttendanceQRScan,
    },
    data() {
        return {
            isOpeningQr: false,
        };
    },
    computed: {
        ...mapGetters("auth", ["isAdmin"]),
    },
    methods: {
        async openQrAttendance() {
            this.isOpeningQr = true;
            const qrWindow = window.open("about:blank", "_blank");

            try {
                const response = await axiosInstance.get(
                    API_ROUTES_CONFIG.attendanceQrDisplayAccess,
                );
                const token = response?.data?.token || response?.token;

                if (!token) {
                    throw new Error("Missing QR display access token");
                }

                const route = this.$router.resolve({
                    path: "/qr-attendance",
                    query: {
                        access: token,
                    },
                });
                const absoluteUrl = new URL(
                    route.href,
                    window.location.origin,
                ).toString();

                if (qrWindow) {
                    qrWindow.opener = null;
                    qrWindow.location.replace(absoluteUrl);
                } else {
                    window.open(absoluteUrl, "_blank", "noopener,noreferrer");
                }
            } catch (error) {
                if (qrWindow) {
                    qrWindow.close();
                }
                console.error(this.$t("dashboard.logs.open_qr_error"), error);
            } finally {
                this.isOpeningQr = false;
            }
        },
    },
};
</script>
