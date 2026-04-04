<template>
    <div>
        <div v-if="loading" class="d-flex justify-center py-8">
            <v-progress-circular indeterminate color="primary" size="48" />
        </div>

        <template v-else>
            <div v-if="hasPrimaryPosition" class="d-flex justify-end mb-4">
                <v-btn color="primary" variant="flat" @click="dialog = true">
                    {{ $t("position.add_temp_position") }}
                </v-btn>
            </div>

            <v-alert v-else type="warning" variant="tonal" class="mb-4">
                {{ $t("position.please_update_position") }}
            </v-alert>

            <div v-if="!groupedPositions.length" class="pa-8 text-center">
                <v-icon
                    icon="mdi-database-off-outline"
                    size="large"
                    color="grey-lighten-1"
                />
                <div class="text-grey-darken-1 mt-2">
                    {{ $t("base.no_data") }}
                </div>
            </div>

            <v-expansion-panels v-else v-model="model">
                <v-expansion-panel
                    v-for="departmentGroup in groupedPositions"
                    :key="departmentGroup.departmentId"
                    :value="departmentGroup.departmentId"
                    rounded="lg"
                    class="mb-3 border"
                >
                    <v-expansion-panel-title>
                        <div
                            class="d-flex align-center justify-space-between w-100 ga-3"
                        >
                            <div class="d-flex align-center flex-wrap ga-2">
                                <span class="font-weight-medium">
                                    {{ $t("bo_phan.title") }}:
                                    {{ departmentGroup.departmentName }}
                                </span>

                                <v-chip
                                    v-if="departmentGroup.hasPrimary"
                                    size="small"
                                    color="success"
                                    variant="tonal"
                                >
                                    {{ $t("position.primary") }}
                                    <v-icon>mdi-check</v-icon>
                                </v-chip>
                            </div>

                            <v-chip size="small" variant="outlined">
                                {{ departmentGroup.positions.length }}
                            </v-chip>
                        </div>
                    </v-expansion-panel-title>

                    <v-expansion-panel-text>
                        <v-list class="py-0">
                            <div
                                v-for="(
                                    positionItem, index
                                ) in departmentGroup.positions"
                                :key="positionItem.id"
                            >
                                <v-list-item class="px-0 py-2">
                                    <v-row align="start" class="ma-0 ga-3">
                                        <v-col cols="12" sm class="pa-0">
                                            <div
                                                class="d-flex align-start ga-3"
                                            >
                                                <v-icon
                                                    color="primary"
                                                    class="mt-1"
                                                >
                                                    mdi-briefcase-account-outline
                                                </v-icon>

                                                <div class="w-100">
                                                    <div
                                                        class="d-flex align-center flex-wrap ga-2"
                                                    >
                                                        <span
                                                            class="font-weight-medium"
                                                        >
                                                            {{
                                                                $t(
                                                                    "position.text",
                                                                )
                                                            }}:
                                                            {{
                                                                positionItem
                                                                    .position
                                                                    ?.name
                                                            }}
                                                        </span>

                                                        <v-chip
                                                            v-if="
                                                                positionItem.isPrimary
                                                            "
                                                            size="small"
                                                            color="success"
                                                            variant="tonal"
                                                        >
                                                            <v-icon>
                                                                mdi-check
                                                            </v-icon>
                                                            {{
                                                                $t(
                                                                    "position.primary",
                                                                )
                                                            }}
                                                        </v-chip>
                                                        <v-chip
                                                            v-else
                                                            size="small"
                                                            color="warning"
                                                            variant="tonal"
                                                        >
                                                            <v-icon>
                                                                mdi-clock-outline
                                                            </v-icon>
                                                            {{
                                                                $t(
                                                                    "position.temporary",
                                                                )
                                                            }}
                                                        </v-chip>
                                                    </div>

                                                    <div
                                                        class="d-flex flex-wrap ga-2 mt-2"
                                                    >
                                                        <v-chip
                                                            v-if="
                                                                positionItem?.startTemp
                                                            "
                                                            size="small"
                                                            color="info"
                                                            variant="tonal"
                                                            prepend-icon="mdi-clock-start"
                                                        >
                                                            <span>
                                                                {{
                                                                    positionItem.startTemp
                                                                }}
                                                            </span>
                                                        </v-chip>

                                                        <v-chip
                                                            v-if="
                                                                positionItem?.endTemp
                                                            "
                                                            size="small"
                                                            color="error"
                                                            variant="tonal"
                                                            prepend-icon="mdi-clock-end"
                                                        >
                                                            <span>
                                                                {{
                                                                    positionItem.endTemp
                                                                }}
                                                            </span>
                                                        </v-chip>
                                                    </div>
                                                </div>
                                            </div>
                                        </v-col>

                                        <v-col
                                            cols="12"
                                            sm="auto"
                                            class="pa-0 d-flex flex-column ga-2"
                                        >
                                            <v-btn
                                                block
                                                size="small"
                                                variant="tonal"
                                                color="primary"
                                                @click.stop="
                                                    openPermissionDialog(
                                                        positionItem,
                                                    )
                                                "
                                            >
                                                {{
                                                    $t(
                                                        "position.view_permission",
                                                    )
                                                }}
                                            </v-btn>
                                            <v-btn
                                                v-if="!positionItem.isPrimary"
                                                block
                                                size="small"
                                                variant="tonal"
                                                color="error"
                                                @click.stop="
                                                    handleDeleteTemporaryPosition(
                                                        positionItem,
                                                    )
                                                "
                                            >
                                                {{
                                                    $t(
                                                        "position.delete_temporary_position",
                                                    )
                                                }}
                                            </v-btn>
                                        </v-col>
                                    </v-row>
                                </v-list-item>

                                <v-divider
                                    v-if="
                                        index <
                                        departmentGroup.positions.length - 1
                                    "
                                    class="my-5"
                                />
                            </div>
                        </v-list>
                    </v-expansion-panel-text>
                </v-expansion-panel>
            </v-expansion-panels>
        </template>

        <v-dialog
            v-model="permissionDialog"
            max-width="1200"
            scrollable
            persistent
        >
            <v-card class="position-relative">
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="position-absolute"
                    style="top: 8px; right: 8px"
                    @click="permissionDialog = false"
                />

                <v-card-title class="pr-12">
                    {{
                        selectedPermissionPosition
                            ? $t("position.permission_of_position", {
                                  name:
                                      selectedPermissionPosition.position
                                          ?.name || "",
                              })
                            : $t("position.view_permission")
                    }}
                </v-card-title>

                <v-card-text>
                    <DepartmentPermissionEditor
                        v-if="selectedPermissionPosition"
                        :model-value="selectedPermissionData"
                        :positions="selectedPermissionPositions"
                        readonly
                    />
                </v-card-text>
            </v-card>
        </v-dialog>

        <UserTemporaryPositionDialog
            v-model="dialog"
            :loading="submittingTemporaryPosition"
            @submit="submitTemporaryPosition"
        />

        <ConfirmDialog
            v-model="showConfirmDelete"
            :message="
                $t('media_library.delete_confirm_message', {
                    count: 1,
                })
            "
            :loading="isDeleting"
            @confirm="deleteTemporaryPosition"
            @cancel="showConfirmDelete = false"
        />
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import DepartmentPermissionEditor from "@/pages/Department/components/DepartmentPermissionEditor.vue";
import { postData } from "@/services/bases/postData";
import UserTemporaryPositionDialog from "./UserTemporaryPositionDialog.vue";
import { deleteData } from "@/services/bases/deleteData";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

