<template>
    <div>
        <v-app-bar color="primary">
            <v-app-bar-nav-icon
                variant="text"
                @click.stop="drawer = !drawer"
            ></v-app-bar-nav-icon>

            <v-toolbar-title>My files</v-toolbar-title>

            <template v-if="$vuetify.display.mdAndUp">
                <v-btn icon="mdi-magnify" variant="text"></v-btn>

                <v-btn icon="mdi-filter" variant="text"></v-btn>
            </template>

            <v-btn icon="mdi-dots-vertical" variant="text"></v-btn>
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
