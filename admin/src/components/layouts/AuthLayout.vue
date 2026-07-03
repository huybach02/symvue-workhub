<template>
    <div class="d-flex align-center justify-center" style="min-height: 100vh">
        <v-card class="px-4 py-6" elevation="16" width="400">
            <v-card-item>
                <v-card-title class="text-center">
                    <div class="mb-3">
                        <v-avatar size="x-large">
                            <v-img
                                alt="Logo"
                                :src="logoUrl"
                            />
                        </v-avatar>
                    </div>
                    <h2 class="mb-3">{{ appName }}</h2>
                    <v-chip class="mb-5 text-uppercase font-weight-bold">
                        {{ subName }}
                    </v-chip>
                    <h3 class="mb-3 text-uppercase">{{ title }}</h3>
                </v-card-title>
            </v-card-item>

            <v-card-text>
                <v-sheet class="mx-auto">
                    <router-view />
                </v-sheet>
            </v-card-text>
        </v-card>
    </div>
</template>

<script>
export default {
    name: "AuthLayout",
    data() {
        return {
            appName: import.meta.env.VITE_APP_NAME,
        };
    },
    computed: {
        logoUrl() {
            return import.meta.env.VITE_LOGO_DEFAULT;
        },
        title() {
            return this.$route.meta.title || this.$t("auth.login");
        },
        subName() {
            return this.$t("system_management");
        },
    },
};
</script>

<style></style>
