<template>
    <NotAuthenticatedMiddleware>
        <PermissionMiddleware>
            <v-card>
                <v-layout>
                    <SidebarMobile v-if="isMobile" />
                    <SidebarPC v-else />
                    <v-main style="min-height: 100vh; overflow-y: auto">
                        <v-progress-linear
                            v-if="$store.state.isLoading"
                            color="primary"
                            indeterminate
                            height="5"
                        />
                        <v-card
                            class="ma-2 pa-4 main-content-card"
                            elevation="3"
                        >
                            <router-view />
                        </v-card>
                    </v-main>
                </v-layout>
            </v-card>
        </PermissionMiddleware>
    </NotAuthenticatedMiddleware>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import NotAuthenticatedMiddleware from "@/middlewares/NotAuthenticatedMiddleware.vue";
import SidebarPC from "./SidebarPC.vue";
import SidebarMobile from "./SidebarMobile.vue";
import PermissionMiddleware from "@/middlewares/PermissionMiddleware.vue";
import axiosInstance from "@/configs/axios";
import { createMercureConnection } from "@/services/mercureService";

export default {
    name: "MainLayout",
    components: {
        SidebarPC,
        SidebarMobile,
        NotAuthenticatedMiddleware,
        PermissionMiddleware,
    },
    data() {
        return {
            mercureConnection: null,
            connectionStatus: "connecting",
        };
    },
    computed: {
        ...mapGetters("auth", ["currentUser"]),
        isMobile() {
            return this.$vuetify.display.mobile;
        },
    },
    watch: {
        currentUser(newVal) {
            if (newVal && newVal.id) {
                this.connectMercure();
                this.danhSachThongBao();
            }
        },
    },
    created() {
        this.mercureConnection = createMercureConnection({
            getCurrentUser: () => this.currentUser,
            onStatusChange: (status) => {
                this.connectionStatus = status;
            },
        });
        this.fetchDefaultPermissions();
    },
    beforeUnmount() {
        this.mercureConnection?.disconnect();
    },
    methods: {
        ...mapActions("department", ["fetchDefaultPermissions"]),
        connectMercure() {
            this.mercureConnection?.connect();
        },

        async danhSachThongBao() {
            try {
                const res = await axiosInstance.get(
                    `/mercure/notification-list/${this.currentUser?.id}`,
                );
                this.$store.commit("mercure/SET_NOTIFICATIONS", res.data);
            } catch (error) {
                console.error("Không thể lấy danh sách thông báo:", error);
            }
        },
    },
};
</script>

<style>
.main-content-card {
    min-height: calc(100vh - 100px);
}
</style>
