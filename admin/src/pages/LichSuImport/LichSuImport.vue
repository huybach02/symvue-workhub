<template>
    <div>
        <v-row>
            <v-col cols="12">
                <DanhSachLichSuImport
                    :path="path"
                    :items="items"
                    :total-items="totalItems"
                    :loading="loading"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import DanhSachLichSuImport from "./DanhSachLichSuImport.vue";
import { getListData } from "@/services/bases/getData";

export default {
    name: "LichSuImport",
    components: {
        DanhSachLichSuImport,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.lichSuImport,
            items: [],
            totalItems: 0,
            loading: false,
        };
    },
    created() {
        this.getDanhSach();
    },
    methods: {
        getDanhSach: async function (params) {
            try {
                this.loading = true;
                const response = await getListData(this.path, params);
                this.items = response.data || [];
                this.totalItems = response.total || 0;
            } catch (error) {
                console.error("Lỗi khi lấy danh sách:", error);
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style></style>
