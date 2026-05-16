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
import CreateEditDepartment from "./CreateEditDepartment.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "Department",
    components: {
        DepartmentList,
        CreateEditDepartment,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.boPhan,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("department", ["fetchDepartments"]),
        async getDanhSach(params) {
            await this.fetchDepartments(params);
        },
    },
};
</script>

<style></style>
