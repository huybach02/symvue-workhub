<template>
    <div class="d-flex flex-column ga-4">
        <div v-if="loading" class="d-flex justify-center py-8">
            <v-progress-circular indeterminate color="primary" size="48" />
        </div>

        <template v-else>
            <UserPositionInfoCard
                v-if="displayPosition"
                :position="displayPosition"
            />

            <v-alert v-else type="info" variant="tonal">
                {{ $t("position.choose_position_to_view") }}
            </v-alert>

            <v-card variant="outlined">
                <v-card-item>
                    <v-card-title>
                        {{ $t("position.position_detail") }}
                    </v-card-title>
                </v-card-item>

                <v-divider />

                <v-card-text>
                    <UserPositionForm
                        :item="form"
                        :loading="saving"
                        :currency="displayPosition?.currency || 'VND'"
                        :branch-options="branchOptions"
                        :department-options="departmentOptions"
                        :position-options="positionOptions"
                        :submit-button-text="$t('button.update')"
                        @branch-change="handleBranchChange"
                        @department-change="handleDepartmentChange"
                        @position-change="handlePositionChange"
                        @submit="submitForm"
                    />
                </v-card-text>
            </v-card>
        </template>
    </div>
</template>

<script>
import UserPositionForm from "./UserPositionForm.vue";
import UserPositionInfoCard from "./UserPositionInfoCard.vue";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        UserPositionForm,
        UserPositionInfoCard,
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
        active: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["reload"],
    data() {
        return {
            loading: false,
            saving: false,
            selectedPosition: null,
            form: this.createDefaultForm(),
        };
    },
    computed: {
        ...mapGetters("user", [
            "departmentOptionsByBranch",
            "positionOptionsByDepartment",
            "userPositionByUserId",
        ]),
        ...mapGetters("branch", { branchOptions: "options" }),
        departmentOptions() {
            return this.departmentOptionsByBranch(this.form.branchId);
        },
        positionOptions() {
            return this.positionOptionsByDepartment(this.form.departmentId);
        },
        displayPosition() {
            return this.selectedPosition || this.form.positionSnapshot || null;
        },
    },
    watch: {
        active: {
            async handler(isActive) {
                if (isActive) {
                    await this.loadData();
                }
            },
            immediate: true,
        },
    },
    methods: {
        ...mapActions("user", [
            "fetchDepartmentOptions",
            "fetchDepartmentPositions",
            "fetchUserPosition",
            "updateUserPosition",
        ]),
        ...mapActions("branch", { fetchBranchOptions: "fetchOptions" }),
        createDefaultForm() {
            return {
                branchId: null,
                departmentId: null,
                positionId: null,
                salary: null,
                salaryGross: null,
                salaryNet: null,
                insuranceSalary: null,
                insuranceCode: "",
                effectiveFrom: "",
                effectiveTo: "",
                probationFrom: "",
                probationTo: "",
                note: "",
                positionSnapshot: null,
                allowances: [{ name: "", amount: null }],
            };
        },
        async loadData() {
            this.loading = true;

            try {
                const [, userPosition] = await Promise.all([
                    this.fetchBranchOptions(),
                    this.fetchUserPosition(this.item.id),
                ]);

                if (!userPosition) {
                    this.form = this.createDefaultForm();
                    this.selectedPosition = null;
                    return;
                }

                this.form = {
                    ...this.createDefaultForm(),
                    ...userPosition,
                    allowances:
                        Array.isArray(userPosition.allowances) &&
                        userPosition.allowances.length
                            ? userPosition.allowances
                            : [{ name: "", amount: null }],
                };

                if (this.form.branchId) {
                    await this.loadDepartments(this.form.branchId, false);
                }

                if (this.form.departmentId) {
                    await this.loadPositions(this.form.departmentId, false);
                    this.selectedPosition =
                        this.positionOptions.find(
                            (position) => position.id === this.form.positionId,
                        ) || null;
                }
            } finally {
                this.loading = false;
            }
        },
        async loadDepartments(branchId, resetDepartment = true) {
            if (!branchId) {
                this.form = {
                    ...this.form,
                    branchId: null,
                    departmentId: null,
                    positionId: null,
                    positionSnapshot: null,
                };
                this.selectedPosition = null;
                return;
            }

            await this.fetchDepartmentOptions({
                branchId,
                force: true,
            });

            if (resetDepartment) {
                this.form = {
                    ...this.form,
                    branchId,
                    departmentId: null,
                    positionId: null,
                    positionSnapshot: null,
                };
                this.selectedPosition = null;
            }
        },
        async loadPositions(departmentId, resetPosition = true) {
            if (!departmentId) {
                this.selectedPosition = null;

                if (resetPosition) {
                    this.form = {
                        ...this.form,
                        positionId: null,
                        positionSnapshot: null,
                    };
                }

                return;
            }

            const positionOptions = await this.fetchDepartmentPositions({
                departmentId,
                force: true,
            });

            if (resetPosition) {
                this.form = {
                    ...this.form,
                    departmentId,
                    positionId: null,
                    positionSnapshot: null,
                };
                this.selectedPosition = null;
                return;
            }

            this.selectedPosition =
                positionOptions.find(
                    (position) => position.id === this.form.positionId,
                ) || null;
        },
        async handleBranchChange(value) {
            this.form = {
                ...this.form,
                branchId: value,
                departmentId: null,
                positionId: null,
                positionSnapshot: null,
            };

            await this.loadDepartments(value);
        },
        async handleDepartmentChange(value) {
            this.form = {
                ...this.form,
                departmentId: value,
                positionId: null,
                positionSnapshot: null,
            };

            await this.loadPositions(value);
        },
        handlePositionChange(value) {
            const selectedPosition =
                this.positionOptions.find(
                    (position) => position.id === value,
                ) || null;

            this.form = {
                ...this.form,
                positionId: value,
                positionSnapshot: selectedPosition,
            };
            this.selectedPosition = selectedPosition;
        },
        async submitForm(values) {
            this.saving = true;

            try {
                const response = await this.updateUserPosition({
                    userId: this.item.id,
                    values: {
                        departmentId: values.departmentId,
                        positionId: values.positionId,
                        salary: values.salary,
                        allowances: values.allowances,
                        effectiveFrom: values.effectiveFrom || null,
                        effectiveTo: values.effectiveTo || null,
                        probationFrom: values.probationFrom,
                        probationTo: values.probationTo || null,
                        salaryNet: values.salaryNet,
                        salaryGross: values.salaryGross,
                        insuranceSalary: values.insuranceSalary,
                        insuranceCode: values.insuranceCode || null,
                        note: values.note || null,
                    },
                });

                if (response) {
                    this.form = {
                        ...this.createDefaultForm(),
                        ...response,
                        allowances:
                            Array.isArray(response.allowances) &&
                            response.allowances.length
                                ? response.allowances
                                : [{ name: "", amount: null }],
                    };

                    this.selectedPosition =
                        this.positionOptions.find(
                            (position) => position.id === response.positionId,
                        ) || null;

                    this.$emit("reload");
                }
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
