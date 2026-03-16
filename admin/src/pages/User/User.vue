<template>
    <div>
        <v-row>
            <v-col cols="12" md="5">
                <div class="d-flex ga-2">
                    <ExportDataExcel v-if="permission?.export" :path="path" />
                    <ImportDataExcel
                        v-if="permission?.import"
                        :path="path"
                        :note="`Mật khẩu mặc định của tất cả người dùng sau khi import là 'password'`"
                        @reload="getDanhSach"
                    />
                </div>
            </v-col>
            <v-col cols="12" md="7">
                <CreateEditUser
                    v-if="permission?.create"
                    :path="path"
                    mode="create"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <UserList
                    v-if="permission?.index"
                    :path="path"
                    :users="users"
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
import UserList from "./UserList.vue";
import { getListData } from "@/services/bases/getData";
import CreateEditUser from "./CreateEditUser.vue";
import ExportDataExcel from "@/components/ExportDataExcel.vue";
import ImportDataExcel from "@/components/ImportDataExcel.vue";
import { usePermission } from "@/hooks/usePermission";

export default {
    name: "User",
    components: {
        UserList,
        CreateEditUser,
        ExportDataExcel,
        ImportDataExcel,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.user,
            users: [],
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
