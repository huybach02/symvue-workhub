<template>
    <div>
        <v-row>
            <v-col cols="12" md="12" class="text-right">
                <v-btn
                    icon
                    size="small"
                    variant="outlined"
                    color="primary"
                    @click="dialog = true"
                >
                    <v-icon>mdi-office-building</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-dialog v-model="dialog" max-width="1400" scrollable persistent>
            <v-card
                :title="`${$t('bo_phan.text.departmentOfUser')} ${item.name}`"
                prepend-icon="mdi-office-building"
                class="position-relative card-wrap-title"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="close-btn"
                    @click="dialog = false"
                />

                <v-card-text>
                    <v-row align="end" class="mb-2">
                        <v-col cols="12" md="10">
                            <div class="mb-2">
                                {{ $t("field.bo_phan_moi") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-autocomplete
                                v-model="boPhanSelected"
                                name="boPhanId"
                                :items="danhSachBoPhan"
                                item-title="label"
                                item-value="value"
                                variant="outlined"
                                clearable
                                hide-details
                                :placeholder="`${$t('base.enter')} ${$t('field.bo_phan_moi')}`"
                            />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-btn
                                color="primary"
                                prepend-icon="mdi-plus"
                                size="large"
                                :loading="loading"
                                @click="addBoPhan"
                            >
                                {{ $t("button.create") }}
                            </v-btn>
                        </v-col>
                    </v-row>

                    <div class="d-flex ga-2 flex-wrap mb-3">
                        <v-chip
                            color="warning"
                            variant="tonal"
                            prepend-icon="mdi-account-group-outline"
                        >
                            {{ $t("bo_phan.text.isMemberOf") }}
                            <strong class="mx-1">{{ memberCount }}</strong>
                            {{ $t("bo_phan.text.department") }}
                        </v-chip>
                        <v-chip
                            color="primary"
                            variant="tonal"
                            prepend-icon="mdi-shield-account-outline"
                        >
                            {{ $t("bo_phan.text.isManagerOf") }}
                            <strong class="mx-1">{{ managerCount }}</strong>
                            {{ $t("bo_phan.text.department") }}
                        </v-chip>
                    </div>

                    <!-- Loading -->
                    <div v-if="loading" class="d-flex justify-center py-8">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="48"
                        />
                    </div>

                    <!-- KhÃƒÂ´ng cÃƒÂ³ dÃ¡Â»Â¯ liÃ¡Â»â€¡u -->
                    <v-alert
                        v-else-if="departments.length === 0 && boPhanLoaded"
                        type="warning"
                        variant="tonal"
                        class="mt-2"
                    >
                        {{ $t("bo_phan.text.notBelongsToAnyDepartment") }}
                    </v-alert>

                    <!-- Expansion panels cho tÃ¡Â»Â«ng bÃ¡Â»â„¢ phÃ¡ÂºÂ­n -->
                    <v-expansion-panels v-else class="mb-2">
                        <v-expansion-panel
                            v-for="(dept, deptIndex) in departments"
                            :key="dept.id"
                        >
                            <v-expansion-panel-title>
                                <div class="d-flex align-center ga-3">
                                    <v-chip
                                        :color="
                                            dept.is_manager
                                                ? 'primary'
                                                : 'warning'
                                        "
                                        size="small"
                                        label
                                    >
                                        {{
                                            dept.is_manager
                                                ? $t("bo_phan.vai_tro.manager")
                                                : $t("bo_phan.vai_tro.employee")
                                        }}
                                    </v-chip>
                                    <span class="font-weight-medium">
                                        {{ dept.ten_bo_phan }}
                                    </span>
                                    <v-chip
                                        v-if="dept.is_default"
                                        color="success"
                                        size="small"
                                        variant="tonal"
                                    >
                                        {{ $t("bo_phan.text.default") }}
                                    </v-chip>
                                </div>
                            </v-expansion-panel-title>

                            <v-expansion-panel-text>
                                <v-card
                                    variant="outlined"
                                    class="position-relative overflow-hidden"
                                >
                                    <v-row no-gutters>
                                        <v-col cols="12" md="4" lg="3">
                                            <v-card
                                                flat
                                                rounded="0"
                                                class="h-100 border-e"
                                            >
                                                <v-card-item>
                                                    <v-card-title>
                                                        Module
                                                    </v-card-title>
                                                    <v-card-subtitle>
                                                        {{
                                                            dept.phan_quyen
                                                                ?.length || 0
                                                        }}
                                                        module
                                                    </v-card-subtitle>
                                                </v-card-item>

                                                <v-divider />

                                                <v-list
                                                    class="permission-module-list"
                                                    nav
                                                    density="comfortable"
                                                >
                                                    <v-list-item
                                                        v-for="(
                                                            permission,
                                                            permIndex
                                                        ) in dept.phan_quyen"
                                                        :key="permission.name"
                                                        :active="
                                                            permIndex ===
                                                            activePermissionIndices[
                                                                deptIndex
                                                            ]
                                                        "
                                                        color="primary"
                                                        rounded="lg"
                                                        @click="
                                                            setActivePermissionIndex(
                                                                deptIndex,
                                                                permIndex,
                                                            )
                                                        "
                                                    >
                                                        <v-list-item-title>
                                                            {{
                                                                formatModuleName(
                                                                    permission.name,
                                                                )
                                                            }}
                                                        </v-list-item-title>

                                                        <template #append>
                                                            <v-chip
                                                                size="small"
                                                                variant="tonal"
                                                                color="primary"
                                                            >
                                                                {{
                                                                    getPermissionActionList(
                                                                        permission,
                                                                    ).length
                                                                }}
                                                            </v-chip>
                                                        </template>
                                                    </v-list-item>
                                                </v-list>
                                            </v-card>
                                        </v-col>

                                        <v-col cols="12" md="8" lg="9">
                                            <template
                                                v-if="
                                                    getActivePermission(
                                                        deptIndex,
                                                    )
                                                "
                                            >
                                                <v-card flat rounded="0">
                                                    <v-card-item class="pb-2">
                                                        <div
                                                            class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between ga-4"
                                                        >
                                                            <div>
                                                                <v-card-title
                                                                    class="px-0"
                                                                >
                                                                    {{
                                                                        formatModuleName(
                                                                            getActivePermission(
                                                                                deptIndex,
                                                                            )
                                                                                .name,
                                                                        )
                                                                    }}
                                                                </v-card-title>
                                                            </div>

                                                            <v-card
                                                                variant="tonal"
                                                                color="primary"
                                                            >
                                                                <v-card-text
                                                                    class="d-flex align-center justify-space-between ga-4 py-3"
                                                                >
                                                                    <div
                                                                        class="d-flex align-center ga-2"
                                                                    >
                                                                        <span
                                                                            class="font-weight-medium"
                                                                        >
                                                                            {{
                                                                                dept.is_manager
                                                                                    ? $t(
                                                                                          "bo_phan.vai_tro.manager",
                                                                                      )
                                                                                    : $t(
                                                                                          "bo_phan.vai_tro.employee",
                                                                                      )
                                                                            }}
                                                                        </span>
                                                                        <v-chip
                                                                            size="small"
                                                                            variant="flat"
                                                                            color="warning"
                                                                        >
                                                                            {{
                                                                                countSelectedPermissions(
                                                                                    deptIndex,
                                                                                    activePermissionIndices[
                                                                                        deptIndex
                                                                                    ],
                                                                                    getRoleKey(
                                                                                        dept,
                                                                                    ),
                                                                                )
                                                                            }}
                                                                        </v-chip>
                                                                    </div>
                                                                    <v-checkbox
                                                                        :model-value="
                                                                            isAllChecked(
                                                                                deptIndex,
                                                                                activePermissionIndices[
                                                                                    deptIndex
                                                                                ],
                                                                                getRoleKey(
                                                                                    dept,
                                                                                ),
                                                                            )
                                                                        "
                                                                        color="primary"
                                                                        hide-details
                                                                        density="compact"
                                                                        @update:model-value="
                                                                            toggleAll(
                                                                                deptIndex,
                                                                                activePermissionIndices[
                                                                                    deptIndex
                                                                                ],
                                                                                getRoleKey(
                                                                                    dept,
                                                                                ),
                                                                                $event,
                                                                            )
                                                                        "
                                                                    />
                                                                </v-card-text>
                                                            </v-card>
                                                        </div>
                                                    </v-card-item>

                                                    <v-divider />

                                                    <v-card-text class="pa-4">
                                                        <v-row>
                                                            <v-col
                                                                v-for="action in getPermissionActionList(
                                                                    getActivePermission(
                                                                        deptIndex,
                                                                    ),
                                                                )"
                                                                :key="
                                                                    action.key
                                                                "
                                                                cols="12"
                                                                sm="6"
                                                                xl="4"
                                                            >
                                                                <v-card
                                                                    variant="outlined"
                                                                    class="h-100"
                                                                >
                                                                    <v-card-item>
                                                                        <div
                                                                            class="d-flex align-center justify-space-between ga-3"
                                                                        >
                                                                            <div
                                                                                class="font-weight-medium"
                                                                            >
                                                                                {{
                                                                                    action.label
                                                                                }}
                                                                            </div>
                                                                            <v-chip
                                                                                size="x-small"
                                                                                variant="tonal"
                                                                            >
                                                                                {{
                                                                                    action.key
                                                                                }}
                                                                            </v-chip>
                                                                        </div>
                                                                    </v-card-item>

                                                                    <v-divider />

                                                                    <v-list
                                                                        density="compact"
                                                                    >
                                                                        <v-list-item>
                                                                            <template
                                                                                #title
                                                                            >
                                                                                <div
                                                                                    class="permission-checkbox-row"
                                                                                >
                                                                                    <span>
                                                                                        {{
                                                                                            dept.is_manager
                                                                                                ? $t(
                                                                                                      "bo_phan.vai_tro.manager",
                                                                                                  )
                                                                                                : $t(
                                                                                                      "bo_phan.vai_tro.employee",
                                                                                                  )
                                                                                        }}
                                                                                    </span>
                                                                                    <v-checkbox
                                                                                        v-if="
                                                                                            permissionStates[
                                                                                                deptIndex
                                                                                            ] &&
                                                                                            permissionStates[
                                                                                                deptIndex
                                                                                            ][
                                                                                                activePermissionIndices[
                                                                                                    deptIndex
                                                                                                ]
                                                                                            ] &&
                                                                                            permissionStates[
                                                                                                deptIndex
                                                                                            ][
                                                                                                activePermissionIndices[
                                                                                                    deptIndex
                                                                                                ]
                                                                                            ][
                                                                                                getRoleKey(
                                                                                                    dept,
                                                                                                )
                                                                                            ][
                                                                                                action
                                                                                                    .key
                                                                                            ] !==
                                                                                                undefined
                                                                                        "
                                                                                        v-model="
                                                                                            permissionStates[
                                                                                                deptIndex
                                                                                            ][
                                                                                                activePermissionIndices[
                                                                                                    deptIndex
                                                                                                ]
                                                                                            ][
                                                                                                getRoleKey(
                                                                                                    dept,
                                                                                                )
                                                                                            ][
                                                                                                action
                                                                                                    .key
                                                                                            ]
                                                                                        "
                                                                                        color="primary"
                                                                                        hide-details
                                                                                        density="compact"
                                                                                    />
                                                                                </div>
                                                                            </template>
                                                                        </v-list-item>
                                                                    </v-list>
                                                                </v-card>
                                                            </v-col>
                                                        </v-row>
                                                    </v-card-text>
                                                </v-card>
                                            </template>

                                            <v-empty-state
                                                v-else
                                                icon="mdi-shield-account"
                                                title="Chua co module phan quyen"
                                                text="Danh sach module phan quyen hien dang trong."
                                            />
                                        </v-col>
                                    </v-row>
                                </v-card>

                                <div
                                    class="d-flex flex-column flex-sm-row justify-sm-space-between ga-2 mt-3 mb-1"
                                >
                                    <div class="d-flex flex-wrap ga-2">
                                        <v-btn
                                            color="error"
                                            variant="elevated"
                                            :loading="savingIndex === deptIndex"
                                            @click="showConfirmDelete = true"
                                        >
                                            {{ $t("bo_phan.button.delete") }}
                                        </v-btn>
                                        <v-btn
                                            v-if="dept.is_custom"
                                            color="warning"
                                            variant="elevated"
                                            :loading="savingIndex === deptIndex"
                                            class="btn-wrap-text"
                                            @click="
                                                resetOrDelete(dept.id, 'reset')
                                            "
                                        >
                                            {{
                                                $t(
                                                    "bo_phan.button.resetPermissionDefault",
                                                )
                                            }}
                                        </v-btn>
                                    </div>
                                    <v-btn
                                        color="primary"
                                        variant="elevated"
                                        :loading="savingIndex === deptIndex"
                                        @click="savePermission(deptIndex)"
                                    >
                                        {{ $t("bo_phan.button.update") }}
                                    </v-btn>
                                    <ConfirmDialog
                                        v-model="showConfirmDelete"
                                        :message="
                                            $t(
                                                'media_library.delete_confirm_message',
                                                {
                                                    count: 1,
                                                },
                                            )
                                        "
                                        :loading="savingIndex === deptIndex"
                                        @confirm="
                                            resetOrDelete(dept.id, 'delete')
                                        "
                                        @cancel="showConfirmDelete = false"
                                    />
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { toast } from "@/main";
import { getDataById, getDataSelect } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";
import { patchData } from "@/services/bases/updateData";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

