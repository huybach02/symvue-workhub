<template>
    <div>
        <v-btn
            icon
            size="small"
            variant="outlined"
            color="primary"
            @click="dialog = true"
        >
            <v-icon>mdi-briefcase-account-outline</v-icon>
        </v-btn>

        <v-dialog v-model="dialog" max-width="1600" scrollable persistent>
            <v-card
                :title="
                    $t('bo_phan.text.positionPermissionDialogTitle', {
                        tenBoPhan: item.tenBoPhan,
                    })
                "
                prepend-icon="mdi-briefcase-account-outline"
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

                <v-alert
                    v-if="!item.positionManager"
                    type="error"
                    variant="tonal"
                >
                    {{ $t("bo_phan.text.notHasPositionManager") }}
                </v-alert>
                <v-alert v-else type="info" variant="tonal">
                    {{
                        $t("bo_phan.text.hasPositionManager", {
                            positionManager: item.positionManager.name,
                            positionManagerCode: item.positionManager.code,
                        })
                    }}
                </v-alert>

                <v-card-text class="d-flex flex-column ga-6">
                    <DepartmentPositionTable
                        :items="positions"
                        :loading="loading"
                        @create="handlePositionAction('create')"
                        @edit="handlePositionAction('update', $event)"
                        @delete="handlePositionAction('delete', $event)"
                    />

                    <v-divider />

                    <div>
                        <div class="text-h6 mb-2 font-weight-bold">
                            {{ $t("bo_phan.text.permission") }}
                        </div>

                        <v-alert
                            v-if="positions.length === 0"
                            type="info"
                            variant="tonal"
                        >
                            {{ $t("bo_phan.text.permissionRequiresPosition") }}
                        </v-alert>

                        <template v-else>
                            <DepartmentPermissionEditor
                                v-model="permissionsData"
                                :positions="positions"
                            />

                            <div class="d-flex justify-end mt-4">
                                <v-btn
                                    color="primary"
                                    :loading="savingPermissions"
                                    @click="savePermissions"
                                >
                                    {{
                                        $t(
                                            "bo_phan.button.savePermissionByPosition",
                                        )
                                    }}
                                </v-btn>
                            </div>
                        </template>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>

        <DepartmentPositionDialog
            v-model="positionDialog"
            :item="selectedPosition"
            :mode="positionDialogMode"
            :department="item"
            :loading="savingPosition"
            @submit="submitPosition"
        />

        <ConfirmDialog
            v-model="deleteDialog"
            :loading="deletingPositionLoading"
            icon="mdi-delete"
            @confirm="confirmDeletePosition"
            @cancel="deletingPosition = null"
        />
    </div>
</template>

<script>
import { getDataById } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";
import { deleteData } from "@/services/bases/deleteData";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import DepartmentPermissionEditor from "./components/DepartmentPermissionEditor.vue";
import DepartmentPositionDialog from "./components/DepartmentPositionDialog.vue";
import DepartmentPositionTable from "./components/DepartmentPositionTable.vue";

export default {
    components: {
        ConfirmDialog,
        DepartmentPermissionEditor,
        DepartmentPositionDialog,
        DepartmentPositionTable,
    },
    props: {
        item: {
            type: Object,
            default: null,
        },
        path: {
            type: String,
            default: "",
        },
        permission: {
            type: Object,
            default: null,
        },
    },
    emits: ["reload"],
    data() {
        return {
            dialog: false,
            loading: false,
            positions: [],
            permissionsData: {},
            positionDialog: false,
            positionDialogMode: "create",
            selectedPosition: null,
            savingPosition: false,
            savingPermissions: false,
            deleteDialog: false,
            deletingPosition: null,
            deletingPositionLoading: false,
        };
    },
    watch: {
        async dialog(isOpen) {
            if (isOpen) {
                await this.loadData();
            } else {
                this.positions = [];
                this.permissionsData = {};
                this.selectedPosition = null;
                this.deletingPosition = null;
            }
        },
    },
    methods: {
        async loadData() {
            this.loading = true;
            try {
                const [department, positions] = await Promise.all([
                    getDataById(this.path, this.item.id),
                    getDataById(this.path, this.item.id, "chuc-vu"),
                ]);

                this.permissionsData = department?.phanQuyen ?? {};
                this.positions = positions ?? [];
            } finally {
                this.loading = false;
            }
        },
        handlePositionAction(type, position = null) {
            if (type === "delete") {
                this.deletingPosition = position;
                this.deleteDialog = true;
                return;
            }

            this.positionDialogMode = type;
            this.selectedPosition = position;
            this.positionDialog = true;
        },
        async submitPosition(values) {
            this.savingPosition = true;
            try {
                let response;

                if (this.positionDialogMode === "create") {
                    response = await postData(
                        `${this.path}/${this.item.id}/chuc-vu`,
                        values,
                    );
                } else {
                    response = await putData(
                        `${this.path}/${this.item.id}/chuc-vu`,
                        this.selectedPosition.id,
                        values,
                    );
                }

                if (response) {
                    this.positionDialog = false;
                    await this.loadData();
                    this.$emit("reload");
                }
            } finally {
                this.savingPosition = false;
            }
        },
        async confirmDeletePosition() {
            if (!this.deletingPosition) return;

            this.deletingPositionLoading = true;
            try {
                await deleteData(
                    `${this.path}/${this.item.id}/chuc-vu`,
                    this.deletingPosition.id,
                );
                this.deleteDialog = false;
                this.deletingPosition = null;
                await this.loadData();
                this.$emit("reload");
            } finally {
                this.deletingPositionLoading = false;
            }
        },
        async savePermissions() {
            this.savingPermissions = true;
            try {
                const response = await putData(
                    `${this.path}/${this.item.id}/phan-quyen`,
                    null,
                    { phanQuyen: this.permissionsData },
                );

                if (response) {
                    this.permissionsData = response.phanQuyen ?? {};
                    this.$emit("reload");
                }
            } finally {
                this.savingPermissions = false;
            }
        },
    },
};
</script>
