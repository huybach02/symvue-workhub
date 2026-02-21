<template>
    <v-list density="compact" nav>
        <template v-for="(item, index) in filteredMenu">
            <!-- Menu có children (nested menu) -->
            <v-list-group
                v-if="item.children && item.children.length > 0"
                :key="`group-${index}`"
                :value="item.value"
            >
                <!-- Menu cha -->
                <template #activator="{ props }">
                    <v-list-item
                        v-bind="props"
                        :prepend-icon="item.icon"
                        :title="item.title"
                        :active="isParentActive(item)"
                    />
                </template>

                <!-- Menu con -->
                <v-list-item
                    v-for="(child, childIndex) in item.children"
                    :key="`child-${index}-${childIndex}`"
                    :prepend-icon="child.icon"
                    :title="child.title"
                    :value="child.value"
                    :to="child.to"
                    :active="activeItem === child.value"
                />
            </v-list-group>

            <!-- Menu không có children (menu thường) -->
            <v-list-item
                v-else
                :key="`item-${index}`"
                :prepend-icon="item.icon"
                :title="item.title"
                :value="item.value"
                :to="item.to"
                :active="activeItem === item.value"
            />
        </template>
    </v-list>
</template>

<script>
import { menuSidebar } from "@/configs/menuSidebar";
import { useSidebarPermission } from "@/hooks/useSidebarPermission";

export default {
    data() {
        return {
            menuSidebar,
        };
    },
    computed: {
        filteredMenu() {
            return useSidebarPermission(menuSidebar);
        },
        activeItem() {
            return this.$route.name;
        },
    },
    methods: {
        // Kiểm tra xem menu cha có đang active không
        // Menu cha sẽ active nếu một trong các menu con đang active
        isParentActive(item) {
            if (!item.children || item.children.length === 0) {
                return false;
            }
            // Kiểm tra xem route hiện tại có match với bất kỳ child nào không
            return item.children.some(
                (child) => this.activeItem === child.value,
            );
        },
    },
};
</script>

<style scoped>
:deep(.v-list-item-title) {
    font-weight: 500;
    font-size: 0.9rem;
    text-transform: uppercase;
}

:deep(.v-list-item--active .v-list-item-title) {
    font-weight: 700;
}

:deep(.v-list-item--active) {
    background-color: rgba(var(--v-theme-primary), 0.1) !important;
    color: rgb(var(--v-theme-primary)) !important;
}

/* Chỉ menu con trong group mới có màu nhạt hơn */
/* :deep(.v-list-group__items .v-list-item--active) {
    background-color: rgba(var(--v-theme-primary), 0.1) !important;
    color: rgb(var(--v-theme-primary)) !important;
} */

:deep(.v-list-item) {
    height: 45px;
}

:deep(.v-list-item__spacer) {
    width: 10px !important;
}

:deep(.v-list-item-title) {
    font-size: 0.91rem !important;
    padding: 1.5px;
}
</style>
