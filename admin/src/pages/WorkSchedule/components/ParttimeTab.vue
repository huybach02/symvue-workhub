<template>
    <div>
        <v-alert
            type="info"
            variant="tonal"
            density="compact"
            class="parttime-info-alert"
        >
            {{ $t("calendar.clickShiftToAssign") }}
        </v-alert>

        <Calendar
            :data-calendar="dataCalendar"
            type="parttime"
            @shift-selected="handleShiftSelected"
        />

        <ParttimeAssignDialog
            v-model="dialog"
            :selected-shift="selectedShift"
            :department-id="departmentId"
            @saved="handleAssignmentSaved"
        />
    </div>
</template>

<script>
import dayjs from "dayjs";
import Calendar from "@/components/Calendar.vue";
import ParttimeAssignDialog from "./ParttimeAssignDialog.vue";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getListData } from "@/services/bases/getData";

export default {
    components: {
        Calendar,
        ParttimeAssignDialog,
    },
    props: {
        departmentId: {
            type: String,
            default: "",
        },
    },
    data() {
        return {
            dataCalendar: {
                shifts: [],
            },
            dialog: false,
            selectedShift: null,
            latestShiftRequestId: 0,
        };
    },
    watch: {
        departmentId: {
            handler(newDepartmentId, oldDepartmentId) {
                if (newDepartmentId !== oldDepartmentId) {
                    this.dialog = false;
                    this.selectedShift = null;
                }

                this.getParttimeShifts();
            },
            immediate: true,
        },
        dialog(isOpen) {
            if (!isOpen) {
                this.selectedShift = null;
            }
        },
    },
    methods: {
        async getParttimeShifts() {
            const requestId = ++this.latestShiftRequestId;

            if (!this.departmentId) {
                if (requestId === this.latestShiftRequestId) {
                    this.dataCalendar = { shifts: [] };
                    this.$store.commit(
                        "workSchedule/SET_PARTTIME_LOADING",
                        false,
                    );
                }
                return;
            }

            this.$store.commit("workSchedule/SET_PARTTIME_LOADING", true);
            const today = dayjs();

            try {
                const res = await getListData(
                    `${API_ROUTES_CONFIG.workSchedule}/parttime/shifts`,
                    {
                        departmentId: this.departmentId,
                        startDate: today
                            .subtract(1, "year")
                            .startOf("year")
                            .format("YYYY-MM-DD"),
                        endDate: today
                            .add(1, "year")
                            .endOf("year")
                            .format("YYYY-MM-DD"),
                    },
                );

                if (requestId !== this.latestShiftRequestId) {
                    return;
                }

                this.dataCalendar = res ?? { shifts: [] };
            } finally {
                if (requestId === this.latestShiftRequestId) {
                    this.$store.commit(
                        "workSchedule/SET_PARTTIME_LOADING",
                        false,
                    );
                }
            }
        },
        handleShiftSelected(shift) {
            this.selectedShift = shift;
            this.dialog = true;
        },
        handleAssignmentSaved() {
            this.getParttimeShifts();
        },
    },
};
</script>
