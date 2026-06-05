<template>
    <v-container class="py-8">
        <v-btn
            color="primary"
            :loading="isSending"
            prepend-icon="mdi-bell-ring"
            @click="sendTestNotification"
        >
            Gửi thông báo test
        </v-btn>

        <v-btn
            color="success"
            :loading="isOpeningQr"
            prepend-icon="mdi-qrcode"
            @click="openQrAttendance"
            class="ml-2"
        >
            Hiển thị QR chấm công
        </v-btn>
    </v-container>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";

export default {
    data() {
        return {
            isSending: false,
            isOpeningQr: false,
        };
    },
    methods: {
        async sendTestNotification() {
            this.isSending = true;
            try {
                const res = await axiosInstance.get("/mercure/test");
                console.log("[Mercure] API publish response:", res);
            } catch (error) {
                console.error("[Mercure] Lỗi khi gửi thông báo:", error);
            } finally {
                this.isSending = false;
            }
        },
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
                console.error("Không thể mở trang QR chấm công:", error);
            } finally {
                this.isOpeningQr = false;
            }
        },
    },
};
</script>

<style scoped>
.status-row {
    gap: 4px;
}
</style>
