<template>
    <div>
        <v-btn
            :loading="isExporting"
            prepend-icon="mdi-microsoft-excel"
            color="success"
            variant="outlined"
            @click="handleExport"
        >
            {{ $t("base.export_excel") }}
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
            isExporting: false,
        };
    },
    methods: {
        async handleExport() {
            this.isExporting = true;
            try {
                const response = await exportData(this.path);

                const url = window.URL.createObjectURL(
                    new Blob([response.data]),
                );

                const link = document.createElement("a");
                link.href = url;

                const contentDisposition =
                    response.headers?.["content-disposition"];
                let fileName = `data_export_${new Date().toISOString().split("T")[0]}.xlsx`;
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

                toast.success("Xuất file thành công!");
            } catch (error) {
                console.error("Lỗi xuất file:", error);
                toast.error("Có lỗi xảy ra khi xuất file.");
            } finally {
                this.isExporting = false;
            }
        },
    },
};
</script>

<style></style>
