<template>
    <v-dialog
        :model-value="modelValue"
        max-width="650px"
        persistent
        @update:model-value="$emit('update:model-value', $event)"
    >
        <v-card>
            <v-card-title
                class="headline bg-primary text-white d-flex justify-space-between align-center"
            >
                <span>{{ $t("profile.signature.setup") }}</span>
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    color="white"
                    @click="handleCancel"
                    :disabled="loading"
                />
            </v-card-title>
            <v-card-text class="pa-4">
                <div class="canvas-container">
                    <canvas ref="canvas" class="signature-canvas"></canvas>
                </div>
            </v-card-text>
            <v-card-actions
                class="pa-4 pt-0 d-flex align-center justify-space-between flex-wrap gap-2"
            >
                <div class="d-flex align-center gap-2">
                    <v-btn
                        color="warning"
                        variant="tonal"
                        :disabled="loading"
                        @click="handleClear"
                    >
                        {{ $t("profile.signature.clear") }}
                    </v-btn>
                    <v-btn
                        color="secondary"
                        variant="tonal"
                        :disabled="loading"
                        @click="handleUndo"
                    >
                        {{ $t("profile.signature.undo") }}
                    </v-btn>
                </div>
                <v-btn
                    color="primary"
                    variant="tonal"
                    :loading="loading"
                    @click="handleSave"
                >
                    {{ $t("profile.signature.save") }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import SignaturePad from "signature_pad";
import { toast } from "@/main";

export default {
    name: "SignatureDialog",
    props: {
        modelValue: {
            type: Boolean,
            required: true,
        },
        signature: {
            type: Object,
            default: () => null,
        },
    },
    emits: ["update:model-value", "saved"],
    data() {
        return {
            signaturePad: null,
            loading: false,
            lastCanvasWidth: 0,
            lastCanvasHeight: 0,
            resizeObserver: null,
        };
    },
    watch: {
        modelValue(val) {
            if (val) {
                this.lastCanvasWidth = 0;
                this.lastCanvasHeight = 0;
                this.$nextTick(() => {
                    this.initSignaturePad();
                });
            }
        },
    },
    beforeUnmount() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
    },
    methods: {
        initSignaturePad() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            // Khởi tạo lại SignaturePad mới cho canvas element vừa dựng
            this.signaturePad = new SignaturePad(canvas, {
                backgroundColor: "rgba(0,0,0,0)",
                penColor: "rgb(0,0,0)",
            });

            // Quan sát thay đổi kích thước của canvas element mới dựng
            if (this.resizeObserver) {
                this.resizeObserver.disconnect();
            }
            this.resizeObserver = new ResizeObserver((entries) => {
                for (let entry of entries) {
                    const { width, height } = entry.contentRect;
                    if (width > 0 && height > 0) {
                        if (
                            Math.abs(width - this.lastCanvasWidth) > 1 ||
                            Math.abs(height - this.lastCanvasHeight) > 1
                        ) {
                            this.resizeCanvas();
                        }
                    }
                }
            });
            this.resizeObserver.observe(canvas);

            this.resizeCanvas();
        },
        scaleStrokes(strokes, fromWidth, fromHeight, toWidth, toHeight) {
            if (!strokes || !fromWidth || !fromHeight || !toWidth || !toHeight)
                return strokes;
            const scaleX = toWidth / fromWidth;
            const scaleY = toHeight / fromHeight;

            return strokes.map((stroke) => {
                return {
                    ...stroke,
                    points: stroke.points.map((point) => {
                        return {
                            ...point,
                            x: point.x * scaleX,
                            y: point.y * scaleY,
                            time: point.time,
                            pressure: point.pressure,
                        };
                    }),
                };
            });
        },
        resizeCanvas() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            const newWidth = canvas.offsetWidth;
            const newHeight = canvas.offsetHeight;

            if (newWidth === 0 || newHeight === 0) return;

            let currentData = [];
            if (this.signaturePad) {
                currentData = this.signaturePad.toData();
                if (
                    currentData &&
                    currentData.length > 0 &&
                    this.lastCanvasWidth &&
                    this.lastCanvasHeight
                ) {
                    currentData = this.scaleStrokes(
                        currentData,
                        this.lastCanvasWidth,
                        this.lastCanvasHeight,
                        newWidth,
                        newHeight,
                    );
                }
            }

            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = newWidth * ratio;
            canvas.height = newHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);

            if (this.signaturePad) {
                this.signaturePad.clear();

                if (currentData && currentData.length > 0) {
                    this.signaturePad.fromData(currentData);
                } else if (this.signature && this.signature.signatureStrokes) {
                    const scaledServerStrokes = this.scaleStrokes(
                        this.signature.signatureStrokes,
                        this.signature.width,
                        this.signature.height,
                        newWidth,
                        newHeight,
                    );
                    this.signaturePad.fromData(scaledServerStrokes);
                }
            }

            this.lastCanvasWidth = newWidth;
            this.lastCanvasHeight = newHeight;
        },
        handleClear() {
            if (this.signaturePad) {
                this.signaturePad.clear();
            }
        },
        handleUndo() {
            if (this.signaturePad) {
                const data = this.signaturePad.toData();
                if (data && data.length > 0) {
                    data.pop();
                    this.signaturePad.fromData(data);
                }
            }
        },
        handleCancel() {
            this.$emit("update:model-value", false);
        },
        async handleSave() {
            if (!this.signaturePad || this.signaturePad.isEmpty()) {
                toast.error(this.$t("profile.signature.error_empty"));
                return;
            }

            this.loading = true;
            try {
                const pngDataUrl = this.signaturePad.toDataURL("image/png");
                const svgContent = this.signaturePad.toSVG();
                const strokes = this.signaturePad.toData();

                this.$emit("saved", {
                    pngDataUrl,
                    svgContent,
                    strokes,
                    width: this.lastCanvasWidth,
                    height: this.lastCanvasHeight,
                });
            } catch (error) {
                console.error("Lỗi khi kết xuất chữ ký:", error);
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.canvas-container {
    width: 100%;
    aspect-ratio: 8 / 3;
    background-color: #f9f9f9;
    border: 1px dashed #ccc;
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}

.signature-canvas {
    width: 100%;
    height: 100%;
    display: block;
    cursor: crosshair;
}

.gap-2 {
    gap: 8px;
}
</style>
