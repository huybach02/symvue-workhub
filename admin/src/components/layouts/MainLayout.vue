<template>
    <NotAuthenticatedMiddleware>
        <v-card>
            <v-layout>
                <SidebarMobile v-if="isMobile" />
                <SidebarPC v-else />
                <v-main style="height: 100vh">
                    <router-view />
                </v-main>
            </v-layout>
        </v-card>
    </NotAuthenticatedMiddleware>
</template>

<script>
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
        isMobile() {
            return this.$vuetify.display.mobile;
        },
    },
};
</script>

<style></style>
