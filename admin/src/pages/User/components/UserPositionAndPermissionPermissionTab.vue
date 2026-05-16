<template>
    <div>
        <v-alert
            v-if="
                customPermission &&
                customPermission.length &&
                permissions.length
            "
            type="info"
            variant="tonal"
            class="mb-3"
        >
            {{
                $t("position.notification", {
                    count: customPermission.length,
                    listModuleCustomPermission: listModuleCustomPermission,
                })
            }}
        </v-alert>

        <v-alert v-if="!userId" type="info" variant="tonal">
            Chua xac dinh duoc nguoi dung de tai permission.
        </v-alert>

        <DepartmentPermissionEditor
            v-else
            v-model="permissions"
            :show-position-list="false"
        />

        <div v-if="userId" class="d-flex justify-space-between mt-4">
            <div>
                <v-btn
                    v-if="
                        customPermission &&
                        customPermission.length &&
                        permissions.length
                    "
                    color="warning"
                    :loading="saving"
                    @click="showConfirmRestore = true"
                >
                    {{ $t("button.restore_default") }}
                </v-btn>
            </div>
            <v-btn color="primary" :loading="saving" @click="savePermissions">
                {{ $t("button.update") }}
            </v-btn>
        </div>

        <ConfirmDialog
            v-model="showConfirmRestore"
            :message="`Bạn có chắc chắn muốn khôi phục permission mặc định cho người dùng này không?`"
            :loading="isRestoring"
            @confirm="restorePermissions"
            @cancel="showConfirmRestore = false"
        />
    </div>
</template>

<script>
import DepartmentPermissionEditor from "@/pages/Department/components/DepartmentPermissionEditor.vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        DepartmentPermissionEditor,
        ConfirmDialog,
    },
    props: {
        item: {
            type: Object,
            default: null,
        },
        userId: {
            type: [Number, String],
            default: null,
        },
    },
    data() {
        return {
            saving: false,
            permissions: [],
            showConfirmRestore: false,
            isRestoring: false,
        };
    },
    computed: {
        ...mapGetters("user", ["customPermissionByUserId"]),
        customPermission() {
            return this.customPermissionByUserId(this.userId);
        },
        listModuleCustomPermission() {
            return (this.customPermission ?? []).join(", ");
        },
    },
    watch: {
        userId: {
            async handler() {
                await this.loadPermissions();
                await this.checkUserHasCustomPermission();
            },
            immediate: true,
        },
    },
    methods: {
        ...mapActions("user", [
            "fetchUserCustomPermission",
            "fetchUserPermissions",
            "restoreDefaultPermissions",
            "updateUserPermissions",
        ]),
        async loadPermissions() {
            if (!this.userId) {
                this.permissions = [];
                return;
            }

            this.permissions = await this.fetchUserPermissions(this.userId);
        },
        async savePermissions() {
            if (!this.userId) {
                return;
            }

            this.saving = true;

            try {
                const response = await this.updateUserPermissions({
                    userId: this.userId,
                    permissions: this.permissions,
                });

                if (response) {
                    this.permissions = response ?? [];
                }
            } finally {
                this.saving = false;
                await this.checkUserHasCustomPermission();
            }
        },
        async checkUserHasCustomPermission() {
            if (!this.userId) {
                return;
            }

            await this.fetchUserCustomPermission(this.userId);
        },
        async restorePermissions() {
            this.isRestoring = true;

            await this.restoreDefaultPermissions(this.userId);
            this.isRestoring = false;
            this.showConfirmRestore = false;
        },
    },
};
</script>