export default {
    components: {
        DepartmentPermissionEditor,
        UserTemporaryPositionDialog,
        ConfirmDialog,
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
        loading: {
            type: Boolean,
            default: false,
        },
        groupedPositions: {
            type: Array,
            default: () => [],
        },
        expandedDepartments: {
            type: Array,
            default: () => [],
        },
    },
    emits: ["update:expandedDepartments", "reload"],
    data() {
        return {
            dialog: false,
            permissionDialog: false,
            selectedPermissionPosition: null,
            submittingTemporaryPosition: false,
            showConfirmDelete: false,
            isDeleting: false,
            selectedPositionItem: null,
        };
    },
    computed: {
        model: {
            get() {
                return this.expandedDepartments;
            },
            set(value) {
                this.$emit("update:expandedDepartments", value);
            },
        },
        selectedPermissionPositions() {
            const position = this.selectedPermissionPosition?.position;

            return position ? [position] : [];
        },
        selectedPermissionData() {
            const selectedPosition = this.selectedPermissionPosition;
            const positionCode = selectedPosition?.position?.code;
            const departmentPermissions =
                selectedPosition?.department?.phanQuyen || {};

            if (!positionCode) {
                return {};
            }

            return {
                [positionCode]: departmentPermissions[positionCode] ?? [],
            };
        },
        hasPrimaryPosition() {
            return this.groupedPositions.some((departmentGroup) =>
                departmentGroup.positions.some(
                    (positionItem) => !!positionItem.isPrimary,
                ),
            );
        },
    },
    methods: {
        async submitTemporaryPosition(value) {
            if (!this.item?.id) {
                return;
            }

            this.submittingTemporaryPosition = true;

            try {
                const response = await postData(
                    `${this.path || API_ROUTES_CONFIG.user}/${this.item.id}/vi-tri-cong-viec/temp`,
                    {
                        ...value,
                    },
                );

                if (response) {
                    this.dialog = false;
                    this.$emit("reload");
                }
            } finally {
                this.submittingTemporaryPosition = false;
            }
        },
        openPermissionDialog(positionItem) {
            this.selectedPermissionPosition = positionItem;
            this.permissionDialog = true;
        },
        handleDeleteTemporaryPosition(positionItem) {
            this.showConfirmDelete = true;
            this.selectedPositionItem = positionItem;
        },
        async deleteTemporaryPosition() {
            if (!this.selectedPositionItem?.id) {
                return;
            }
            this.isDeleting = true;
            await deleteData(
                `${this.path || API_ROUTES_CONFIG.user}/vi-tri-cong-viec/temp`,
                this.selectedPositionItem.id,
            );
            this.isDeleting = false;
            this.$emit("reload");
            this.showConfirmDelete = false;
        },
    },
};
</script>
