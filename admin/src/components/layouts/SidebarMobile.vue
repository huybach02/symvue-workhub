<template>
    <div>
        <v-app-bar color="primary" style="z-index: 3000">
            <v-app-bar-nav-icon
                variant="text"
                @click.stop="drawer = !drawer"
            ></v-app-bar-nav-icon>

            <v-toolbar-title class="toolbar-title-mobile">
                <v-avatar size="35" class="elevation-2 bg-white mr-2">
                    <v-img alt="Logo" :src="logo" />
                </v-avatar>
                {{ title }}
            </v-toolbar-title>

            <div class="d-flex align-center ga-2">
                <ChatRealTime />
                <NotificationRealtime />
                <UserDropdown @logout="handleLogout" />
            </div>
        </v-app-bar>

        <v-navigation-drawer
            v-model="drawer"
            :location="$vuetify.display.mobile ? 'left' : undefined"
            temporary
            width="276"
        >
            <MenuSidebar />
        </v-navigation-drawer>
    </div>
</template>

<script>
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";
import MenuSidebar from "./MenuSidebar.vue";
import { authService } from "@/services/authService";
import UserDropdown from "@/components/UserDropdown.vue";
import NotificationRealtime from "@/components/NotificationRealtime.vue";
import ChatRealTime from "@/components/ChatRealTime.vue";

export default {
    components: {
        MenuSidebar,
        UserDropdown,
        NotificationRealtime,
        ChatRealTime,
    },
    data() {
        return {
            drawer: false,
        };
    },
    computed: {
        title() {
            return this.$route.meta.title;
        },
        logo() {
            return import.meta.env.VITE_LOGO_DEFAULT;
        },
        currentUser() {
            return this.$store.state.user;
        },
    },
    methods: {
        goToProfile() {
            console.log("Go to profile");
        },
        async handleLogout() {
            const response = await authService.logout();
            if (response.success) {
                this.$store.commit("auth/CLEAR_AUTH_DATA");
                this.$router.push({ name: NAME_ROUTES_CONFIG.login });
            }
        },
    },
};
</script>

<style>
.toolbar-title-mobile {
    font-size: 1rem !important;
}
</style>
