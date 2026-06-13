<template>
    <div>
        <v-row>
            <v-col cols="12">
                <AttendanceList
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
import AttendanceList from "./AttendanceList.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "Attendance",
    components: {
        AttendanceList,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.attendance,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("attendance", ["fetchItems"]),
        async getDanhSach(params) {
            await this.fetchItems(params);
        },
    },
};
</script>

<style></style>
