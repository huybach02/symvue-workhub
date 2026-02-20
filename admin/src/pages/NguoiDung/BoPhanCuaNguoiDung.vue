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
                    <v-icon>mdi-account-multiple-outline</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-dialog v-model="dialog" max-width="1400" scrollable persistent>
            <v-card
                :title="`${$t('bo_phan.text.departmentOfUser')} ${item.name}`"
                prepend-icon="mdi-account-multiple-outline"
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
                    <!-- Select và button submit Cho phép assign người dùng vào 1 bộ phận với vai trò employee -->
                    <v-row align="end" class="mb-2">
                        <v-col cols="12" md="10">
                            <div class="mb-2">
                                {{ $t("field.bo_phan_moi") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-autocomplete
                                v-model="boPhanSelected"
                                name="boPhanId"
                                :items="boPhanData"
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

                    <!-- Không có dữ liệu -->
                    <v-alert
                        v-else-if="departments.length === 0"
                        type="warning"
                        variant="tonal"
                        class="mt-2"
                    >
                        {{ $t("bo_phan.text.notBelongsToAnyDepartment") }}
                    </v-alert>

                    <!-- Expansion panels cho từng bộ phận -->
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
                                <!-- Bảng phân quyền -->
                                <div
                                    class="permission-table-wrapper"
                                    style="position: relative"
                                >
                                    <v-table
                                        class="permission-table"
                                        fixed-header
                                        height="400px"
                                    >
                                        <thead>
                                            <tr>
                                                <th class="text-left">
                                                    Module
                                                </th>
                                                <th class="text-left">
                                                    {{
                                                        $t(
                                                            "bo_phan.vai_tro.title",
                                                        )
                                                    }}
                                                </th>
                                                <th
                                                    v-for="action in availableActions"
                                                    :key="action.key"
                                                    class="text-center"
                                                >
                                                    {{ action.label }}
                                                </th>
                                                <th class="text-center">
                                                    {{
                                                        $t(
                                                            "bo_phan.actions.all",
                                                        )
                                                    }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template
                                                v-for="(
                                                    permission, permIndex
                                                ) in dept.phan_quyen"
                                                :key="permission.name"
                                            >
                                                <tr>
                                                    <td
                                                        class="module-name-cell"
                                                    >
                                                        {{
                                                            formatModuleName(
                                                                permission.name,
                                                            )
                                                        }}
                                                    </td>
                                                    <td>
                                                        {{
                                                            dept.is_manager
                                                                ? $t(
                                                                      "bo_phan.vai_tro.manager",
                                                                  )
                                                                : $t(
                                                                      "bo_phan.vai_tro.employee",
                                                                  )
                                                        }}
                                                    </td>
                                                    <td
                                                        v-for="action in availableActions"
                                                        :key="action.key"
                                                        class="text-center"
                                                    >
                                                        <v-checkbox
                                                            v-if="
                                                                permissionStates[
                                                                    deptIndex
                                                                ] &&
                                                                permissionStates[
                                                                    deptIndex
                                                                ][permIndex] &&
                                                                permissionStates[
                                                                    deptIndex
                                                                ][permIndex][
                                                                    dept.is_manager
                                                                        ? 'manager'
                                                                        : 'employee'
                                                                ][
                                                                    action.key
                                                                ] !== undefined
                                                            "
                                                            v-model="
                                                                permissionStates[
                                                                    deptIndex
                                                                ][permIndex][
                                                                    dept.is_manager
                                                                        ? 'manager'
                                                                        : 'employee'
                                                                ][action.key]
                                                            "
                                                            color="primary"
                                                            hide-details
                                                            density="compact"
                                                            class="d-inline-flex justify-center"
                                                        />
                                                    </td>
                                                    <td class="text-center">
                                                        <v-checkbox
                                                            :model-value="
                                                                isAllChecked(
                                                                    deptIndex,
                                                                    permIndex,
                                                                    dept.is_manager
                                                                        ? 'manager'
                                                                        : 'employee',
                                                                )
                                                            "
                                                            color="primary"
                                                            hide-details
                                                            density="compact"
                                                            class="d-inline-flex justify-center"
                                                            @update:model-value="
                                                                toggleAll(
                                                                    deptIndex,
                                                                    permIndex,
                                                                    dept.is_manager
                                                                        ? 'manager'
                                                                        : 'employee',
                                                                    $event,
                                                                )
                                                            "
                                                        />
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </v-table>
                                </div>

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
            savingIndex: null,
            boPhanData: [],
            boPhanSelected: null,
            showConfirmDelete: false,
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
    },
    watch: {
        async dialog(isOpen) {
            if (isOpen) {
                await this.loadDepartments();
            } else {
                this.departments = [];
                this.permissionStates = [];
                this.savingIndex = null;
            }
        },
    },
    created() {
        this.getBoPhan();
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
        isAllChecked(deptIndex, permIndex, role) {
            const state =
                this.permissionStates?.[deptIndex]?.[permIndex]?.[role];
            if (!state) return false;
            return Object.values(state).every((v) => v === true);
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
            const isManager = dept.is_manager ?? false;
            const role = isManager ? "manager" : "employee";

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
                toast.error("Vui lòng chọn bộ phận");
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
.permission-table-wrapper {
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.permission-table {
    border: none !important;
}

.permission-table :deep(thead) {
    position: sticky;
    top: 0;
    z-index: 10;
}

.permission-table :deep(th) {
    background-color: #f5f5f5 !important;
    font-weight: 600;
    padding: 12px 8px;
    border-bottom: 2px solid #e0e0e0 !important;
}

.permission-table :deep(td) {
    padding: 8px;
}

.permission-table :deep(tbody tr:hover) {
    background-color: #fafafa;
}

.module-name-cell {
    vertical-align: middle;
    font-weight: 500;
    border-right: 1px solid #e0e0e0;
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
