<template>
    <div>
        <v-app-bar color="primary">
            <v-app-bar-nav-icon
                variant="text"
                @click.stop="drawer = !drawer"
            ></v-app-bar-nav-icon>

            <v-toolbar-title>My files</v-toolbar-title>

            <v-menu offset-y>
                <template #activator="{ props }">
                    <v-btn icon v-bind="props" class="mr-2">
                        <v-avatar size="40">
                            <v-img
                                v-if="currentUser?.avatarUrl"
                                alt="Avatar"
                                :src="currentUser?.avatarUrl"
                            />
                            <v-icon v-else size="40">mdi-account</v-icon>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-list min-width="200">
                    <v-list-item>
                        <v-list-item-title class="font-weight-bold mb-2">
                            {{ currentUser?.fullName || "User" }}
                        </v-list-item-title>
                        <v-list-item-subtitle>
                            {{ currentUser?.email || "" }}
                        </v-list-item-subtitle>
                    </v-list-item>
                    <v-divider />
                    <v-list-item @click="goToProfile">
                        <template #prepend>
                            <v-icon>mdi-account</v-icon>
                        </template>
                        <v-list-item-title>Hồ sơ</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="handleLogout">
                        <template #prepend>
                            <v-icon color="error">mdi-logout</v-icon>
                        </template>
                        <v-list-item-title class="text-error">
                            Đăng xuất
                        </v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <v-navigation-drawer
            v-model="drawer"
            :location="$vuetify.display.mobile ? 'left' : undefined"
            temporary
        >
            <MenuSidebar />
        </v-navigation-drawer>
    </div>
</template>

<script>
import MenuSidebar from "./MenuSidebar.vue";
export default {
    components: {
        MenuSidebar,
    },
    data() {
        return {
            drawer: false,
        };
    },
};
</script>

<style></style>
