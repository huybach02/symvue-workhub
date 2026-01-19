<template>
    <v-navigation-drawer location="left" permanent>
        <template v-slot:prepend>
            <v-list-item
                lines="two"
                prepend-avatar="https://randomuser.me/api/portraits/women/81.jpg"
                subtitle="Logged in"
                title="Jane Smith"
            ></v-list-item>
        </template>

        <v-divider></v-divider>

        <MenuSidebar />
    </v-navigation-drawer>
</template>

<script>
import MenuSidebar from "./MenuSidebar.vue";
export default {
    components: {
        MenuSidebar,
    },
};
</script>

<style></style>
