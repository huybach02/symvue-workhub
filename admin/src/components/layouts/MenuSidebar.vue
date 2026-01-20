<template>
    <v-list density="compact" nav>
        <v-list-item
            v-for="(item, index) in menuSidebar"
            :key="index"
            :prepend-icon="item.icon"
            :title="item.title"
            :value="item.value"
            :to="item.to"
            :active="activeItem === item.value"
        />
    </v-list>
</template>

<script>
import { menuSidebar } from "@/configs/menuSidebar";
export default {
    data() {
        return {
            menuSidebar,
        };
    },
    computed: {
        activeItem() {
            return this.$route.name;
        },
    },
};
</script>

<style scoped>
:deep(.v-list-item-title) {
    font-weight: 500;
    font-size: 0.9rem;
}

:deep(.v-list-item--active .v-list-item-title) {
    font-weight: 700;
}

:deep(.v-list-item) {
    height: 45px;
}

:deep(.v-list-item__spacer) {
    width: 10px !important;
}

:deep(.v-list-item-title) {
    font-size: 0.95rem !important;
    padding: 1.5px;
}
</style>
