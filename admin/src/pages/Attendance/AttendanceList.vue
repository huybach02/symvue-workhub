<template>
    <div>
        <v-row>
            <v-col cols="12">
                <v-row align="center">
                    <v-col cols="12" md="4" lg="3">
                        <div class="text-body-2 font-weight-medium mb-1">
                            {{ $t("attendance.filters.work_date") }}
                        </div>
                        <DatePicker
                            :model-value="selectedWorkDate"
                            density="compact"
                            :placeholder="
                                $t('attendance.filters.work_date_placeholder')
                            "
                            @update:model-value="handleWorkDateChange"
                        />
                    </v-col>
                </v-row>
            </v-col>

        <v-col
            v-for="section in attendanceSections"
            :key="section.key"
            cols="12"
            md="6"
        >
            <v-card rounded="lg" height="100%" elevation="1">
                <v-card-title class="d-flex align-center ga-2 py-3 px-4">
                    <v-icon :icon="section.icon" color="primary" />
                    <span class="text-subtitle-1 font-weight-bold">
                        {{ $t(section.titleKey) }}
                    </span>
                    <v-spacer />
                    <v-chip color="primary" size="small" variant="tonal">
                        {{
                            $t("attendance.labels.shift_count", {
                                count: section.totalShifts,
                            })
                        }}
                    </v-chip>
                </v-card-title>

                <v-card-text class="pa-3">
                    <div v-if="loading" class="d-flex justify-center py-8">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="28"
                        />
                    </div>

                    <template v-else>
                        <v-text-field
                            :model-value="section.keyword"
                            class="mb-3"
                            clearable
                            density="compact"
                            hide-details
                            :placeholder="
                                $t('attendance.filters.employee_placeholder')
                            "
                            prepend-inner-icon="mdi-magnify"
                            variant="outlined"
                            @update:model-value="
                                updateSectionKeyword(section.key, $event)
                            "
                        />

                        <v-list
                            v-if="section.groups.length > 0"
                            bg-color="transparent"
                            class="px-1 py-1 d-flex flex-column ga-2"
                        >
                            <v-card
                                v-for="group in section.groups"
                                :key="group.groupKey"
                                rounded="lg"
                                class="mx-1 mb-2"
                                elevation="2"
                            >
                                <v-card-text class="pa-3">
                                    <div class="d-flex align-start ga-2 mb-2">
                                        <v-avatar
                                            color="primary"
                                            size="28"
                                            variant="tonal"
                                        >
                                            <v-icon
                                                icon="mdi-account-outline"
                                            />
                                        </v-avatar>

                                        <div class="flex-grow-1">
                                            <div
                                                class="d-flex flex-wrap align-center ga-2"
                                            >
                                                <div
                                                    class="text-body-1 font-weight-bold"
                                                >
                                                    {{ getEmployeeName(group) }}
                                                </div>

                                                <v-chip
                                                    v-for="chip in getGroupHeaderChips(
                                                        section.key,
                                                        group,
                                                    )"
                                                    :key="chip.key"
                                                    :color="chip.color"
                                                    size="x-small"
                                                    :variant="chip.variant"
                                                >
                                                    {{ chip.label }}
                                                </v-chip>
                                            </div>
                                        </div>
                                    </div>

                                    <v-list
                                        bg-color="transparent"
                                        class="pa-0 d-flex flex-column ga-2"
                                    >
                                        <component
                                            :is="section.shiftWrapperComponent"
                                            v-for="shift in group.shifts"
                                            :key="shift.shiftKey"
                                            v-bind="section.shiftWrapperProps"
                                        >
                                            <v-card-text class="pa-3">
                                                <div
                                                    v-if="
                                                        section.isPartTime ||
                                                        group.shifts.length > 1
                                                    "
                                                    class="d-flex align-center ga-2 mb-2"
                                                >
                                                    <v-avatar
                                                        color="primary"
                                                        size="24"
                                                        variant="tonal"
                                                    >
                                                        <v-icon
                                                            icon="mdi-calendar-clock-outline"
                                                            size="16"
                                                        />
                                                    </v-avatar>
                                                    <div
                                                        class="text-body-2 font-weight-medium"
                                                    >
                                                        {{
                                                            getAttendanceShiftLabel(
                                                                shift,
                                                            )
                                                        }}
                                                    </div>
                                                </div>

                                                <v-row>
                                                    <v-col
                                                        v-for="action in attendanceActions"
                                                        :key="`${shift.shiftKey}-${action.key}`"
                                                        cols="12"
                                                        md="6"
                                                    >
                                                        <v-card
                                                            rounded="md"
                                                            class="ma-1"
                                                            variant="outlined"
                                                        >
                                                            <v-card-text
                                                                class="pa-2"
                                                            >
                                                                <div
                                                                    class="d-flex align-center ga-2"
                                                                >
                                                                    <v-avatar
                                                                        :color="
                                                                            action.color
                                                                        "
                                                                        size="22"
                                                                        variant="tonal"
                                                                    >
                                                                        <v-icon
                                                                            :icon="
                                                                                action.icon
                                                                            "
                                                                            size="14"
                                                                        />
                                                                    </v-avatar>
                                                                    <div
                                                                        class="text-body-2 font-weight-medium"
                                                                    >
                                                                        {{
                                                                            $t(
                                                                                action.labelKey,
                                                                            )
                                                                        }}
                                                                    </div>
                                                                    <v-spacer />
                                                                    <v-btn
                                                                        size="x-small"
                                                                        variant="tonal"
                                                                        icon="mdi-history"
                                                                        :aria-label="
                                                                            $t(
                                                                                'attendance.labels.view_log',
                                                                            )
                                                                        "
                                                                        :title="
                                                                            $t(
                                                                                'attendance.labels.view_log',
                                                                            )
                                                                        "
                                                                        @click="
                                                                            viewAttendanceLog(
                                                                                shift,
                                                                                action,
                                                                            )
                                                                        "
                                                                    />
                                                                </div>

                                                                <div
                                                                    class="d-flex flex-wrap ga-1 mt-2"
                                                                >
                                                                    <v-chip
                                                                        :color="
                                                                            getAttendanceStatusColor(
                                                                                getActionRecord(
                                                                                    shift,
                                                                                    action,
                                                                                )
                                                                                    ?.status,
                                                                            )
                                                                        "
                                                                        size="x-small"
                                                                        variant="tonal"
                                                                    >
                                                                        {{
                                                                            getAttendanceStatusLabel(
                                                                                getActionRecord(
                                                                                    shift,
                                                                                    action,
                                                                                )
                                                                                    ?.status,
                                                                            )
                                                                        }}
                                                                    </v-chip>

                                                                    <v-chip
                                                                        v-if="
                                                                            hasAttendanceTime(
                                                                                getActionRecord(
                                                                                    shift,
                                                                                    action,
                                                                                ),
                                                                            )
                                                                        "
                                                                        size="x-small"
                                                                        variant="outlined"
                                                                    >
                                                                        {{
                                                                            getAttendanceTimeLabel(
                                                                                getActionRecord(
                                                                                    shift,
                                                                                    action,
                                                                                ),
                                                                            )
                                                                        }}
                                                                    </v-chip>
                                                                </div>
                                                            </v-card-text>
                                                        </v-card>
                                                    </v-col>
                                                </v-row>
                                            </v-card-text>
                                        </component>
                                    </v-list>
                                </v-card-text>
                            </v-card>
                        </v-list>

                        <v-card v-else rounded="lg" elevation="1">
                            <v-card-text
                                class="py-8 text-center text-medium-emphasis"
                            >
                                {{ $t("attendance.empty.no_data") }}
                            </v-card-text>
                        </v-card>
                    </template>
                </v-card-text>
            </v-card>
        </v-col>
        </v-row>

        <AttendanceLogDialog
            v-model="isAttendanceLogDialogOpen"
            :logs="attendanceLogs"
            :action-label="attendanceLogContext.actionLabel"
            :employee-name="attendanceLogContext.employeeName"
            :shift-label="attendanceLogContext.shiftLabel"
        />
    </div>
