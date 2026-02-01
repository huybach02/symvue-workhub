<template>
    <div>
        <v-row>
            <v-col cols="12">
                <ThemNguoiDung :path="path" />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <DanhSachNguoiDung
                    :path="path"
                    :users="users"
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
import DanhSachNguoiDung from "./DanhSachNguoiDung.vue";
import ThemNguoiDung from "./ThemNguoiDung.vue";
import { getListData } from "@/services/bases/getData";

export default {
    name: "NguoiDung",
    components: {
        DanhSachNguoiDung,
        ThemNguoiDung,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.user,
            users: [],
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
                this.users = response.data || [];
                this.totalItems = response.total || 0;
            } catch (error) {
                console.error("Lỗi khi lấy danh sách người dùng:", error);
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style></style>
