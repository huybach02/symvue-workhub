<template>
    <div>
        <VeeForm
            v-if="mode === 'create' || (mode === 'update' && item)"
            ref="formRef"
            as="form"
            :validation-schema="validationSchema"
            :initial-values="initialValues"
            @submit="handleSubmit"
        >
            <v-row>
                <v-col cols="12">
                    <v-row>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="quanLyBoPhanId"
                            >
                                <div class="mb-2">
                                    {{ $t("field.quan_ly_bo_phan") }}
                                    <span class="text-red">* </span>
                                </div>
                                <v-autocomplete
                                    :key="selectedQuanLyBoPhan"
                                    :model-value="field.value"
                                    :items="nguoiDungOptions"
                                    item-title="label"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.quan_ly_bo_phan')}`"
                                    clearable
                                    @update:model-value="handleChange"
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage, handleChange }"
                                name="tenBoPhan"
                            >
                                <div class="mb-2">
                                    {{ $t("field.ten_bo_phan") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.ten_bo_phan')}`"
                                    @input="
                                        onTenBoPhanChange($event, handleChange)
                                    "
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="maBoPhan"
                            >
                                <div class="mb-2">
                                    {{ $t("field.ma_bo_phan") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    readonly
                                    :placeholder="`${$t('base.enter')} ${$t('field.ma_bo_phan')}`"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="status"
                            >
                                <div class="mb-2">
                                    {{ $t("field.trang_thai") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-select
                                    :model-value="field.value"
                                    :items="statusOptions"
                                    item-title="text"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.trang_thai')}`"
                                    @update:model-value="handleChange"
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12">
                            <div class="mb-2">
                                {{ $t("field.phan_quyen") }}
                                <span class="text-red"> * </span>
                            </div>

                            <!-- Bảng phân quyền -->
                            <div
                                class="permission-table-wrapper"
                                style="position: relative"
                            >
                                <v-overlay
                                    :model-value="permissionLoading"
                                    contained
                                    scrim="rgba(255, 255, 255, 0.6)"
                                    class="align-center justify-center"
                                >
                                    <v-progress-circular
                                        indeterminate
                                        color="primary"
                                        size="48"
                                    />
                                </v-overlay>
                                <v-table
                                    class="permission-table"
                                    fixed-header
                                    height="400px"
                                >
                                    <thead>
                                        <tr>
                                            <th class="text-left">Module</th>
                                            <th class="text-left">
                                                {{
                                                    $t("bo_phan.vai_tro.title")
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
                                                {{ $t("bo_phan.actions.all") }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template
                                            v-for="(
                                                permission, index
                                            ) in permissions"
                                            :key="permission.name"
                                        >
                                            <!-- Row Quản lý -->
                                            <tr>
                                                <td
                                                    :rowspan="2"
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
                                                        $t(
                                                            "bo_phan.vai_tro.manager",
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
                                                            permission.actions[
                                                                action.key
                                                            ] !== undefined
                                                        "
                                                        v-model="
                                                            permissionStates[
                                                                index
                                                            ].manager[
                                                                action.key
                                                            ]
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
                                                                index,
                                                                'manager',
                                                            )
                                                        "
                                                        color="primary"
                                                        hide-details
                                                        density="compact"
                                                        class="d-inline-flex justify-center"
                                                        @update:model-value="
                                                            toggleAll(
                                                                index,
                                                                'manager',
                                                                $event,
                                                            )
                                                        "
                                                    />
                                                </td>
                                            </tr>
                                            <!-- Row Nhân viên -->
                                            <tr>
                                                <td>
                                                    {{
                                                        $t(
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
                                                            permission.actions[
                                                                action.key
                                                            ] !== undefined
                                                        "
                                                        v-model="
                                                            permissionStates[
                                                                index
                                                            ].employee[
                                                                action.key
                                                            ]
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
                                                                index,
                                                                'employee',
                                                            )
                                                        "
                                                        color="primary"
                                                        hide-details
                                                        density="compact"
                                                        class="d-inline-flex justify-center"
                                                        @update:model-value="
                                                            toggleAll(
                                                                index,
                                                                'employee',
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
                        </v-col>
                    </v-row>
                </v-col>

                <!-- Nút cancel và create/update -->
                <v-col cols="12">
                    <div class="d-flex justify-end ga-2">
                        <v-btn color="grey" @click="handleCancel">
                            {{ $t("button.cancel") }}
                        </v-btn>
                        <v-btn
                            color="primary"
                            type="submit"
                            :loading="$store.state.isLoading"
                        >
                            {{ submitButtonText }}
                        </v-btn>
                    </div>
                </v-col>
            </v-row>
        </VeeForm>
        <LoadingForm v-if="mode === 'update' && !item" :is-loading="true" />
    </div>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { constant } from "@/utils/constants/constant";
import LoadingForm from "@/components/LoadingForm.vue";
import {
    getDataSelect,
    getListPhanQuyenMacDinh,
} from "@/services/bases/getData";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { functionHelper } from "@/helpers/functionHelper";
import { boPhanSchema } from "@/utils/schemas/boPhan";

export default {
    components: {
        LoadingForm,
        VeeForm,
        VeeField,
    },
    props: {
        submitButtonText: {
            type: String,
            default: "",
        },
        item: {
            type: Object,
            default: null,
        },
        mode: {
            type: String,
            default: "create",
        },
    },
    emits: ["submit", "cancel"],
    data() {
        return {
            validationSchema: boPhanSchema,
            initialValues: {
                quanLyBoPhanId: "",
                tenBoPhan: "",
                maBoPhan: "",
                status: 1,
            },
            nguoiDungOptions: [],
            permissions: [],
            permissionStates: [],
            permissionLoading: false,
            selectedQuanLyBoPhan: null,
        };
    },
    computed: {
        statusOptions() {
            return constant.STATUS.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
        availableActions() {
            return [
                { key: "index", label: this.$t("bo_phan.actions.index") },
                { key: "create", label: this.$t("bo_phan.actions.create") },
                { key: "show", label: this.$t("bo_phan.actions.show") },
                { key: "edit", label: this.$t("bo_phan.actions.edit") },
                { key: "delete", label: this.$t("bo_phan.actions.delete") },
                { key: "export", label: this.$t("bo_phan.actions.export") },
                { key: "import", label: this.$t("bo_phan.actions.import") },
                { key: "showMenu", label: this.$t("bo_phan.actions.showMenu") },
            ];
        },
    },
    watch: {
        item: {
            handler(value) {
                if (value && this.permissions.length > 0) {
                    this.selectedQuanLyBoPhan = value.quanLyBoPhan;
                    this.$nextTick(() => {
                        if (this.$refs.formRef) {
                            this.$refs.formRef.setValues(value);
                        }
                        // Fill permission data vào checkboxes
                        if (value.phanQuyen && value.phanQuyen.length > 0) {
                            this.fillPermissionStates(value.phanQuyen);
                        }
                    });
                }
            },
            deep: true,
            immediate: true,
        },
    },
    created() {
        this.getPermission();
        this.getUser();
    },
    methods: {
        onTenBoPhanChange(event, handleChange) {
            const tenBoPhan = event.target.value;
            handleChange(tenBoPhan);

            if (tenBoPhan && this.$refs.formRef) {
                const maBoPhan = functionHelper.generateMa(tenBoPhan);
                this.$refs.formRef.setFieldValue("maBoPhan", maBoPhan);
            }
        },
        handleSubmit(values) {
            // Map permissionStates vào format cần thiết cho API
            const permissionsData = this.permissions.map(
                (permission, index) => ({
                    name: permission.name,
                    manager: this.permissionStates[index].manager,
                    employee: this.permissionStates[index].employee,
                }),
            );

            // Thêm permissions vào values
            const submitData = {
                ...values,
                permissions: permissionsData,
            };

            this.$emit("submit", submitData);
        },
        handleCancel() {
            this.$emit("cancel");
        },
        async getUser() {
            try {
                const response = await getDataSelect(API_ROUTES_CONFIG.user);
                this.nguoiDungOptions = response;
            } catch (error) {
                console.error(error);
            }
        },
        async getPermission() {
            this.permissionLoading = true;
            try {
                const response = await getListPhanQuyenMacDinh();
                this.permissions = response;

                this.initializePermissionStates();

                if (this.item) {
                    this.selectedQuanLyBoPhan = this.item.quanLyBoPhan;
                    this.$nextTick(() => {
                        if (this.$refs.formRef) {
                            this.$refs.formRef.setValues(this.item);
                        }
                        if (
                            this.item.phanQuyen &&
                            this.item.phanQuyen.length > 0
                        ) {
                            this.fillPermissionStates(this.item.phanQuyen);
                        }
                    });
                }
            } catch (error) {
                console.error(error);
            } finally {
                this.permissionLoading = false;
            }
        },
        initializePermissionStates() {
            this.permissionStates = this.permissions.map((permission) => {
                const managerState = {};
                const employeeState = {};
                Object.keys(permission.actions).forEach((action) => {
                    const defaultVal =
                        this.mode === "create"
                            ? permission.actions[action]
                            : false;
                    managerState[action] = defaultVal;
                    employeeState[action] = defaultVal;
                });
                return { manager: managerState, employee: employeeState };
            });
        },
        fillPermissionStates(phanQuyenData) {
            phanQuyenData.forEach((phanQuyen) => {
                const moduleIndex = this.permissions.findIndex(
                    (p) => p.name === phanQuyen.name,
                );

                if (moduleIndex !== -1) {
                    const roles = ["manager", "employee"];
                    roles.forEach((role) => {
                        if (phanQuyen[role]) {
                            Object.keys(phanQuyen[role]).forEach(
                                (actionKey) => {
                                    if (
                                        this.permissionStates[moduleIndex][
                                            role
                                        ][actionKey] !== undefined
                                    ) {
                                        this.permissionStates[moduleIndex][
                                            role
                                        ][actionKey] =
                                            phanQuyen[role][actionKey];
                                    }
                                },
                            );
                        }
                    });
                }
            });
        },
        // Format tên module từ dạng "cau-hinh-chung" thành "Cau Hinh Chung"
        formatModuleName(name) {
            return name
                .split("-")
                .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                .join(" ");
        },
        // Kiểm tra xem tất cả các switch trong row có được check không
        isAllChecked(index, role) {
            const state = this.permissionStates[index]?.[role];
            if (!state) return false;

            const permission = this.permissions[index];
            const availableKeys = Object.keys(permission.actions);

            return availableKeys.every((key) => state[key] === true);
        },
        // Toggle tất cả các switch trong row
        toggleAll(index, role, value) {
            const permission = this.permissions[index];
            const availableKeys = Object.keys(permission.actions);

            availableKeys.forEach((key) => {
                this.permissionStates[index][role][key] = value;
            });
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
</style>
