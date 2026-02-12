<template>
    <div>
        <v-row>
            <v-col cols="12">
                <ThemSuaBoPhan
                    :path="path"
                    mode="create"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <DanhSachBoPhan
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
import DanhSachBoPhan from "./DanhSachBoPhan.vue";
import { getListData } from "@/services/bases/getData";
import ThemSuaBoPhan from "./ThemSuaBoPhan.vue";

export default {
    name: "BoPhan",
    components: {
        DanhSachBoPhan,
        ThemSuaBoPhan,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.boPhan,
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
