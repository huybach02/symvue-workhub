<template>
    <div>
        <v-row>
            <v-col cols="12">
                <CreateEditDepartment
                    v-if="permission?.create"
                    :path="path"
                    mode="create"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <DepartmentList
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
import DepartmentList from "./DepartmentList.vue";
import { getListData } from "@/services/bases/getData";
import CreateEditDepartment from "./CreateEditDepartment.vue";
import { usePermission } from "@/hooks/usePermission";

export default {
    name: "Department",
    components: {
        DepartmentList,
        CreateEditDepartment,
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
