<template>
    <v-dialog v-model="dialog" max-width="450" persistent>
        <v-card class="position-relative">
            <v-card-title
                class="d-flex align-center justify-space-between pb-2"
            >
                <span class="text-h6 font-weight-bold">
                    {{
                        $t("dining_table.qr_modal_title", {
                            number: table?.tableNumber,
                        })
                    }}
                </span>
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    @click="closeDialog"
                />
            </v-card-title>

            <v-divider />

            <v-card-text class="text-center py-6">
                <div v-if="loadingQr" class="py-10">
                    <v-progress-circular indeterminate color="primary" />
                </div>
                <div v-else-if="qrDataUrl">
                    <v-img
                        :src="qrDataUrl"
                        max-width="280"
                        class="mx-auto elevation-2 rounded-lg"
                        alt="QR Code"
                    />
                </div>
                <div v-else class="text-grey py-6">
                    {{ $t("dining_table.no_qr_code") }}
                </div>
            </v-card-text>

            <v-divider />

            <v-card-actions class="d-flex justify-end pa-4 ga-2">
                <v-btn
                    v-if="qrDataUrl"
                    color="primary"
                    prepend-icon="mdi-download"
                    @click="downloadQr"
                >
                    {{ $t("dining_table.download_qr") }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import QRCode from "qrcode";

export default {
    name: "DiningTableQrDialog",
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        table: {
            type: Object,
            default: null,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            qrDataUrl: "",
            loadingQr: false,
        };
    },
    computed: {
        dialog: {
            get() {
                return this.modelValue;
            },
            set(val) {
                this.$emit("update:modelValue", val);
            },
        },
    },
    watch: {
        modelValue(val) {
            if (val && this.table?.qrCode) {
                this.generateQr();
            } else {
                this.qrDataUrl = "";
            }
        },
    },
    methods: {
        async generateQr() {
            if (!this.table?.qrCode) {
                return;
            }
            this.loadingQr = true;
            try {
                const tableNumber = this.table?.tableNumber ?? "";
                const qrCode = this.table?.qrCode ?? "";

                // Kích thước canvas in chất lượng cao (chuẩn bảng đặt bàn)
                const width = 600;
                const height = 760;
                const canvas = document.createElement("canvas");
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext("2d");

                if (!ctx) {
                    return;
                }

                // 1. Nền trắng
                ctx.fillStyle = "#FFFFFF";
                ctx.fillRect(0, 0, width, height);

                // 2. Viền ngoài trang nhã
                ctx.strokeStyle = "#1A237E";
                ctx.lineWidth = 4;
                ctx.strokeRect(20, 20, width - 40, height - 40);

                // Viền chỉ phụ bên trong
                ctx.strokeStyle = "#C5CAE9";
                ctx.lineWidth = 1.5;
                ctx.strokeRect(26, 26, width - 52, height - 52);

                // 3. Tiêu đề số bàn
                ctx.fillStyle = "#1A237E";
                ctx.font = "bold 44px Roboto, Arial, sans-serif";
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillText(`BÀN SỐ ${tableNumber}`, width / 2, 85);

                // 4. Sinh QR Code lên canvas trung gian
                const qrCanvas = document.createElement("canvas");
                await QRCode.toCanvas(qrCanvas, qrCode, {
                    width: 440,
                    margin: 2,
                    color: {
                        dark: "#1A237E",
                        light: "#FFFFFF",
                    },
                });

                // Vẽ QR code vào vị trí trung tâm
                const qrX = (width - 440) / 2;
                const qrY = 135;
                ctx.drawImage(qrCanvas, qrX, qrY, 440, 440);

                // 5. Chân trang hướng dẫn & mã QR
                ctx.fillStyle = "#2E7D32";
                ctx.font = "bold 22px Roboto, Arial, sans-serif";
                ctx.fillText("QUÉT MÃ ĐẶT MÓN", width / 2, 620);

                ctx.fillStyle = "#757575";
                ctx.font = "16px monospace";
                ctx.fillText(qrCode, width / 2, 665);

                this.qrDataUrl = canvas.toDataURL("image/png");
            } catch (error) {
                console.error("Lỗi tạo hình ảnh QR:", error);
            } finally {
                this.loadingQr = false;
            }
        },
        downloadQr() {
            if (!this.qrDataUrl) return;
            const link = document.createElement("a");
            link.href = this.qrDataUrl;
            link.download = `QR_Ban_${this.table?.tableNumber || "table"}.png`;
            link.click();
        },
        closeDialog() {
            this.dialog = false;
        },
    },
};
</script>

<style scoped></style>
