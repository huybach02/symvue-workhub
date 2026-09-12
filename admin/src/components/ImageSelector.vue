<template>
    <div>
        <div v-if="label" class="mb-2">
            {{ label }}
            <span v-if="required" class="text-red"> * </span>
        </div>

        <v-card
            variant="outlined"
            color="grey"
            :class="{ 'error-border': errorMessage }"
        >
            <v-card-item>
                <div class="d-flex align-center justify-space-between">
                    <v-btn
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-image-multiple"
                        @click="showModal = true"
                    >
                        {{ $t("media_library.select_from_library") }}
                    </v-btn>

                    <v-btn
                        v-if="displayImages.length > 0"
                        color="error"
                        variant="text"
                        size="small"
                        prepend-icon="mdi-delete"
                        @click="clearAll"
                    >
                        {{
                            isMultiple
                                ? $t("base.delete_all")
                                : $t("base.delete")
                        }}
                    </v-btn>
                </div>

                <MediaLibraryModal
                    v-model="showModal"
                    :is-multiple="isMultiple"
                />

                <!-- Danh sách ảnh preview thu nhỏ gọn gàng -->
                <div v-if="displayImages.length > 0" class="mt-3">
                    <div class="d-flex flex-wrap ga-3">
                        <div
                            v-for="(image, index) in displayImages"
                            :key="index"
                            class="position-relative image-preview-item"
                        >
                            <v-img
                                :src="image"
                                :aspect-ratio="1"
                                cover
                                class="rounded elevation-1 preview-image cursor-pointer"
                                @click="openPreview(image)"
                            />
                            <v-btn
                                icon="mdi-close"
                                size="x-small"
                                color="error"
                                variant="flat"
                                class="position-absolute remove-btn"
                                @click.stop="removeImage(index)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Modal xem ảnh kích thước lớn khi click vào thumbnail -->
                <v-dialog v-model="previewDialog" max-width="700">
                    <v-card class="position-relative pa-2">
                        <v-btn
                            icon="mdi-close"
                            variant="text"
                            size="small"
                            class="position-absolute"
                            style="top: 8px; right: 8px; z-index: 10"
                            @click="previewDialog = false"
                        />
                        <v-img
                            :src="previewImageUrl"
                            max-height="70vh"
                            contain
                            class="rounded"
                        />
                    </v-card>
                </v-dialog>
            </v-card-item>
        </v-card>
        <div
            v-if="errorMessage"
            class="text-error text-caption mt-2 ml-4"
            style="color: rgb(var(--v-theme-error))"
        >
            {{ errorMessage }}
        </div>
    </div>
</template>

<script>
import MediaLibraryModal from "./MediaLibraryModal.vue";

export default {
    components: { MediaLibraryModal },
    props: {
        label: {
            type: String,
            default: "",
        },
        required: {
            type: Boolean,
            default: false,
        },
        isMultiple: {
            type: Boolean,
            default: false,
        },
        errorMessage: {
            type: String,
            default: "",
        },
        modelValue: {
            type: [String, Array, Object],
            default: null,
        },
    },
    emits: ["selected"],
    data() {
        return {
            showModal: false,
            previewDialog: false,
            previewImageUrl: "",
        };
    },
    computed: {
        selectedMedia() {
            return this.$store.getters["media/selectedMedia"];
        },
        displayImages() {
            // Nếu có ảnh mới chọn từ modal, hiển thị ảnh đó
            if (this.selectedMedia.length > 0) {
                return this.selectedMedia.map((media) => media.path);
            }

            // Nếu không, hiển thị ảnh từ modelValue (giá trị form)
            if (this.modelValue) {
                if (Array.isArray(this.modelValue)) {
                    return this.modelValue;
                }
                if (typeof this.modelValue === "string") {
                    return [this.modelValue];
                }
                if (this.modelValue.path) {
                    return [this.modelValue.path];
                }
            }

            return [];
        },
    },
    watch: {
        selectedMedia(newVal) {
            if (this.isMultiple && newVal.length > 0) {
                this.$emit("selected", newVal);
            } else if (!this.isMultiple && newVal.length > 0) {
                this.$emit("selected", newVal[0]);
            } else {
                this.$emit("selected", null);
            }
        },
    },
    methods: {
        openPreview(url) {
            this.previewImageUrl = url;
            this.previewDialog = true;
        },
        clearAll() {
            this.$store.dispatch("media/clearSelectedMedia");
            this.$emit("selected", this.isMultiple ? [] : null);
        },
        removeImage(index) {
            if (this.isMultiple) {
                const updated = [...this.displayImages];
                updated.splice(index, 1);
                this.$store.dispatch("media/clearSelectedMedia");
                this.$emit("selected", updated);
            } else {
                this.clearAll();
            }
        },
    },
    unmounted() {
        this.$store.dispatch("media/clearSelectedMedia");
    },
};
</script>

<style scoped>
.image-preview-item {
    width: 100px;
    height: 100px;
}
.preview-image {
    width: 100px;
    height: 100px;
    border: 1px solid rgba(0, 0, 0, 0.12);
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
}
.preview-image:hover {
    transform: scale(1.04);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15) !important;
}
.remove-btn {
    top: -6px;
    right: -6px;
    width: 22px !important;
    height: 22px !important;
    min-width: 22px !important;
    z-index: 2;
}
.error-border {
    border: 1px solid rgb(var(--v-theme-error)) !important;
}
</style>
