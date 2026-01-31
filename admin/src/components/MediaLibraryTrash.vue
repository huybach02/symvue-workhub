<template>
    <v-container fluid>
        <div class="d-flex justify-space-between align-center mb-4">
            <div v-if="mediaList.length > 0" class="d-flex align-center">
                <v-btn
                    :color="selectAll ? 'success' : 'primary'"
                    :prepend-icon="
                        selectAll ? 'mdi-check' : 'mdi-checkbox-blank-outline'
                    "
                    @click="toggleSelectAll"
                >
                    {{
                        selectAll
                            ? $t("media_library.deselect_all")
                            : $t("media_library.select_all")
                    }}
                    <span v-if="selectedMedia.length > 0" class="ml-2">
                        ({{ selectedMedia.length }}/{{ mediaList.length }})
                    </span>
                </v-btn>
            </div>
            <div
                v-if="selectedMedia.length > 0"
                class="d-flex align-center ga-2"
            >
                <v-btn
                    color="warning"
                    variant="elevated"
                    prepend-icon="mdi-delete"
                    @click="handleRestore"
                >
                    {{
                        $t("media_library.restore_button", {
                            count: selectedMedia.length,
                        })
                    }}
                </v-btn>
                <v-btn
                    color="error"
                    variant="elevated"
                    prepend-icon="mdi-delete"
                    @click="handleDeletePermanently"
                >
                    {{
                        $t("media_library.delete_permanently_button", {
                            count: selectedMedia.length,
                        })
                    }}
                </v-btn>
                <ConfirmDialog
                    v-model="showConfirmDialog"
                    :title="dialogTitle"
                    :message="dialogMessage"
                    :icon="dialogIcon"
                    :confirm-text="dialogConfirmText"
                    :confirm-color="dialogConfirmColor"
                    :loading="isProcessing"
                    @confirm="confirmAction"
                    @cancel="showConfirmDialog = false"
                />
            </div>
        </div>
        <v-row v-if="!isLoading && mediaList.length > 0">
            <v-col v-for="media in mediaList" :key="media.id" cols="6" md="2">
                <div class="media-item-wrapper">
                    <v-checkbox-btn
                        v-model="selectedMedia"
                        :value="media.id"
                        class="media-checkbox"
                        color="success"
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
                {{ $t("media_library.no_images_in_trash") }}
            </p>
        </div>
        <div v-else class="d-flex justify-center align-center">
            <v-progress-circular color="primary" size="50" indeterminate />
        </div>
    </v-container>
</template>

<script>
import { uploadService } from "@/services/uploadService";
import ConfirmDialog from "./ConfirmDialog.vue";

export default {
    components: {
        ConfirmDialog,
    },
    props: {
        activeTab: {
            type: Number,
            default: 1,
        },
    },
    data() {
        return {
            mediaList: [],
            isLoading: false,
            selectedMedia: [],
            showConfirmDialog: false,
            isProcessing: false,
            actionType: null,
        };
    },
    computed: {
        selectAll: {
            get() {
                return (
                    this.mediaList.length > 0 &&
                    this.selectedMedia.length === this.mediaList.length
                );
            },
            set(value) {
                if (value) {
                    this.selectedMedia = this.mediaList.map(
                        (media) => media.id,
                    );
                } else {
                    this.selectedMedia = [];
                }
            },
        },

        dialogTitle() {
            return this.actionType === "restore"
                ? this.$t("media_library.restore_confirm_title")
                : this.$t("media_library.delete_permanently_confirm_title");
        },
        dialogMessage() {
            const count = this.selectedMedia.length;
            if (this.actionType === "restore") {
                return this.$t("media_library.restore_confirm_message", {
                    count,
                });
            }
            return this.$t("media_library.delete_permanently_confirm_message", {
                count,
            });
        },
        dialogIcon() {
            return this.actionType === "restore"
                ? "mdi-restore"
                : "mdi-delete-forever";
        },
        dialogConfirmText() {
            return this.actionType === "restore"
                ? this.$t("media_library.restore_action")
                : this.$t("media_library.delete_permanently_action");
        },
        dialogConfirmColor() {
            return this.actionType === "restore" ? "warning" : "error";
        },
    },
    watch: {
        activeTab(newTab) {
            if (newTab === 2) {
                this.getMediaTrash();
            }
        },
    },
    created() {
        this.getMediaTrash();
    },
    methods: {
        async getMediaTrash() {
            this.isLoading = true;
            const response = await uploadService.getMediaTrash();
            this.mediaList = response.data;
            this.isLoading = false;
        },

        toggleSelectAll() {
            this.selectAll = !this.selectAll;
        },

        selectMedia(media) {
            const index = this.selectedMedia.indexOf(media.id);
            if (index > -1) {
                this.selectedMedia.splice(index, 1);
            } else {
                this.selectedMedia.push(media.id);
            }
        },

        handleRestore() {
            this.actionType = "restore";
            this.showConfirmDialog = true;
        },

        handleDeletePermanently() {
            this.actionType = "delete";
            this.showConfirmDialog = true;
        },

        async confirmAction() {
            if (this.actionType === "restore") {
                await this.restoreMedia();
            } else if (this.actionType === "delete") {
                await this.deleteMediaPermanently();
            }
        },

        async restoreMedia() {
            try {
                this.isProcessing = true;
                await uploadService.restore(this.selectedMedia);
            } catch (error) {
                console.error("Lỗi khi khôi phục ảnh:", error);
            } finally {
                this.isProcessing = false;
                this.selectedMedia = [];
                this.showConfirmDialog = false;
                await this.getMediaTrash();
            }
        },

        async deleteMediaPermanently() {
            try {
                this.isProcessing = true;
                await uploadService.deletePermanently(this.selectedMedia);
            } catch (error) {
                console.error("Lỗi khi xóa vĩnh viễn ảnh:", error);
            } finally {
                this.isProcessing = false;
                this.selectedMedia = [];
                this.showConfirmDialog = false;
                await this.getMediaTrash();
            }
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
</style>
