<template>
    <div>
        <v-row>
            <v-col cols="12" md="5"></v-col>
            <v-col cols="12" md="7">
                <CreateEditNotification
                    v-if="permission?.create"
                    :path="path"
                    mode="create"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <NotificationList
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
import NotificationList from "./NotificationList.vue";
import { getListData } from "@/services/bases/getData";
import CreateEditNotification from "./CreateEditNotification.vue";
import { usePermission } from "@/hooks/usePermission";

export default {
    name: "Notification",
    components: {
        NotificationList,
        CreateEditNotification,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.thongBao,
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
