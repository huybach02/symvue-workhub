<template>
    <div>
        <v-row class="mt-2">
            <v-col cols="12" class="d-flex align-center flex-wrap gap-4">
                <v-btn
                    color="primary"
                    prepend-icon="mdi-pencil-plus"
                    :disabled="loading"
                    @click="isDialogOpen = true"
                >
                    {{ $t("profile.signature.setup") }}
                </v-btn>

                <v-btn
                    v-if="signature?.signaturePngUrl"
                    color="error"
                    variant="outlined"
                    prepend-icon="mdi-delete"
                    :disabled="loading"
                    @click="confirmDelete"
                >
                    {{ $t("profile.signature.delete_btn") }}
                </v-btn>
            </v-col>

            <v-col v-if="signature?.signaturePngUrl" cols="12" md="5">
                <v-card variant="outlined" class="signature-preview-card pa-4">
                    <div
                        class="signature-preview-container checkerboard d-flex align-center justify-center rounded-lg"
                    >
                        <v-img
                            :src="resolveMediaUrl(signature.signaturePngUrl)"
                            class="signature-image"
                            max-height="160"
                            contain
                        />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <SignatureDialog
            v-model="isDialogOpen"
            :signature="signature"
            @saved="handleSaved"
        />

        <ConfirmDialog
            v-model="isConfirmDeleteOpen"
            :title="$t('profile.signature.delete_btn')"
            :message="$t('profile.signature.delete_confirm')"
            :loading="loading"
            @confirm="handleDelete"
        />
    </div>
</template>

<script>
import { profileSignatureService } from "@/services/profileSignatureService";
import SignatureDialog from "./SignatureDialog.vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

export default {
    name: "UserSignatureSection",
    components: {
        SignatureDialog,
        ConfirmDialog,
    },
    data() {
        return {
            signature: null,
            isDialogOpen: false,
            isConfirmDeleteOpen: false,
            loading: false,
            timestamp: Date.now(),
        };
    },
    mounted() {
        this.fetchSignature();
    },
    methods: {
        async fetchSignature() {
            this.loading = true;
            try {
                const res = await profileSignatureService.getSignature();
                this.signature = res;
            } catch (error) {
                console.error("Lỗi khi tải chữ ký:", error);
            } finally {
                this.loading = false;
            }
        },
        async handleSaved({ pngDataUrl, svgContent, strokes, width, height }) {
            this.isDialogOpen = false;
            this.loading = true;
            try {
                const res = await profileSignatureService.saveSignature({
                    pngDataUrl,
                    svgContent,
                    strokes,
                    width,
                    height,
                });
                if (res) {
                    this.signature = res;
                    this.timestamp = Date.now();
                }
            } catch (error) {
                console.error("Lỗi khi lưu chữ ký:", error);
            } finally {
                this.loading = false;
            }
        },
        confirmDelete() {
            this.isConfirmDeleteOpen = true;
        },
        async handleDelete() {
            this.loading = true;
            try {
                const success = await profileSignatureService.deleteSignature();
                if (success) {
                    this.signature = null;
                }
            } catch (error) {
                console.error("Lỗi khi xóa chữ ký:", error);
            } finally {
                this.loading = false;
                this.isConfirmDeleteOpen = false;
            }
        },
        resolveMediaUrl(url) {
            if (!url) return "";
            const apiBaseUrl =
                import.meta.env.VITE_API_BASE_URL ||
                "http://localhost:8000/api";
            let apiOrigin = window.location.origin;
            try {
                apiOrigin = new URL(apiBaseUrl, window.location.origin).origin;
            } catch (e) {
                console.error(e);
            }
            const separator = url.includes("?") ? "&" : "?";
            const bustedUrl = `${url}${separator}t=${this.timestamp}`;
            if (bustedUrl.startsWith("/")) {
                return `${apiOrigin}${bustedUrl}`;
            }
            return bustedUrl;
        },
    },
};
</script>

<style scoped>
.signature-preview-card {
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 8px;
    background-color: #fafafa;
}

.signature-preview-container {
    width: 100%;
    height: 180px;
    position: relative;
    border: 1px solid #e0e0e0;
}

.signature-image {
    width: 100%;
    height: 100%;
}

.gap-4 {
    gap: 16px;
}

/* Nền checkerboard chuyên nghiệp cho ảnh transparent */
.checkerboard {
    background-image:
        linear-gradient(45deg, #f0f0f0 25%, transparent 25%),
        linear-gradient(-45deg, #f0f0f0 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #f0f0f0 75%),
        linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
    background-size: 20px 20px;
    background-position:
        0 0,
        0 10px,
        10px -10px,
        -10px 0px;
    background-color: #ffffff;
}
</style>
