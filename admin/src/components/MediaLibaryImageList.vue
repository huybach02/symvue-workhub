<template>
    <v-row class="mt-1">
        <v-col cols="12" md="8" class="order-last order-md-first">
            <div
                v-if="mediaList.length > 0"
                class="d-flex justify-space-between align-center mb-3"
            >
                <div class="d-flex ga-2">
                    <v-btn
                        v-if="selectedMedia.length > 0"
                        color="error"
                        variant="elevated"
                        prepend-icon="mdi-delete"
                        :block="$vuetify.display.smAndDown"
                        @click="showConfirmDelete = true"
                    >
                        {{
                            $t("media_library.delete_button", {
                                count: selectedMedia.length,
                            })
                        }}
                    </v-btn>
                    <ConfirmDialog
                        v-model="showConfirmDelete"
                        :message="
                            $t('media_library.delete_confirm_message', {
                                count: selectedMedia.length,
                            })
                        "
                        :loading="isDeleting"
                        @confirm="handleDelete"
                        @cancel="showConfirmDelete = false"
                    />
                    <v-btn
                        v-if="selectedMedia.length > 0"
                        color="success"
                        variant="elevated"
                        prepend-icon="mdi-check"
                        :block="$vuetify.display.smAndDown"
                        @click="handleConfirmSelect"
                    >
                        {{
                            $t("media_library.confirm_select_button", {
                                count: selectedMedia.length,
                            })
                        }}
                    </v-btn>
                </div>
            </div>
            <div class="image-gallery-container mt-5">
                <v-row v-if="!isLoading && mediaList.length > 0">
                    <v-col
                        v-for="media in mediaList"
                        :key="media.id"
                        cols="4"
                        md="3"
                    >
                        <div class="media-item-wrapper">
                            <v-checkbox-btn
                                :model-value="isMediaSelected(media)"
                                class="media-checkbox"
                                color="success"
                                @click.stop="selectMedia(media)"
                            />
                            <v-img
                                :aspect-ratio="1"
                                class="bg-surface rounded cursor-pointer elevation-3"
                                :src="media.path"
                                width="300"
                                cover
                                @click="selectMedia(media)"
                            />
                        </div>
                    </v-col>
                </v-row>
                <div
                    v-else-if="!isLoading && mediaList.length === 0"
                    class="d-flex justify-center align-center h-100"
                >
                    <p class="text-h6 text-medium-emphasis">
                        {{ $t("media_library.no_images") }}
                    </p>
                </div>
                <div v-else class="d-flex justify-center align-center">
                    <v-progress-circular
                        color="primary"
                        size="50"
                        indeterminate
                    />
                </div>
            </div>
        </v-col>

        <!-- Upload - Bên phải trên PC, ở trên trên mobile -->
        <v-col cols="12" md="4" class="upload-col order-first order-md-last">
            <v-file-upload
                v-model="selectedFiles"
                density="comfortable"
                :disabled="isUploading"
                class="upload-section"
                title="Upload images"
                accept="image/*"
                multiple
                @update:model-value="handleFileUpload"
            >
                <template v-if="isUploading" #append>
                    <v-progress-circular
                        :model-value="uploadProgress"
                        color="primary"
                        size="24"
                    />
                </template>
            </v-file-upload>
        </v-col>
    </v-row>
</template>

<script>
import { VFileUpload } from "vuetify/labs/VFileUpload";
import { uploadService } from "@/services/uploadService";
import ConfirmDialog from "./ConfirmDialog.vue";

export default {
    components: {
        VFileUpload,
        ConfirmDialog,
    },
    props: {
        isMultiple: {
            type: Boolean,
            default: false,
        },
        activeTab: {
            type: Number,
            default: 1,
        },
    },
    emits: ["uploadSuccess", "close"],
    data() {
        return {
            windowWidth: window.innerWidth,
            mediaList: [],
            selectedFiles: [],
            isLoading: false,
            isUploading: false,
            uploadProgress: 0,
            showConfirmDelete: false,
            isDeleting: false,
        };
    },
    computed: {
        selectedMedia() {
            return this.$store.getters["media/selectedMedia"];
        },
    },
    watch: {
        // Tự động refresh khi chuyển về tab Thư viện ảnh (tab = 1)
        activeTab(newTab) {
            if (newTab === 1) {
                this.getMedia();
            }
        },
    },
    created() {
        this.getMedia();
    },
    mounted() {
        window.addEventListener("resize", this.handleResize);
    },
    beforeUnmount() {
        window.removeEventListener("resize", this.handleResize);
    },
    methods: {
        handleResize() {
            this.windowWidth = window.innerWidth;
        },

        async handleFileUpload(files) {
            if (!files || files.length === 0) return;

            this.isUploading = true;
            const totalFiles = files.length;
            let uploadedCount = 0;

            try {
                for (const file of files) {
                    const formData = new FormData();
                    formData.append("file", file);

                    const response = await uploadService.upload(formData);
                    if (response.success) {
                        uploadedCount++;
                    }

                    this.uploadProgress = (uploadedCount / totalFiles) * 100;
                }

                this.selectedFiles = [];

                if (uploadedCount > 0) {
                    await this.getMedia();
                }
            } catch (error) {
                console.error("Upload error:", error);
            } finally {
                this.isUploading = false;
                this.uploadProgress = 0;
            }
        },

        async getMedia() {
            this.isLoading = true;
            const response = await uploadService.getAll();
            this.mediaList = response.data;
            this.isLoading = false;
        },

        isMediaSelected(media) {
            return this.selectedMedia.some((m) => m.id === media.id);
        },

        selectMedia(media) {
            if (this.isMultiple) {
                if (this.isMediaSelected(media)) {
                    const updatedMedia = this.selectedMedia.filter(
                        (m) => m.id !== media.id,
                    );
                    this.$store.commit(
                        "media/SET_SELECTED_MEDIA",
                        updatedMedia,
                    );
                } else {
                    this.$store.commit("media/SET_SELECTED_MEDIA", [
                        ...this.selectedMedia,
                        media,
                    ]);
                }
            } else {
                this.$store.commit("media/SET_SELECTED_MEDIA", [media]);
            }
        },

        async handleDelete() {
            try {
                this.isDeleting = true;
                await uploadService.delete(
                    this.selectedMedia.map((media) => media.id),
                );
            } catch (error) {
                console.error("Delete error:", error);
            } finally {
                this.$store.commit("media/SET_SELECTED_MEDIA", []);
                this.isDeleting = false;
                this.showConfirmDelete = false;
                await this.getMedia();
            }
        },

        handleConfirmSelect() {
            this.$emit("close");
        },
    },
};
</script>

<style scoped>
.media-item-wrapper {
    position: relative;
}

.media-checkbox {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 2;
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 4px;
}

.image-gallery-container {
    overflow-y: auto;
    overflow-x: hidden;
}

@media (max-width: 959px) {
    .image-gallery-container {
        height: 350px;
    }
}

@media (min-width: 960px) {
    .image-gallery-container {
        height: 500px;
    }
}

@media (max-width: 959px) {
    .upload-col {
        padding-bottom: 8px !important;
    }

    .upload-section :deep(.v-input__control) {
        min-height: 180px !important;
    }

    .upload-section :deep(.v-field__field) {
        padding: 12px !important;
    }

    .upload-section :deep(.v-field__append-inner) {
        padding-top: 8px !important;
    }
}
</style>
