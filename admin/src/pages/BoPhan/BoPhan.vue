<template>
    <div>
        <v-row>
            <v-col cols="12">
                <ThemSuaBoPhan
                    v-if="permission?.create"
                    :path="path"
                    mode="create"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <DanhSachBoPhan
                    v-if="permission?.index"
                    :path="path"
                    :items="items"
                    :total-items="totalItems"
                    :loading="loading"
                    :permission="permission"
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
import { usePermission } from "@/hooks/usePermission";

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
    computed: {
        permission() {
            return usePermission(this.path);
        },
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