export default {
    components: {
        ConfirmDialog,
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
            loading: false,
            departments: [],
            permissionStates: [],
            activePermissionIndices: [],
            savingIndex: null,
            boPhanData: [],
            boPhanSelected: null,
            showConfirmDelete: false,
            boPhanLoaded: false,
        };
    },
    computed: {
        availableActions() {
            return [
                { key: "index", label: this.$t("bo_phan.actions.index") },
                { key: "create", label: this.$t("bo_phan.actions.create") },
                { key: "show", label: this.$t("bo_phan.actions.show") },
                { key: "edit", label: this.$t("bo_phan.actions.edit") },
                { key: "delete", label: this.$t("bo_phan.actions.delete") },
                { key: "export", label: this.$t("bo_phan.actions.export") },
                { key: "import", label: this.$t("bo_phan.actions.import") },
                {
                    key: "showMenu",
                    label: this.$t("bo_phan.actions.showMenu"),
                },
            ];
        },
        memberCount() {
            return this.departments.filter((d) => !d.is_manager).length;
        },
        managerCount() {
            return this.departments.filter((d) => d.is_manager).length;
        },
        danhSachBoPhan() {
            return this.boPhanData.filter((item) => {
                return !this.departments.some(
                    (dept) => dept.bo_phan_id === item.value,
                );
            });
        },
    },
    watch: {
        async dialog(isOpen) {
            if (isOpen) {
                if (!this.boPhanLoaded) {
                    await this.getBoPhan();
                    this.boPhanLoaded = true;
                }
                await this.loadDepartments();
            } else {
                this.departments = [];
                this.permissionStates = [];
                this.activePermissionIndices = [];
                this.savingIndex = null;
            }
        },
    },
    methods: {
        async loadDepartments() {
            this.loading = true;
            try {
                const res = await getDataById(
                    this.path,
                    this.item.id,
                    "department",
                );
                this.departments = res ?? [];
                this.buildPermissionStates();
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },
        buildPermissionStates() {
            this.activePermissionIndices = this.departments.map(() => 0);
            this.permissionStates = this.departments.map((dept) => {
                const phanQuyen = dept.phan_quyen ?? [];
                const isManager = dept.is_manager ?? false;

                return phanQuyen.map((permission) => {
                    const actions = permission.actions ?? {};
                    const filledState = {};
                    const emptyState = {};

                    Object.keys(actions).forEach((key) => {
                        filledState[key] = actions[key] ?? false;
                        emptyState[key] = false;
                    });

                    return {
                        manager: isManager ? filledState : emptyState,
                        employee: !isManager ? filledState : emptyState,
                    };
                });
            });
        },
        getRoleKey(dept) {
            return dept.is_manager ? "manager" : "employee";
        },
        getActivePermission(deptIndex) {
            const permIndex = this.activePermissionIndices[deptIndex] ?? 0;
            return (
                this.departments?.[deptIndex]?.phan_quyen?.[permIndex] || null
            );
        },
        getPermissionActionList(permission) {
            if (!permission?.actions) {
                return [];
            }

            return this.availableActions.filter(
                (action) => permission.actions[action.key] !== undefined,
            );
        },
        setActivePermissionIndex(deptIndex, permIndex) {
            this.activePermissionIndices[deptIndex] = permIndex;
        },
        isAllChecked(deptIndex, permIndex, role) {
            const state =
                this.permissionStates?.[deptIndex]?.[permIndex]?.[role];
            if (!state) return false;
            return Object.values(state).every((value) => value === true);
        },
        countSelectedPermissions(deptIndex, permIndex, role) {
            const state =
                this.permissionStates?.[deptIndex]?.[permIndex]?.[role];
            if (!state) return 0;
            return Object.values(state).filter((value) => value === true)
                .length;
        },
        toggleAll(deptIndex, permIndex, role, value) {
            const state =
                this.permissionStates?.[deptIndex]?.[permIndex]?.[role];
            if (!state) return;
            Object.keys(state).forEach((key) => {
                state[key] = value;
            });
        },
        async savePermission(deptIndex) {
            const dept = this.departments[deptIndex];
            const states = this.permissionStates[deptIndex];
            const role = this.getRoleKey(dept);

            const phanQuyen = (dept.phan_quyen ?? []).map(
                (permission, permIndex) => {
                    const actions = states?.[permIndex]?.[role] ?? {};
                    return { name: permission.name, actions };
                },
            );

            this.savingIndex = deptIndex;
            const res = await patchData(
                `${this.path}/${this.item.id}/department`,
                dept.id,
                { phan_quyen: phanQuyen },
                () => {
                    this.departments[deptIndex].phan_quyen = phanQuyen;
                },
            );
            if (res) {
                this.loadDepartments();
            }
            this.savingIndex = null;
        },
        formatModuleName(name) {
            return name
                .split("-")
                .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                .join(" ");
        },
        async getBoPhan() {
            const res = await getDataSelect(API_ROUTES_CONFIG.boPhan);
            this.boPhanData = res;
        },
        async addBoPhan() {
            if (!this.boPhanSelected) {
                toast.error("Vui long chon bo phan");
                return;
            }

            this.loading = true;
            const res = await postData(
                `${this.path}/${this.item.id}/department`,
                { bo_phan_id: this.boPhanSelected },
            );

            if (res) {
                this.boPhanSelected = null;
                this.loadDepartments();
            }
            this.loading = false;
        },
        async resetOrDelete(deptId, action) {
            this.loading = true;
            const res = await postData(
                `${this.path}/${this.item.id}/department/${deptId}`,
                { action },
            );
            if (res) {
                this.loadDepartments();
            }
            this.loading = false;
        },
    },
};
</script>
<style scoped>
.permission-module-list {
    max-height: 420px;
    overflow: auto;
}

.permission-checkbox-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    width: 100%;
}

.permission-checkbox-row :deep(.v-selection-control) {
    min-height: auto;
}

.permission-checkbox-row :deep(.v-selection-control__wrapper) {
    margin-inline-start: 0;
}

.close-btn {
    position: absolute;
    top: 12px;
    right: 12px;
}

.card-wrap-title :deep(.v-card-title) {
    white-space: normal;
    word-break: break-word;
    line-height: 1.4;
}

.btn-wrap-text {
    height: auto !important;
    min-height: 36px;
}

.btn-wrap-text :deep(.v-btn__content) {
    white-space: normal;
    word-break: break-word;
    text-align: center;
    padding: 6px 0;
}
</style>
