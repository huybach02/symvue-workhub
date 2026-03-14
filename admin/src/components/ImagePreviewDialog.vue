<template>
    <v-dialog
        :model-value="modelValue"
        max-width="1100"
        content-class="image-preview-dialog"
        @update:model-value="handleDialogChange"
    >
        <v-card class="image-preview-card">
            <div class="d-flex justify-space-between align-center px-4 py-3">
                <div class="text-subtitle-2 font-weight-medium">Xem ảnh</div>
                <div class="d-flex align-center ga-2">
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        :disabled="imagePreviewScale <= minScale"
                        @click="zoomOutPreview"
                    >
                        <v-icon>mdi-magnify-minus-outline</v-icon>
                    </v-btn>
                    <span class="text-caption image-preview-zoom-label">
                        {{ Math.round(imagePreviewScale * 100) }}%
                    </span>
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        :disabled="imagePreviewScale >= maxScale"
                        @click="zoomInPreview"
                    >
                        <v-icon>mdi-magnify-plus-outline</v-icon>
                    </v-btn>
                    <v-btn
                        variant="text"
                        size="small"
                        @click="resetImagePreviewZoom"
                    >
                        Reset
                    </v-btn>
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        @click="closeDialog"
                    >
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </div>
            </div>

            <v-divider />

            <div
                ref="previewStage"
                class="image-preview-stage"
                :class="{
                    'is-zoomed': imagePreviewScale > minScale,
                    'is-dragging': isDragging,
                }"
                @mousedown="startDrag"
                @mousemove="handleDrag"
                @mouseup="stopDrag"
                @mouseleave="stopDrag"
            >
                <img
                    v-if="imageUrl"
                    ref="previewImage"
                    :src="imageUrl"
                    alt="Image preview"
                    class="image-preview-full"
                    :draggable="false"
                    :style="imageStyle"
                    @load="handleImageLoaded"
                />
            </div>
        </v-card>
    </v-dialog>
</template>

<script>
const IMAGE_PREVIEW_MIN_SCALE = 1;
const IMAGE_PREVIEW_MAX_SCALE = 4;
const IMAGE_PREVIEW_STEP = 0.25;

export default {
    name: "ImagePreviewDialog",

    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        imageUrl: {
            type: String,
            default: "",
        },
    },

    emits: ["update:modelValue"],

    data() {
        return {
            imagePreviewScale: IMAGE_PREVIEW_MIN_SCALE,
            minScale: IMAGE_PREVIEW_MIN_SCALE,
            maxScale: IMAGE_PREVIEW_MAX_SCALE,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            translateX: 0,
            translateY: 0,
            startTranslateX: 0,
            startTranslateY: 0,
        };
    },

    computed: {
        imageStyle() {
            return {
                transform: `translate(${this.translateX}px, ${this.translateY}px) scale(${this.imagePreviewScale})`,
                transition: this.isDragging ? "none" : "transform 0.12s ease-out",
            };
        },
    },

    watch: {
        modelValue(value) {
            if (value) {
                this.$nextTick(() => this.resetImagePreviewZoom());
                return;
            }

            this.resetPreviewState();
        },
        imageUrl() {
            this.resetImagePreviewZoom();
        },
    },

    beforeUnmount() {
        window.removeEventListener("mouseup", this.stopDrag);
    },

    methods: {
        handleDialogChange(value) {
            this.$emit("update:modelValue", value);
        },

        closeDialog() {
            this.$emit("update:modelValue", false);
        },

        zoomInPreview() {
            this.imagePreviewScale = Math.min(
                this.imagePreviewScale + IMAGE_PREVIEW_STEP,
                IMAGE_PREVIEW_MAX_SCALE,
            );
            this.$nextTick(() => this.clampTranslate());
        },

        zoomOutPreview() {
            this.imagePreviewScale = Math.max(
                this.imagePreviewScale - IMAGE_PREVIEW_STEP,
                IMAGE_PREVIEW_MIN_SCALE,
            );
            this.$nextTick(() => this.clampTranslate());
        },

        resetImagePreviewZoom() {
            this.imagePreviewScale = IMAGE_PREVIEW_MIN_SCALE;
            this.isDragging = false;
            this.translateX = 0;
            this.translateY = 0;
        },

        startDrag(event) {
            if (this.imagePreviewScale <= IMAGE_PREVIEW_MIN_SCALE) return;

            event.preventDefault();
            this.isDragging = true;
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.startTranslateX = this.translateX;
            this.startTranslateY = this.translateY;
            window.addEventListener("mouseup", this.stopDrag);
        },

        handleDrag(event) {
            if (!this.isDragging) return;

            const deltaX = event.clientX - this.dragStartX;
            const deltaY = event.clientY - this.dragStartY;

            this.translateX = this.startTranslateX + deltaX;
            this.translateY = this.startTranslateY + deltaY;
            this.clampTranslate();
        },

        stopDrag() {
            this.isDragging = false;
            window.removeEventListener("mouseup", this.stopDrag);
        },

        handleImageLoaded() {
            this.resetImagePreviewZoom();
        },

        clampTranslate() {
            const stage = this.$refs.previewStage;
            const image = this.$refs.previewImage;
            if (!stage) return;

            if (!image || this.imagePreviewScale <= IMAGE_PREVIEW_MIN_SCALE) {
                this.translateX = 0;
                this.translateY = 0;
                return;
            }

            const maxOffsetX = Math.max(
                0,
                (image.clientWidth * this.imagePreviewScale - stage.clientWidth) / 2,
            );
            const maxOffsetY = Math.max(
                0,
                (image.clientHeight * this.imagePreviewScale - stage.clientHeight) / 2,
            );

            this.translateX = Math.min(
                maxOffsetX,
                Math.max(-maxOffsetX, this.translateX),
            );
            this.translateY = Math.min(
                maxOffsetY,
                Math.max(-maxOffsetY, this.translateY),
            );
        },

        resetPreviewState() {
            this.stopDrag();
            this.resetImagePreviewZoom();
        },
    },
};
</script>

<style scoped>
.image-preview-card {
    overflow: hidden;
}

.image-preview-stage {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    max-height: 80vh;
    padding: 24px;
    overflow: hidden;
    background: linear-gradient(
        135deg,
        rgba(245, 247, 250, 0.95),
        rgba(232, 236, 241, 0.98)
    );
    cursor: default;
}

.image-preview-stage.is-zoomed {
    cursor: grab;
}

.image-preview-stage.is-dragging {
    cursor: grabbing;
    user-select: none;
}

.image-preview-full {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transform-origin: center center;
    user-select: none;
    will-change: transform;
}

.image-preview-zoom-label {
    min-width: 48px;
    text-align: center;
}
</style>
