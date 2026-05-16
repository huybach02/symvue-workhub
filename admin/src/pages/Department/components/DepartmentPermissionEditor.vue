<template>
    <v-card variant="outlined" class="position-relative overflow-hidden">
        <v-overlay
            :model-value="permissionLoading"
            absolute
            contained
            scrim="rgba(255, 255, 255, 0.6)"
            class="align-center justify-center"
        >
            <v-progress-circular indeterminate color="primary" size="48" />
        </v-overlay>

        <v-row no-gutters>
            <v-col cols="12" md="4" lg="3">
                <v-card flat rounded="0" class="h-100 border-e">
                    <v-card-item>
                        <v-card-title>Module</v-card-title>
                        <v-card-subtitle>
                            {{ permissions.length }} module
                        </v-card-subtitle>
                    </v-card-item>

                    <v-divider />

                    <v-list
                        class="permission-module-list"
                        nav
                        density="comfortable"
                    >
                        <v-list-item
                            v-for="(permission, index) in permissions"
                            :key="permission.name"
                            :active="index === activePermissionIndex"
                            color="primary"
                            rounded="lg"
                            @click="setActivePermissionIndex(index)"
                        >
                            <v-list-item-title>
                                {{ formatModuleName(permission.name) }}
                            </v-list-item-title>

                            <template #append>
                                <v-chip
                                    size="small"
                                    variant="tonal"
                                    color="primary"
                                >
                                    {{
                                        getPermissionActionList(permission)
                                            .length
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
                                    <v-card-title class="px-0">
                                        {{
                                            formatModuleName(
                                                activePermission.name,
                                            )
                                        }}
                                    </v-card-title>
                                </div>

                                <div
                                    v-if="showPositionList"
                                    class="d-flex flex-wrap ga-3"
                                >
                                    <v-card
                                        v-for="position in renderPositions"
                                        :key="
                                            getPositionPermissionKey(position)
                                        "
                                        variant="tonal"
                                        color="primary"
                                        min-width="220"
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
                                                    {{ position.name }}
                                                </span>
                                                <v-chip
                                                    size="small"
                                                    variant="flat"
                                                    color="warning"
                                                >
                                                    {{
                                                        countSelectedPermissions(
                                                            activePermissionIndex,
                                                            getPositionPermissionKey(
                                                                position,
                                                            ),
                                                        )
                                                    }}
                                                </v-chip>
                                            </div>
                                            <v-checkbox
                                                :model-value="
                                                    isAllChecked(
                                                        activePermissionIndex,
                                                        getPositionPermissionKey(
                                                            position,
                                                        ),
                                                    )
                                                "
                                                color="primary"
                                                hide-details
                                                density="compact"
                                                :readonly="readonly"
                                                :disabled="readonly"
                                                @update:model-value="
                                                    toggleAll(
                                                        activePermissionIndex,
                                                        getPositionPermissionKey(
                                                            position,
                                                        ),
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
                                    <v-card variant="outlined" class="h-100">
                                        <v-card-item>
                                            <div
                                                class="d-flex align-center justify-space-between ga-3"
                                            >
                                                <div class="font-weight-medium">
                                                    {{ action.label }}
                                                </div>
                                                <v-chip
                                                    size="x-small"
                                                    variant="tonal"
                                                >
                                                    {{ action.key }}
                                                </v-chip>
                                            </div>
                                        </v-card-item>

                                        <v-divider />

                                        <v-list density="compact">
                                            <v-list-item
                                                v-for="position in renderPositions"
                                                :key="`${action.key}-${getPositionPermissionKey(position)}`"
                                            >
                                                <template #title>
                                                    <div
                                                        class="permission-checkbox-row"
                                                    >
                                                        <span
                                                            v-if="
                                                                showPositionList
                                                            "
                                                        >
                                                            {{ position.name }}
                                                        </span>
                                                        <v-checkbox
                                                            v-model="
                                                                permissionStates[
                                                                    activePermissionIndex
                                                                ][
                                                                    getPositionPermissionKey(
                                                                        position,
                                                                    )
                                                                ][action.key]
                                                            "
                                                            color="primary"
                                                            hide-details
                                                            density="compact"
                                                            :readonly="readonly"
                                                            :disabled="readonly"
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
</template>

<script>
import { constant } from "@/utils/constants/constant";
import { mapActions, mapGetters } from "vuex";

export default {
    props: {
        modelValue: {
            type: [Array, Object],
            default: () => ({}),
        },
        positions: {
            type: Array,
            default: () => [],
        },
        showPositionList: {
            type: Boolean,
            default: true,
        },
        readonly: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            permissionStates: [],
            activePermissionIndex: 0,
            syncingFromModel: false,
        };
    },
    computed: {
        ...mapGetters("department", [
            "defaultPermissions",
            "defaultPermissionsLoading",
        ]),
        permissions() {
            return this.defaultPermissions;
        },
        permissionLoading() {
            return this.defaultPermissionsLoading;
        },
        availableActions() {
            return constant.ACTIONS;
        },
        activePermission() {
            return this.permissions[this.activePermissionIndex] || null;
        },
        renderPositions() {
            return this.showPositionList
                ? this.positions
                : [{ code: "fake_position" }];
        },
    },
    watch: {
        modelValue: {
            handler() {
                this.syncPermissionStates();
            },
            deep: true,
            immediate: true,
        },
        positions: {
            handler() {
                this.syncPermissionStates();
            },
            deep: true,
            immediate: true,
        },
        permissions: {
            handler() {
                this.activePermissionIndex = 0;
                this.syncPermissionStates();
            },
            deep: true,
        },
        permissionStates: {
            handler() {
                if (!this.syncingFromModel) {
                    this.emitPermissionsData();
                }
            },
            deep: true,
        },
    },
    created() {
        this.getPermission();
    },
    methods: {
        ...mapActions("department", ["fetchDefaultPermissions"]),
        async getPermission() {
            await this.fetchDefaultPermissions();
        },
        syncPermissionStates() {
            this.syncingFromModel = true;

            if (!this.permissions.length) {
                if (this.permissionStates.length) {
                    this.permissionStates = [];
                }

                this.$nextTick(() => {
                    this.syncingFromModel = false;
                });
                return;
            }

            this.permissionStates = this.permissions.map((permission) => {
                const stateByPosition = {};

                this.renderPositions.forEach((position) => {
                    const positionKey = this.getPositionPermissionKey(position);
                    const currentModule = this.showPositionList
                        ? this.modelValue?.[positionKey]?.find(
                              (item) => item.name === permission.name,
                          )
                        : this.modelValue?.find(
                              (item) => item.name === permission.name,
                          );
                    const actionState = {};

                    Object.keys(permission.actions || {}).forEach(
                        (actionKey) => {
                            actionState[actionKey] =
                                currentModule?.[positionKey]?.[actionKey] ??
                                currentModule?.actions?.[actionKey] ??
                                false;
                        },
                    );

                    stateByPosition[positionKey] = actionState;
                });

                return stateByPosition;
            });

            this.$nextTick(() => {
                this.syncingFromModel = false;
            });
        },
        emitPermissionsData() {
            if (!this.showPositionList) {
                const positionKey = this.getPositionPermissionKey(
                    this.renderPositions[0],
                );

                this.$emit(
                    "update:modelValue",
                    this.permissions.map((permission, index) => ({
                        name: permission.name,
                        actions:
                            this.permissionStates[index]?.[positionKey] || {},
                    })),
                );
                return;
            }

            const result = {};

            this.renderPositions.forEach((position) => {
                const positionKey = this.getPositionPermissionKey(position);

                result[positionKey] = this.permissions.map(
                    (permission, index) => ({
                        name: permission.name,
                        actions:
                            this.permissionStates[index]?.[positionKey] || {},
                    }),
                );
            });

            this.$emit("update:modelValue", result);
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
        getPositionPermissionKey(position) {
            return position?.code || String(position?.id || "");
        },
        isAllChecked(index, positionKey) {
            const state = this.permissionStates[index]?.[positionKey];
            if (!state) return false;

            return Object.values(state).every((value) => value === true);
        },
        countSelectedPermissions(index, positionKey) {
            const state = this.permissionStates[index]?.[positionKey];
            if (!state) return 0;

            return Object.values(state).filter((value) => value === true)
                .length;
        },
        toggleAll(index, positionKey, value) {
            if (this.readonly) return;

            const state = this.permissionStates[index]?.[positionKey];
            if (!state) return;

            Object.keys(state).forEach((key) => {
                state[key] = value;
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
