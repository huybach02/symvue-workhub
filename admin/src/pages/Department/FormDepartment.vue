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

                            <v-card
                                variant="outlined"
                                class="position-relative overflow-hidden"
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
                                                    {{ permissions.length }}
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
                                                        permission, index
                                                    ) in permissions"
                                                    :key="permission.name"
                                                    :active="
                                                        index ===
                                                        activePermissionIndex
                                                    "
                                                    color="primary"
                                                    rounded="lg"
                                                    @click="
                                                        setActivePermissionIndex(
                                                            index,
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
                                        <template v-if="activePermission">
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
                                                                        activePermission.name,
                                                                    )
                                                                }}
                                                            </v-card-title>
                                                        </div>

                                                        <div
                                                            class="d-flex flex-wrap ga-3"
                                                        >
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
                                                                                $t(
                                                                                    "bo_phan.vai_tro.manager",
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
                                                                                    activePermissionIndex,
                                                                                    "manager",
                                                                                )
                                                                            }}
                                                                        </v-chip>
                                                                    </div>
                                                                    <v-checkbox
                                                                        :model-value="
                                                                            isAllChecked(
                                                                                activePermissionIndex,
                                                                                'manager',
                                                                            )
                                                                        "
                                                                        color="primary"
                                                                        hide-details
                                                                        density="compact"
                                                                        @update:model-value="
                                                                            toggleAll(
                                                                                activePermissionIndex,
                                                                                'manager',
                                                                                $event,
                                                                            )
                                                                        "
                                                                    />
                                                                </v-card-text>
                                                            </v-card>

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
                                                                                $t(
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
                                                                                    activePermissionIndex,
                                                                                    "employee",
                                                                                )
                                                                            }}
                                                                        </v-chip>
                                                                    </div>
                                                                    <v-checkbox
                                                                        :model-value="
                                                                            isAllChecked(
                                                                                activePermissionIndex,
                                                                                'employee',
                                                                            )
                                                                        "
                                                                        color="primary"
                                                                        hide-details
                                                                        density="compact"
                                                                        @update:model-value="
                                                                            toggleAll(
                                                                                activePermissionIndex,
                                                                                'employee',
                                                                                $event,
                                                                            )
                                                                        "
                                                                    />
                                                                </v-card-text>
                                                            </v-card>
                                                        </div>
                                                    </div>
                                                </v-card-item>

                                                <v-divider />

                                                <v-card-text class="pa-4">
                                                    <v-row>
                                                        <v-col
                                                            v-for="action in getPermissionActionList(
                                                                activePermission,
                                                            )"
                                                            :key="action.key"
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
                                                                                        $t(
                                                                                            "bo_phan.vai_tro.manager",
                                                                                        )
                                                                                    }}
                                                                                </span>
                                                                                <v-checkbox
                                                                                    v-model="
                                                                                        permissionStates[
                                                                                            activePermissionIndex
                                                                                        ]
                                                                                            .manager[
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

                                                                    <v-list-item>
                                                                        <template
                                                                            #title
                                                                        >
                                                                            <div
                                                                                class="permission-checkbox-row"
                                                                            >
                                                                                <span>
                                                                                    {{
                                                                                        $t(
                                                                                            "bo_phan.vai_tro.employee",
                                                                                        )
                                                                                    }}
                                                                                </span>
                                                                                <v-checkbox
                                                                                    v-model="
                                                                                        permissionStates[
                                                                                            activePermissionIndex
                                                                                        ]
                                                                                            .employee[
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
                                    </v-col>
                                </v-row>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-col>

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
            activePermissionIndex: 0,
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
            return constant.ACTIONS;
        },
        activePermission() {
            return this.permissions[this.activePermissionIndex] || null;
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
            const permissionsData = this.permissions.map(
                (permission, index) => ({
                    name: permission.name,
                    manager: this.permissionStates[index].manager,
                    employee: this.permissionStates[index].employee,
                }),
            );

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
                this.activePermissionIndex = 0;

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
                    (permission) => permission.name === phanQuyen.name,
                );

                if (moduleIndex !== -1) {
                    ["manager", "employee"].forEach((role) => {
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
        setActivePermissionIndex(index) {
            this.activePermissionIndex = index;
        },
        getPermissionActionList(permission) {
            if (!permission?.actions) {
                return [];
            }

            return this.availableActions.filter(
                (action) => permission.actions[action.key] !== undefined,
            );
        },
        formatModuleName(name) {
            return name
                .split("-")
                .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                .join(" ");
        },
        isAllChecked(index, role) {
            const state = this.permissionStates[index]?.[role];
            if (!state) return false;

            const permission = this.permissions[index];
            const availableKeys = Object.keys(permission.actions);

            return availableKeys.every((key) => state[key] === true);
        },
        countSelectedPermissions(index, role) {
            const state = this.permissionStates[index]?.[role];
            if (!state) return 0;

            return Object.values(state).filter((value) => value === true)
                .length;
        },
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
.permission-module-list {
    max-height: 520px;
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
</style>
