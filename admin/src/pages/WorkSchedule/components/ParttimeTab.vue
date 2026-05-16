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
            @date-range-change="handleDateRangeChange"
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
import { functionHelper } from "@/helpers/functionHelper";
import { mapActions, mapGetters } from "vuex";

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
            dialog: false,
            selectedShift: null,
            currentFetchRange: functionHelper.getParttimeScheduleFetchRange(
                dayjs(),
            ),
        };
    },
    computed: {
        ...mapGetters("workSchedule", ["parttimeDataByDepartment"]),
        dataCalendar() {
            if (!this.departmentId) {
                return { shifts: [] };
            }

            return this.parttimeDataByDepartment(this.departmentId);
        },
    },
    watch: {
        departmentId: {
            handler(newDepartmentId, oldDepartmentId) {
                if (newDepartmentId !== oldDepartmentId) {
                    this.dialog = false;
                    this.selectedShift = null;
                }
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
        ...mapActions("workSchedule", ["ensureParttimeShifts"]),
        async getParttimeShifts(range = this.currentFetchRange, force = false) {
            this.currentFetchRange = range;

            await this.ensureParttimeShifts({
                departmentId: this.departmentId,
                ...range,
                force,
            });
        },
        handleDateRangeChange(range) {
            const fetchRange = functionHelper.getParttimeScheduleFetchRange(
                range?.baseDate ?? dayjs(),
            );

            this.getParttimeShifts(fetchRange);
        },
        handleShiftSelected(shift) {
            this.selectedShift = shift;
            this.dialog = true;
        },
        handleAssignmentSaved() {
            this.getParttimeShifts(this.currentFetchRange, true);
        },
    },
};
</script>
