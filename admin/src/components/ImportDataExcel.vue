<template>
    <div>
        <v-btn
            prepend-icon="mdi-microsoft-excel"
            color="secondary"
            variant="outlined"
            @click="dialog = true"
        >
            {{ $t("base.import_excel") }}
        </v-btn>

        <v-dialog v-model="dialog" max-width="600" scrollable>
            <v-card
                prepend-icon="mdi-microsoft-excel"
                :title="$t('base.import_excel')"
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="close-btn"
                    @click="dialog = false"
                />
                <v-card-text>
                    <div class="text-h6 mb-5">Bước 1: Tải file excel mẫu</div>
                    <DowloadTemplateImportDataExcel :path="path" />

                    <div class="text-h6 my-5">
                        Bước 2: Tải lên file dữ liệu sau khi đã điền đầy đủ
                        thông tin
                    </div>
                    <div class="d-flex align-center justify-center ga-2">
                        <v-file-input
                            v-model="file"
                            label="Chọn file Excel (.xlsx hoặc .xls)"
                            accept=".xlsx, .xls"
                            show-size
                            prepend-inner-icon="mdi-microsoft-excel"
                            variant="outlined"
                            hide-details
                        />
                        <v-btn
                            :loading="loading"
                            color="primary"
                            :disabled="!file"
                            size="x-large"
                            @click="uploadFile"
                        >
                            {{ $t("base.upload") }}
                        </v-btn>
                    </div>
                    <p v-if="note" class="text-caption mt-5">
                        <v-icon
                            icon="mdi-information-outline"
                            color="warning"
                        />
                        {{ $t("base.note") }} {{ note }}
                    </p>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { postDataWithFile } from "@/services/bases/postData";
import DowloadTemplateImportDataExcel from "./DowloadTemplateImportDataExcel.vue";

export default {
    components: {
        DowloadTemplateImportDataExcel,
    },
    props: {
        path: {
            type: String,
            required: true,
        },
        note: {
            type: String,
            default: "",
        },
    },
    emits: ["reload"],
    data() {
        return {
            dialog: false,
            file: null,
            loading: false,
        };
    },
    methods: {
        async uploadFile() {
            if (!this.file) return;

            this.loading = true;
            const formData = new FormData();
            formData.append("file", this.file); // Vuetify 3 file input trả về mảng hoặc object tuỳ version, kiểm tra kỹ

            try {
                await postDataWithFile(this.path + "/import", formData);

                this.file = null; // Reset file
                this.$emit("reload");
                this.dialog = false;
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style></style>
