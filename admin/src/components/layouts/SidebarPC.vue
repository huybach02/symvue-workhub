<template>
    <div>
        <v-navigation-drawer location="left" permanent>
            <template v-slot:prepend>
                <div
                    class="d-flex flex-column align-center justify-center pa-4"
                >
                    <v-avatar size="50" class="mb-3 elevation-2 bg-white">
                        <v-img alt="Logo" :src="logo" />
                    </v-avatar>
                    <div
                        class="text-h6 font-weight-bold text-uppercase text-center text-primary mb-1"
                    >
                        {{ appName }}
                    </div>
                    <div
                        class="text-subtitle-2 text-uppercase font-weight-light text-medium-emphasis text-center"
                    >
                        {{ subName }}
                    </div>
                </div>
            </template>

            <v-divider />

            <MenuSidebar />
        </v-navigation-drawer>

        <v-app-bar>
            <v-app-bar-title
                class="font-weight-bold text-uppercase text-primary"
            >
                <div class="d-flex align-center">
                    <v-icon size="24" class="mr-2">{{ icon }}</v-icon>
                    <span>{{ title }}</span>
                </div>
            </v-app-bar-title>
            <UserDropdown @logout="handleLogout" />
        </v-app-bar>
    </div>
</template>

<script>
import { authService } from "@/services/authService";
import MenuSidebar from "./MenuSidebar.vue";
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";
import UserDropdown from "@/components/UserDropdown.vue";

export default {
    components: {
        MenuSidebar,
        UserDropdown,
    },
    data() {
        return {
            appName: import.meta.env.VITE_APP_NAME,
        };
    },
    computed: {
        title() {
            return this.$route.meta.title;
        },
        icon() {
            return this.$route.meta.icon;
        },
        logo() {
            return import.meta.env.VITE_LOGO_DEFAULT;
        },
        subName() {
            return this.$t("system_management");
        },
    },
    methods: {
        goToProfile() {
            this.$router.push({ name: "profile" });
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

<style></style>