</template>

<script>
import dayjs from "dayjs";
import DatePicker from "@/components/DatePicker.vue";
import AttendanceLogDialog from "./components/AttendanceLogDialog.vue";
import { mapGetters } from "vuex";
import {
    ATTENDANCE_SECTION_KEYS,
    ATTENDANCE_TYPES,
    ATTENDANCE_STATUS,
    ATTENDANCE_UI,
} from "@/utils/constants/constant";

export default {
    name: "AttendanceList",
    components: {
        AttendanceLogDialog,
        DatePicker,
    },
    props: {
        path: {
            type: String,
            default: "",
        },
        permission: {
            type: Object,
            default: () => ({}),
        },
    },
    emits: ["reload"],
    data() {
        return {
            selectedWorkDate: dayjs().format("YYYY-MM-DD"),
            searchKeywords: {
                [ATTENDANCE_SECTION_KEYS.FULL_TIME]: "",
                [ATTENDANCE_SECTION_KEYS.PART_TIME]: "",
            },
            attendanceRecords: [],
            attendanceLogs: [],
            isAttendanceLogDialogOpen: false,
            attendanceLogContext: {
                actionLabel: "",
                employeeName: "",
                shiftLabel: "",
            },
        };
    },
    computed: {
        ...mapGetters("attendance", ["items", "loading"]),
        attendanceActions() {
            return ATTENDANCE_UI.actions;
        },
        visibleShiftRecords() {
            return this.buildVisibleShiftRecords(this.attendanceRecords);
        },
        attendanceSections() {
            return ATTENDANCE_UI.sections.map((config) =>
                this.buildAttendanceSection(config),
            );
        },
    },
    watch: {
        items: {
            immediate: true,
            handler(newItems) {
                this.syncAttendanceRecords(newItems);
            },
        },
    },
    mounted() {
        window.addEventListener(
            "attendance:updated",
            this.handleAttendanceRealtimeMessage,
        );
        this.reloadAttendances();
    },
    beforeUnmount() {
        window.removeEventListener(
            "attendance:updated",
            this.handleAttendanceRealtimeMessage,
        );
    },
    methods: {
        handleWorkDateChange(value) {
            this.selectedWorkDate = value;
            this.attendanceRecords = [];
            this.reloadAttendances();
        },
        reloadAttendances() {
            if (!this.selectedWorkDate) {
                return ATTENDANCE_UI.defaultFilterParams;
            }

            this.$emit("reload", {
                ...ATTENDANCE_UI.defaultFilterParams,
                f: [
                    {
                        field: "workDate",
                        operator: "equal",
                        value: this.selectedWorkDate,
                    },
                ],
            });
        },
        syncAttendanceRecords(records) {
            this.attendanceRecords = this.normalizeAttendanceRecords(records);
        },
        normalizeAttendanceRecords(records) {
            return (records ?? [])
                .map((record) => this.normalizeAttendanceRecord(record))
                .filter(Boolean);
        },
        normalizeAttendanceRecord(record, options = {}) {
            if (!record || !record.attendanceType) {
                return null;
            }

            return {
                ...record,
                workDate: this.formatWorkDate(record.workDate),
                _sortValue:
                    options.sortValue ??
                    record._sortValue ??
                    Number(record?.id ?? 0),
            };
        },
        handleAttendanceRealtimeMessage(event) {
            const record = this.extractAttendanceRealtimeRecord(event?.detail);

            if (!record || !this.shouldHandleRealtimeRecord(record)) {
                return;
            }

            this.upsertAttendanceRecord(record);
        },
        extractAttendanceRealtimeRecord(payload) {
            const resolvedRecord =
                payload?.attendance ??
                payload?.record ??
                payload?.data ??
                payload;

            return this.normalizeAttendanceRecord(resolvedRecord, {
                sortValue: this.resolveRealtimeSortValue(payload),
            });
        },
        shouldHandleRealtimeRecord(record) {
            const workDate = this.formatWorkDate(record.workDate);

            if (this.selectedWorkDate && workDate !== this.selectedWorkDate) {
                return false;
            }

            return Object.values(ATTENDANCE_SECTION_KEYS).includes(
                record.workType,
            );
        },
        upsertAttendanceRecord(record) {
            const nextRecords = [...this.attendanceRecords];
            const recordIndex = nextRecords.findIndex(
                (item) => Number(item.id) === Number(record.id),
            );

            if (recordIndex >= 0) {
                nextRecords.splice(recordIndex, 1, record);
                this.attendanceRecords = nextRecords;

                return;
            }

            nextRecords.unshift(record);

            this.attendanceRecords = nextRecords;
        },
        updateSectionKeyword(sectionKey, value) {
            this.searchKeywords[sectionKey] = String(value ?? "");
        },
        getActionRecord(shift, action) {
            return shift?.[action.recordKey] ?? null;
        },
        buildAttendanceSection(config) {
            const keyword = this.searchKeywords[config.key] ?? "";
            const groups = this.buildSectionGroups(config.key, keyword);
            const isPartTime = config.key === ATTENDANCE_SECTION_KEYS.PART_TIME;

            return {
                ...config,
                isPartTime,
                keyword,
                groups,
                shiftWrapperComponent: isPartTime ? "v-card" : "div",
                shiftWrapperProps: isPartTime
                    ? ATTENDANCE_UI.shiftWrapperProps.partTime
                    : undefined,
                totalShifts: groups.reduce(
                    (total, group) => total + group.shifts.length,
                    0,
                ),
            };
        },
        buildVisibleShiftRecords(records) {
            const shiftMap = {};

            records.forEach((record) => {
                const shiftKey = this.getShiftKey(record);
                const shiftRecord =
                    shiftMap[shiftKey] ??
                    this.createShiftRecord(shiftKey, record);

                this.assignAttendanceRecord(shiftRecord, record);
                shiftRecord.sortValue = Math.max(
                    shiftRecord.sortValue,
                    Number(record._sortValue ?? Number(record?.id ?? 0)),
                );
                shiftRecord.hasVisibleStatus =
                    shiftRecord.hasVisibleStatus ||
                    this.hasVisibleAttendanceStatus(record.status);
                shiftMap[shiftKey] = shiftRecord;
            });

            return Object.values(shiftMap)
                .filter((shift) => shift.hasVisibleStatus)
                .map((shift) => this.hydrateShiftRecords(shift))
                .sort((firstShift, secondShift) =>
                    this.compareShiftRecords(firstShift, secondShift),
                );
        },
        buildSectionGroups(sectionKey, keyword) {
            const shiftRecords = this.visibleShiftRecords.filter(
                (shift) => shift.workType === sectionKey,
            );
            const filteredShiftRecords = this.filterShiftRecordsByKeyword(
                shiftRecords,
                keyword,
            );
            const groupMap = {};

            filteredShiftRecords.forEach((shift) => {
                const groupKey = this.getEmployeeGroupKey(shift);
                const group =
                    groupMap[groupKey] ??
                    this.createShiftGroup(groupKey, shift);

                group.sortValue = Math.max(group.sortValue, shift.sortValue);
                group.shifts.push(shift);
                groupMap[groupKey] = group;
            });

            return Object.values(groupMap)
                .map((group) => ({
                    ...group,
                    shifts: [...group.shifts].sort((firstShift, secondShift) =>
                        this.compareShiftOrder(firstShift, secondShift),
                    ),
                }))
                .sort((firstGroup, secondGroup) =>
                    this.compareGroupOrder(firstGroup, secondGroup),
                );
        },
        filterShiftRecordsByKeyword(records, keyword) {
            const normalizedKeyword = String(keyword ?? "")
                .trim()
                .toLowerCase();

            if (!normalizedKeyword) {
                return records;
            }

            return records.filter((record) =>
                this.getEmployeeSearchText(record).includes(normalizedKeyword),
            );
        },
        compareShiftRecords(firstShift, secondShift) {
            return (
                secondShift.sortValue - firstShift.sortValue ||
                this.compareShiftOrder(firstShift, secondShift)
            );
        },
        compareShiftOrder(firstShift, secondShift) {
            return (
                String(firstShift.workScheduleStartTime).localeCompare(
                    String(secondShift.workScheduleStartTime),
                ) ||
                String(firstShift.workScheduleEndTime).localeCompare(
                    String(secondShift.workScheduleEndTime),
                ) ||
                secondShift.sortValue - firstShift.sortValue
            );
        },
        compareGroupOrder(firstGroup, secondGroup) {
            return (
                secondGroup.sortValue - firstGroup.sortValue ||
                this.getEmployeeName(firstGroup).localeCompare(
                    this.getEmployeeName(secondGroup),
                )
            );
        },
        getGroupHeaderChips(sectionKey, group) {
            const chips = [];

            if (
                sectionKey === ATTENDANCE_SECTION_KEYS.FULL_TIME &&
                group.shifts.length === 1
            ) {
                chips.push({
                    key: "shift",
                    label: this.getAttendanceShiftLabel(group.shifts[0]),
                    variant: "outlined",
                });
            }

            chips.push({
                key: "work-date",
                label: group.workDate || this.$t("base.not_available"),
                variant: "text",
            });

            if (sectionKey === ATTENDANCE_SECTION_KEYS.PART_TIME) {
                chips.push({
                    key: "shift-count",
                    label: this.$t("attendance.labels.shift_count", {
                        count: group.shifts.length,
                    }),
                    variant: "tonal",
                    color: "primary",
                });
            }

            return chips;
        },
        createShiftRecord(shiftKey, record) {
            return {
                shiftKey,
                employee: record.employee,
                workDate: this.formatWorkDate(record.workDate),
                workType: record.workType,
                workScheduleStartTime: record.workScheduleStartTime,
                workScheduleEndTime: record.workScheduleEndTime,
                checkIn: null,
                checkOut: null,
                hasVisibleStatus: false,
                sortValue: 0,
            };
        },
        assignAttendanceRecord(shiftRecord, record) {
            if (record.attendanceType === ATTENDANCE_TYPES.CHECK_IN) {
                shiftRecord.checkIn = record;
            }

            if (record.attendanceType === ATTENDANCE_TYPES.CHECK_OUT) {
                shiftRecord.checkOut = record;
            }
        },
        hasVisibleAttendanceStatus(status) {
            return Boolean(status && status !== ATTENDANCE_STATUS.SCHEDULED);
        },
        createShiftGroup(groupKey, shift) {
            return {
                groupKey,
                employee: shift.employee,
                workDate: shift.workDate,
                workType: shift.workType,
                sortValue: shift.sortValue,
                shifts: [],
            };
        },
        buildKey(parts) {
            return parts.join("-");
        },
        getShiftKey(record) {
            return this.buildKey([
                record.employee?.id ?? "",
                this.formatWorkDate(record.workDate),
                record.workType ?? "",
                record.workScheduleStartTime ?? "",
                record.workScheduleEndTime ?? "",
            ]);
        },
        getEmployeeGroupKey(shift) {
            return this.buildKey([
                shift.employee?.id ?? "",
                shift.workDate ?? "",
                shift.workType ?? "",
            ]);
        },
        hydrateShiftRecords(shift) {
            return {
                ...shift,
                checkIn:
                    shift.checkIn ??
                    this.createAttendancePlaceholder(
                        ATTENDANCE_TYPES.CHECK_IN,
                        shift,
                    ),
                checkOut:
                    shift.checkOut ??
                    this.createAttendancePlaceholder(
                        ATTENDANCE_TYPES.CHECK_OUT,
                        shift,
                    ),
            };
        },
        createAttendancePlaceholder(attendanceType, shift) {
            return {
                id: null,
                employee: shift.employee,
                attendanceType,
                workDate: shift.workDate,
                workType: shift.workType,
                workScheduleStartTime: shift.workScheduleStartTime,
                workScheduleEndTime: shift.workScheduleEndTime,
                status: ATTENDANCE_STATUS.SCHEDULED,
                timeAttendance: null,
            };
        },
        resolveRealtimeSortValue(payload) {
            const timestamp = Date.parse(payload?.timestamp ?? "");

            if (!Number.isNaN(timestamp)) {
                return timestamp;
            }

            return Date.now();
        },
        getEmployeeMeta(item) {
            if (typeof item.employee === "string") {
                return {
                    name: item.employee,
                    code: "",
                };
            }

            return {
                name: item.employee?.name ?? "",
                code: item.employee?.maNhanVien ?? "",
            };
        },
        getEmployeeName(item) {
            const { name, code } = this.getEmployeeMeta(item);

            if (!name) {
                return this.$t("attendance.employee.unknown");
            }

            return code ? `${name} - ${code}` : name;
        },
        getEmployeeSearchText(item) {
            const { name, code } = this.getEmployeeMeta(item);

            return `${name} ${code}`.trim().toLowerCase();
        },
        getAttendanceShiftLabel(item) {
            const startTime = item.workScheduleStartTime ?? "--:--";
            const endTime = item.workScheduleEndTime ?? "--:--";

            return this.$t("attendance.labels.shift_schedule", {
                start: startTime,
                end: endTime,
            });
        },
        getAttendanceTimeLabel(item) {
            return item?.timeAttendance || "";
        },
        hasAttendanceTime(item) {
            return Boolean(item?.timeAttendance);
        },
        getAttendanceStatusLabel(status) {
            return this.$t(
                ATTENDANCE_UI.status.labels[status] ??
                    "attendance.status.unknown",
            );
        },
        getAttendanceStatusColor(status) {
            return ATTENDANCE_UI.status.colors[status] ?? "grey";
        },
        formatWorkDate(workDate) {
            if (!workDate) {
                return "";
            }

            if (typeof workDate === "string") {
                return workDate;
            }

            if (workDate?.date) {
                return String(workDate.date).split(" ")[0];
            }

            return "";
        },
        viewAttendanceLog(shift, action) {
            const attendanceRecord = this.getActionRecord(shift, action);

            this.attendanceLogs = attendanceRecord?.attendanceLogs ?? [];
            this.attendanceLogContext = {
                actionLabel: this.$t(action.labelKey),
                employeeName: this.getEmployeeName(shift),
                shiftLabel: this.getAttendanceShiftLabel(shift),
            };
            this.isAttendanceLogDialogOpen = true;
        },
    },
};
</script>
