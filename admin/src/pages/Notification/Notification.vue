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
import CreateEditNotification from "./CreateEditNotification.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "Notification",
    components: {
        NotificationList,
        CreateEditNotification,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.notifications,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("notification", ["fetchNotifications"]),
        async getDanhSach(params) {
            await this.fetchNotifications(params);
        },
    },
};
</script>

<style></style>
