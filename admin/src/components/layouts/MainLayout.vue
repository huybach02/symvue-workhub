<template>
    <NotAuthenticatedMiddleware>
        <v-card>
            <v-layout>
                <SidebarMobile v-if="isMobile" />
                <SidebarPC v-else />
                <v-main style="height: 100vh">
                    <v-card class="ma-2 pa-4 main-content-card" elevation="3">
                        <router-view />
                    </v-card>
                </v-main>
            </v-layout>
        </v-card>
    </NotAuthenticatedMiddleware>
</template>

<script>
import { mapGetters } from "vuex";
import NotAuthenticatedMiddleware from "@/middlewares/NotAuthenticatedMiddleware.vue";
import SidebarPC from "./SidebarPC.vue";
import SidebarMobile from "./SidebarMobile.vue";

export default {
    name: "MainLayout",
    components: {
        SidebarPC,
        SidebarMobile,
        NotAuthenticatedMiddleware,
    },
    computed: {
        ...mapGetters("auth", ["currentUser"]),
        isMobile() {
            return this.$vuetify.display.mobile;
        },
    },
};
</script>

<style>
.main-content-card {
    min-height: calc(100vh - 100px);
}
</style>
