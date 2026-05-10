<template>
    <div>
        <v-dialog v-model="isOpen" max-width="800">
            <v-card
                :title="$t('calendar.assignPersonnel')"
                class="position-relative parttime-dialog-card"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="position-absolute"
                    style="top: 8px; right: 8px"
                    @click="handleClose"
                />

                <v-card-text v-if="selectedShift" class="pt-4">
                    <div class="mb-4 shift-summary-card">
                        <div
                            class="text-subtitle-1 font-weight-medium shift-summary-title"
                        >
                            {{ selectedShift.title }}
                        </div>
                        <div
                            class="text-body-2 text-medium-emphasis shift-summary-meta"
                        >
                            {{ shiftDateLabel }} | {{ shiftTimeRange }}
                        </div>
                    </div>

                    <div class="section-heading mb-3">
                        <span>{{ $t("calendar.personnel") }}</span>
                    </div>

                    <v-autocomplete
                        v-model="selectedMemberIds"
                        :items="optionMembers"
                        item-title="title"
                        item-value="value"
                        :label="$t('calendar.personnel')"
                        variant="outlined"
                        multiple
                        chips
                        closable-chips
                        clearable
                        hide-details="auto"
                    />

                    <div class="dialog-inline-actions mt-4">
                        <v-btn
                            color="primary"
                            :loading="isLoading"
                            @click="handleSaveAssignment"
                        >
                            {{ $t("base.confirm") }}
                        </v-btn>
                    </div>

                    <div class="mt-4">
                        <div class="section-heading mb-3">
                            <span>{{ $t("calendar.assigned") }}</span>
                        </div>

                        <v-list
                            v-if="memberAssigneds.length"
                            class="mb-4 pa-0"
                            density="comfortable"
                        >
                            <v-list-item
                                v-for="member in memberAssigneds"
                                :key="member.id"
                                :title="member.name"
                                :subtitle="member.email || ''"
                                rounded="lg"
                                class="mb-2 border-sm"
                            >
                                <template #prepend>
                                    <v-avatar
                                        size="40"
                                        :image="member.image"
                                        class="mr-3"
                                    >
                                        <v-icon>mdi-account</v-icon>
                                    </v-avatar>
                                </template>

                                <template #append>
                                    <div class="d-flex align-center ga-2">
                                        <v-btn
                                            size="small"
                                            color="error"
                                            @click="
                                                handleDelete(
                                                    member.assignment.id,
                                                )
                                            "
                                        >
                                            {{ $t("base.delete") }}
                                        </v-btn>
                                    </div>
                                </template>
                            </v-list-item>
                        </v-list>

                        <div
                            v-else
                            class="text-body-2 text-medium-emphasis mb-4"
                        >
                            {{ $t("calendar.noShifts") }}
                        </div>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>

        <ConfirmDialog
            v-model="showConfirmDialog"
            :loading="isProcessing"
            @confirm="handleDeleteAssignment"
            @cancel="showConfirmDialog = false"
        />
    </div>
</template>

<script>
import dayjs from "dayjs";
import "dayjs/locale/vi";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getListData } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";
import { deleteData } from "@/services/bases/deleteData";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

dayjs.locale("vi");

export default {
    components: {
        ConfirmDialog,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        selectedShift: {
            type: Object,
            default: null,
        },
        departmentId: {
            type: String,
            default: "",
        },
    },
    emits: ["update:modelValue", "saved"],
    data() {
        return {
            optionMembers: [],
            memberAssigneds: [],
            selectedMemberIds: [],
            isLoading: false,
            showConfirmDialog: false,
            isProcessing: false,
            deleteAssignmentId: null,
        };
    },
    computed: {
        isOpen: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
        shiftTimeRange() {
            if (!this.selectedShift) {
                return "";
            }

            return `${this.selectedShift.startTime} - ${this.selectedShift.endTime}`;
        },
        shiftDateLabel() {
            if (!this.selectedShift?.date) {
                return "";
            }

            const shiftDate = dayjs(this.selectedShift.date);
            const weekday = shiftDate.format("dddd");
            const capitalizedWeekday =
                weekday.charAt(0).toUpperCase() + weekday.slice(1);

            return `${capitalizedWeekday}, ${shiftDate.format("DD/MM/YYYY")}`;
        },
    },
    watch: {
        modelValue: {
            immediate: true,
            async handler(isOpen) {
                if (!isOpen || !this.selectedShift) {
                    this.resetDialogState();
                    return;
                }

                await this.getMembersByDepartment();
            },
        },
        departmentId: {
            async handler(newDepartmentId, oldDepartmentId) {
                if (newDepartmentId === oldDepartmentId) {
                    return;
                }

                this.resetDialogState();

                if (
                    !this.modelValue ||
                    !this.selectedShift ||
                    !newDepartmentId
                ) {
                    return;
                }

                await this.getMembersByDepartment();
            },
        },
        selectedShift: {
            immediate: true,
            async handler(shift) {
                if (!this.modelValue || !shift) {
                    return;
                }

                await this.getMembersByDepartment();
            },
        },
    },
    methods: {
        handleClose() {
            this.isOpen = false;
        },
        resetDialogState() {
            this.optionMembers = [];
            this.memberAssigneds = [];
            this.selectedMemberIds = [];
            this.isLoading = false;
        },
        async handleSaveAssignment() {
            if (!this.selectedShift) {
                return;
            }

            this.isLoading = true;

            await postData(
                `${API_ROUTES_CONFIG.workSchedule}/parttime/assign`,
                {
                    workShiftId: +this.selectedShift.workShiftId,
                    userIds: [...this.selectedMemberIds],
                    date: this.selectedShift.date,
                },
            );

            this.isLoading = false;
            this.$emit("saved");
            this.handleClose();
        },
        async getMembersByDepartment() {
            if (!this.selectedShift) {
                return;
            }

            const res = await getListData(
                `${API_ROUTES_CONFIG.workSchedule}/parttime/members`,
                {
                    shiftId: this.selectedShift.workShiftId,
                    departmentId: this.departmentId,
                    date: this.selectedShift.date,
                },
            );

            this.optionMembers = res.optionMembers;
            this.memberAssigneds = res.memberAssigneds;
            this.selectedMemberIds = this.memberAssigneds.map(
                (member) => member.id,
            );
        },

        handleDelete(assignmentId) {
            this.deleteAssignmentId = assignmentId;
            this.showConfirmDialog = true;
        },

        async handleDeleteAssignment() {
            if (!this.deleteAssignmentId) {
                return;
            }

            this.isProcessing = true;

            const res = await deleteData(
                `${API_ROUTES_CONFIG.workSchedule}/parttime/assign`,
                this.deleteAssignmentId,
            );

            this.isProcessing = false;
            this.showConfirmDialog = false;
            if (res.success) {
                this.$emit("saved");
                this.handleClose();
            }
        },
    },
};
</script>

<style scoped>
.parttime-dialog-card {
    border-radius: 16px;
}

.shift-summary-card {
    padding: 14px 16px;
    border-radius: 14px;
    background: linear-gradient(135deg, #f4f8ff 0%, #eef6ff 100%);
    border: 1px solid #d8e7fb;
}

.shift-summary-title {
    font-size: 20px;
    font-weight: 700 !important;
    color: #163b66;
}

.shift-summary-meta {
    margin-top: 4px;
    font-size: 13px;
    letter-spacing: 0.2px;
}

.section-heading {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 700;
    color: #17324d;
    text-transform: none;
}

.dialog-inline-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
}
</style>
