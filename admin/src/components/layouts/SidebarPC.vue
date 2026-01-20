<template>
    <div>
        <v-navigation-drawer location="left" permanent>
            <template v-slot:prepend>
                <div
                    class="d-flex flex-column align-center justify-center pa-4"
                >
                    <v-avatar size="50" class="mb-3 elevation-2 bg-white">
                        <v-img
                            alt="Logo"
                            src="https://brandeps.com/logo-download/H/HTML5-Boilerplate-logo-01.png"
                        />
                    </v-avatar>
                    <div
                        class="text-h6 font-weight-bold text-uppercase text-center text-primary mb-1"
                    >
                        {{ appName }}
                    </div>
                    <div
                        class="text-subtitle-2 font-weight-light text-medium-emphasis text-center"
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
            subName: import.meta.env.VITE_APP_SUBNAME,
        };
    },
    computed: {
        title() {
            return this.$route.meta.title;
        },
        icon() {
            return this.$route.meta.icon;
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
