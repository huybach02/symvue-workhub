<template>
    <div>
        <v-btn
            icon
            size="small"
            variant="outlined"
            color="info"
            @click="dialog = true"
        >
            <v-icon>mdi-shield-account-outline</v-icon>
        </v-btn>

        <v-dialog v-model="dialog" max-width="1100" scrollable persistent>
            <v-card
                :title="
                    $t('position.position_and_permission', {
                        name: item?.name || '',
                    })
                "
                prepend-icon="mdi-shield-account-outline"
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="position-absolute"
                    style="top: 8px; right: 8px"
                    @click="dialog = false"
                />

                <v-card-text>
                    <v-tabs v-model="tab" color="primary">
                        <v-tab value="position">
                            {{ $t("position.position") }}
                        </v-tab>
                        <v-tab value="permission">
                            {{ $t("position.user_permission") }}
                        </v-tab>
                    </v-tabs>

                    <v-tabs-window v-model="tab" class="mt-4">
                        <v-tabs-window-item value="position">
                            <UserPositionAndPermissionPositionTab
                                :item="item"
                                :path="path"
                                :loading="loading"
                                :grouped-positions="groupedPositions"
                                :expanded-departments="expandedDepartments"
                                @update:expanded-departments="
                                    expandedDepartments = $event
                                "
                                @reload="handlePositionReload"
                            />
                        </v-tabs-window-item>

                        <v-tabs-window-item value="permission">
                            <UserPositionAndPermissionPermissionTab />
                        </v-tabs-window-item>
                    </v-tabs-window>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getDataById } from "@/services/bases/getData";
import UserPositionAndPermissionPermissionTab from "./components/UserPositionAndPermissionPermissionTab.vue";
import UserPositionAndPermissionPositionTab from "./components/UserPositionAndPermissionPositionTab.vue";

export default {
    components: {
        UserPositionAndPermissionPositionTab,
        UserPositionAndPermissionPermissionTab,
    },
    props: {
        path: {
            type: String,
            default: "",
        },
        item: {
            type: Object,
            default: null,
        },
    },
    emits: ["reload"],
    data() {
        return {
            dialog: false,
            tab: "position",
            loading: false,
            positions: [],
            expandedDepartments: [],
        };
    },
    computed: {
        groupedPositions() {
            const groupedByDepartmentId = new Map();

            this.positions.forEach((positionItem) => {
                const departmentId = positionItem.department?.id ?? "unknown";

                if (!groupedByDepartmentId.has(departmentId)) {
                    groupedByDepartmentId.set(departmentId, {
                        departmentId,
                        departmentName:
                            positionItem.department?.tenBoPhan ||
                            "Chua thuoc bo phan",
                        hasPrimary: false,
                        positions: [],
                    });
                }

                const departmentGroup = groupedByDepartmentId.get(departmentId);
                departmentGroup.positions.push(positionItem);

                if (positionItem.isPrimary) {
                    departmentGroup.hasPrimary = true;
                }
            });

            return Array.from(groupedByDepartmentId.values());
        },
    },
    watch: {
        dialog: {
            async handler(isOpen) {
                if (isOpen) {
                    await this.loadPositions();
                    this.expandedDepartments = this.groupedPositions.map(
                        (group) => group.departmentId,
                    );
                    return;
                }

                this.tab = "position";
                this.expandedDepartments = [];
            },
        },
    },
    methods: {
        async loadPositions() {
            this.loading = true;

            try {
                this.positions =
                    (await getDataById(
                        API_ROUTES_CONFIG.user,
                        this.item?.id,
                        "vi-tri-cong-viec/danh-sach",
                    )) ?? [];
            } finally {
                this.loading = false;
            }
        },
        async handlePositionReload() {
            await this.loadPositions();
            this.$emit("reload");
        },
    },
};
</script>
