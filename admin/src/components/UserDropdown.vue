<template>
    <v-menu offset-y>
        <template #activator="{ props }">
            <v-btn icon v-bind="props" class="mr-2">
                <v-avatar size="40" class="border-md">
                    <v-img
                        v-if="currentUser?.avatarUrl"
                        alt="Avatar"
                        :src="currentUser?.avatarUrl"
                    />
                    <v-icon v-else size="30">mdi-account</v-icon>
                </v-avatar>
            </v-btn>
        </template>
        <v-list min-width="200">
            <v-list-item>
                <v-list-item-title class="font-weight-bold mb-2">
                    {{ currentUser?.name || "User" }}
                </v-list-item-title>
                <v-list-item-subtitle class="mb-2">
                    {{ currentUser?.email || "" }}
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider />

            <LanguageDropdown inline />

            <v-divider />

            <v-list-item @click="goToProfile">
                <template #prepend>
                    <v-icon>mdi-account</v-icon>
                </template>
                <v-list-item-title>{{ $t("auth.profile") }}</v-list-item-title>
            </v-list-item>
            <v-list-item @click="this.$emit('logout')">
                <template #prepend>
                    <v-icon color="error">mdi-logout</v-icon>
                </template>
                <v-list-item-title class="text-error">
                    {{ $t("auth.logout") }}
                </v-list-item-title>
            </v-list-item>
        </v-list>
    </v-menu>
</template>

<script>
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";
import { mapGetters } from "vuex";
import LanguageDropdown from "./LanguageDropdown.vue";

export default {
    name: "UserDropdown",
    components: {
        LanguageDropdown,
    },
    computed: {
        ...mapGetters("auth", ["currentUser"]),
    },
    methods: {
        goToProfile() {
            this.$router.push({ name: NAME_ROUTES_CONFIG.profile });
        },
    },
};
</script>

<style></style>
