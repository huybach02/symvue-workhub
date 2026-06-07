<template>
    <div>
        <h1>Attendance QR Scan</h1>

        <v-btn
            color="primary"
            prepend-icon="mdi-qrcode-scan"
            @click="openDialog"
        >
            Mở quét QR
        </v-btn>

        <v-dialog
            v-model="dialog"
            max-width="500"
            persistent
            @after-enter="startScanner"
            @after-leave="stopScanner"
        >
            <v-card>
                <v-card-title class="text-h6">
                    Quét QR Attendance
                </v-card-title>

                <v-divider />

                <v-card-text class="pa-4">
                    <div :id="readerId" class="qr-reader" />

                    <div
                        v-if="isVerifying"
                        class="d-flex align-center justify-center mt-3"
                    >
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="24"
                            class="mr-2"
                        />
                        <span>Đang xác thực attendance...</span>
                    </div>

                    <v-alert
                        v-if="scanResult"
                        type="success"
                        class="mt-3"
                        variant="tonal"
                        border="start"
                    >
                        Kết quả: <strong>{{ scanResult }}</strong>
                        <div v-if="location" class="mt-1">
                            Vị trí:
                            <strong>
                                {{ location.latitude }},
                                {{ location.longitude }}
                            </strong>
                        </div>
                    </v-alert>

                    <v-alert
                        v-if="errorMessage"
                        type="error"
                        class="mt-3"
                        variant="tonal"
                        border="start"
                    >
                        {{ errorMessage }}
                    </v-alert>
                </v-card-text>

                <v-divider />

                <v-card-actions>
                    <v-spacer />
                    <v-btn
                        v-if="canRetryScan"
                        color="primary"
                        variant="tonal"
                        :disabled="isVerifying"
                        @click="retryScan"
                    >
                        Quét lại
                    </v-btn>
                    <v-btn color="error" variant="text" @click="closeDialog">
                        Đóng
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
            scanResult: null,
            location: null,
            errorMessage: null,
            // Tạo id duy nhất cho div reader để tránh xung đột khi mount nhiều instance
            readerId: "qr-reader-" + Date.now(),
            html5QrCode: null,
            isProcessingScan: false,
            isVerifying: false,
            canRetryScan: false,
        };
    },
    beforeUnmount() {
        // Đảm bảo giải phóng camera khi component bị huỷ
        this.stopScanner();
    },
    methods: {
        openDialog() {
            // Reset trạng thái mỗi lần mở dialog để quét mới
            this.resetScanState();
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        resetScanState() {
            this.scanResult = null;
            this.location = null;
            this.errorMessage = null;
            this.isProcessingScan = false;
            this.isVerifying = false;
            this.canRetryScan = false;
        },
        async startScanner() {
            if (!this.dialog || this.isProcessingScan || this.isVerifying) {
                return;
            }

            // Khởi tạo instance scanner và bật camera (ưu tiên camera sau)
            try {
                if (this.html5QrCode) {
                    await this.stopScanner();
                }

                this.html5QrCode = new Html5Qrcode(this.readerId);
                await this.html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    this.onScanSuccess,
                    this.onScanFailure,
                );
            } catch (err) {
                // Trường hợp thiết bị không có camera hoặc người dùng từ chối quyền truy cập
                this.errorMessage =
                    "Không thể truy cập camera. Vui lòng kiểm tra quyền truy cập.";
                this.canRetryScan = true;
                console.error("Lỗi khởi động camera:", err);
            }
        },
        async stopScanner() {
            // Tắt camera và clear DOM reader để tránh đèn LED camera vẫn sáng sau khi đóng
            if (!this.html5QrCode) {
                return;
            }

            try {
                // isScanning là boolean property; phải stop() trước khi clear() nếu scanner đang chạy
                if (this.html5QrCode.isScanning) {
                    await this.html5QrCode.stop();
                }
                this.html5QrCode.clear();
            } catch (err) {
                console.error("Lỗi khi dừng scanner:", err);
            } finally {
                this.html5QrCode = null;
            }
        },
        async onScanSuccess(decodedText) {
            if (this.isProcessingScan || this.isVerifying) {
                return;
            }

            this.isProcessingScan = true;
            this.canRetryScan = false;
            this.errorMessage = null;
            this.scanResult = decodedText;

            await this.stopScanner();

            try {
                // Lấy vị trí hiện tại của user ngay tại thời điểm quét thành công
                const coords = await functionHelper.fetchCurrentLocation();
                this.location = coords;

                await this.handleVerifyAttendance(
                    decodedText,
                    coords.latitude,
                    coords.longitude,
                    coords.accuracy,
                );
            } catch (err) {
                this.location = null;
                this.errorMessage = `Quét thành công nhưng không lấy được vị trí: ${err.message}`;
                this.canRetryScan = true;
                console.error("Lỗi lấy vị trí:", err);
            } finally {
                this.isProcessingScan = false;
            }
        },
        onScanFailure() {
            // Mỗi frame không chứa QR đều sinh lỗi decode, bỏ qua để tránh log rác
        },
        async retryScan() {
            this.resetScanState();
            await this.startScanner();
        },
        async handleVerifyAttendance(
            decodedText,
            latitude,
            longitude,
            accuracy,
        ) {
            this.isVerifying = true;

            try {
                const res = await postData(
                    API_ROUTES_CONFIG.attendance + "/verify",
                    {
                        qrCode: decodedText,
                        latitude,
                        longitude,
                        accuracy,
                    },
                );

                if (!res) {
                    this.errorMessage =
                        "Chấm công thất bại. Vui lòng bấm Quét lại để thử lại.";
                    this.canRetryScan = true;
                    return;
                }

                this.canRetryScan = false;
            } finally {
                this.isVerifying = false;
            }
        },
    },
};
</script>

<style scoped>
.qr-reader {
    width: 100%;
    min-height: 300px;
    border-radius: 8px;
    overflow: hidden;
    background-color: #000;
}
</style>
