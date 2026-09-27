<template>
    <div>
        <v-btn
            :loading="isExporting"
            prepend-icon="mdi-file-pdf-box"
            color="primary"
            variant="outlined"
            @click="handleExportPdf"
        >
            {{ $t("dining_table.export_all_qr_pdf") }}
        </v-btn>
    </div>
</template>

<script>
import { jsPDF } from "jspdf";
import QRCode from "qrcode";
import { getListData } from "@/services/bases/getData";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { toast } from "@/main";

export default {
    name: "ExportDiningTableQrPdf",
    props: {
        path: {
            type: String,
            default: () => API_ROUTES_CONFIG.diningTable,
        },
        branchId: {
            type: [Number, String],
            default: null,
        },
        branchName: {
            type: String,
            default: "",
        },
    },
    data() {
        return {
            isExporting: false,
        };
    },
    methods: {
        async createTableCardDataUrl(table) {
            const tableNumber = table?.tableNumber ?? "";
            const qrCode = table?.qrCode ?? "";

            // Kích thước canvas (tỉ lệ tương đương card 88x80mm)
            const width = 500;
            const height = 460;
            const canvas = document.createElement("canvas");
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext("2d");
            if (!ctx) return null;

            // 1. Nền trắng
            ctx.fillStyle = "#FFFFFF";
            ctx.fillRect(0, 0, width, height);

            // 2. Viền ngoài thẻ với nét đứt giúp nhận diện đường cắt khi in
            ctx.strokeStyle = "#9FA8DA";
            ctx.lineWidth = 2;
            ctx.setLineDash([6, 4]);
            ctx.strokeRect(10, 10, width - 20, height - 20);
            ctx.setLineDash([]); // Khôi phục nét liền

            // 3. Tiêu đề số bàn
            ctx.fillStyle = "#1A237E";
            ctx.font = "bold 38px Roboto, Arial, sans-serif";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(`BÀN SỐ ${tableNumber}`, width / 2, 55);

            // 4. Sinh mã QR
            const qrCanvas = document.createElement("canvas");
            await QRCode.toCanvas(qrCanvas, qrCode, {
                width: 270,
                margin: 2,
                color: {
                    dark: "#1A237E",
                    light: "#FFFFFF",
                },
            });

            // Vẽ QR code ở giữa thẻ
            const qrX = (width - 270) / 2;
            const qrY = 90;
            ctx.drawImage(qrCanvas, qrX, qrY, 270, 270);

            // 5. Chân trang thẻ
            ctx.fillStyle = "#2E7D32";
            ctx.font = "bold 18px Roboto, Arial, sans-serif";
            ctx.fillText("QUÉT MÃ ĐẶT MÓN", width / 2, 385);

            ctx.fillStyle = "#757575";
            ctx.font = "13px monospace";
            ctx.fillText(qrCode, width / 2, 420);

            return canvas.toDataURL("image/png");
        },

        async handleExportPdf() {
            this.isExporting = true;
            try {
                // Lấy toàn bộ danh sách bàn không phân trang (limit = -1)
                const queryParams = {
                    limit: -1,
                    sort_column: "tableNumber",
                    sort_direction: "asc",
                };
                if (this.branchId) {
                    queryParams.branchId = this.branchId;
                }
                const response = await getListData(this.path, queryParams);

                const tables = (response?.data ?? []).filter(
                    (item) => item?.qrCode,
                );

                if (tables.length === 0) {
                    toast.warning(
                        this.$t("dining_table.no_qr_code") ||
                            "Không tìm thấy bàn nào có mã QR để xuất.",
                    );
                    return;
                }

                // Khởi tạo file PDF khổ A4 đứng (210 x 297 mm)
                const doc = new jsPDF({
                    orientation: "portrait",
                    unit: "mm",
                    format: "a4",
                });

                // Cấu hình layout 6 thẻ / trang (2 cột x 3 dòng)
                const marginX = 12;
                const marginY = 15;
                const cardWidth = 88;
                const cardHeight = 80;
                const gapX = 10;
                const gapY = 9;
                const itemsPerPage = 6;

                for (let i = 0; i < tables.length; i++) {
                    const table = tables[i];
                    const indexOnPage = i % itemsPerPage;

                    if (i > 0 && indexOnPage === 0) {
                        doc.addPage();
                    }

                    const col = indexOnPage % 2; // 0 hoặc 1
                    const row = Math.floor(indexOnPage / 2); // 0, 1 hoặc 2

                    const x = marginX + col * (cardWidth + gapX);
                    const y = marginY + row * (cardHeight + gapY);

                    const cardDataUrl =
                        await this.createTableCardDataUrl(table);
                    if (cardDataUrl) {
                        doc.addImage(
                            cardDataUrl,
                            "PNG",
                            x,
                            y,
                            cardWidth,
                            cardHeight,
                        );
                    }
                }

                const fileName = `Danh_sach_ma_QR_Ban_An_${new Date().toISOString().split("T")[0]}.pdf`;
                doc.save(fileName);

                toast.success(
                    this.$t("dining_table.export_pdf_success") ||
                        "Xuất file PDF mã QR thành công!",
                );
            } catch (error) {
                console.error("Lỗi xuất PDF mã QR:", error);
                toast.error(
                    this.$t("dining_table.export_pdf_error") ||
                        "Có lỗi xảy ra khi xuất file PDF mã QR.",
                );
            } finally {
                this.isExporting = false;
            }
        },
    },
};
</script>

<style scoped></style>
