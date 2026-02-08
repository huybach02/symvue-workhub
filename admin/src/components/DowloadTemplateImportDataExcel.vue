<template>
    <div>
        <v-btn
            :loading="isImporting"
            prepend-icon="mdi-microsoft-excel"
            color="success"
            variant="outlined"
            size="x-large"
            @click="handleImport"
        >
            {{ $t("base.dowload_template_import_excel") }}
        </v-btn>
    </div>
</template>

<script>
import { exportData } from "@/services/bases/getData";
import { toast } from "@/main";

export default {
    props: {
        path: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            isImporting: false,
        };
    },
    methods: {
        async handleImport() {
            this.isImporting = true;
            try {
                const response = await exportData(
                    this.path + "/template-import",
                );

                const url = window.URL.createObjectURL(
                    new Blob([response.data]),
                );

                const link = document.createElement("a");
                link.href = url;

                const contentDisposition =
                    response.headers?.["content-disposition"];
                let fileName = `data_import_${new Date().toISOString().split("T")[0]}.xlsx`;
                if (contentDisposition) {
                    const fileNameMatch =
                        contentDisposition.match(/filename="(.+)"/);
                    if (fileNameMatch && fileNameMatch.length === 2)
                        fileName = fileNameMatch[1];
                }

                link.setAttribute("download", fileName);
                document.body.appendChild(link);
                link.click();

                link.remove();
                window.URL.revokeObjectURL(url);

                toast.success("Tải file thành công!");
            } catch (error) {
                console.error("Lỗi tải file:", error);
                toast.error("Có lỗi xảy ra khi tải file.");
            } finally {
                this.isImporting = false;
            }
        },
    },
};
</script>

<style></style>
