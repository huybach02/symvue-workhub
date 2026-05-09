<template>
    <tbody v-if="loading">
        <tr>
            <td
                :colspan="calendarColumns.length"
                class="calendar-cell calendar-loading-cell"
            >
                <div class="calendar-loading-wrap">
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        size="36"
                    />
                </div>
            </td>
        </tr>
    </tbody>

    <tbody v-else-if="currentMode !== 'day'">
        <tr>
            <td
                v-for="col in calendarColumns"
                :key="col.value"
                :title="col.holidayName || null"
                class="calendar-cell text-center"
                :class="{
                    'holiday-cell': col.isHoliday,
                    'weekend-cell': col.isWeekend && !col.isHoliday,
                    'today-cell': col.isToday,
                }"
            >
                <div class="events-container parttime-events-container">
                    <v-chip
                        v-if="col.isHoliday"
                        size="x-small"
                        color="red"
                        text-color="red-darken-3"
                        class="ma-1 font-weight-bold holiday-tag"
                        variant="flat"
                    >
                        {{ col.holidayName || "Nghỉ lễ" }}
                    </v-chip>

                    <v-tooltip
                        v-for="shift in getShiftsByDate(col.value)"
                        :key="shift.id"
                        location="top"
                        :disabled="!getAllAssignedMemberNames(shift).length"
                    >
                        <template #activator="{ props }">
                            <v-chip
                                v-bind="props"
                                :color="shift.color || DEFAULT_SHIFT_COLOR"
                                size="small"
                                class="ma-1 w-100 parttime-chip"
                                variant="flat"
                                @click="$emit('shiftSelected', shift)"
                            >
                                <div class="parttime-chip-content">
                                    <div class="parttime-chip-title">
                                        {{ formatShiftLabel(shift) }}
                                    </div>
                                    <div
                                        v-if="
                                            getVisibleAssignedMembers(shift)
                                                .length
                                        "
                                        class="parttime-member-list"
                                    >
                                        <div
                                            v-for="memberName in getVisibleAssignedMembers(
                                                shift,
                                            )"
                                            :key="memberName"
                                            class="parttime-member-item"
                                        >
                                            {{ memberName }}
                                        </div>
                                        <div
                                            v-if="
                                                getRemainingAssignedCount(
                                                    shift,
                                                ) > 0
                                            "
                                            class="parttime-member-more"
                                        >
                                            +
                                            {{
                                                getRemainingAssignedCount(shift)
                                            }}
                                            {{ $t("calendar.personnel") }}
                                        </div>
                                    </div>
                                </div>
                            </v-chip>
                        </template>

                        <div class="shift-tooltip">
                            <div class="shift-tooltip-title">
                                {{ formatShiftLabel(shift) }}
                            </div>
                            <div class="shift-tooltip-subtitle">
                                {{ $t("calendar.personnel") }}
                            </div>
                            <div class="shift-tooltip-list">
                                <div
                                    v-for="memberName in getAllAssignedMemberNames(
                                        shift,
                                    )"
                                    :key="memberName"
                                    class="shift-tooltip-item"
                                >
                                    {{ memberName }}
                                </div>
                            </div>
                        </div>
                    </v-tooltip>
                </div>
            </td>
        </tr>
    </tbody>

    <tbody v-else-if="dayModeShifts.length">
        <tr v-for="shift in dayModeShifts" :key="shift.id">
            <td
                v-for="cell in getDayModeRowCells(shift)"
                :key="cell.key"
                class="calendar-cell text-center day-mode-cell"
                :colspan="cell.colspan"
            >
                <div v-if="cell.event" class="day-mode-event-wrapper">
                    <v-tooltip
                        location="top"
                        :disabled="
                            !getAllAssignedMemberNames(cell.event).length
                        "
                    >
                        <template #activator="{ props }">
                            <v-chip
                                v-bind="props"
                                :color="cell.event.color || DEFAULT_SHIFT_COLOR"
                                size="small"
                                class="w-100 day-mode-chip day-mode-shift-chip"
                                variant="flat"
                                @click="$emit('shiftSelected', cell.event)"
                            >
                                <div class="parttime-chip-content">
                                    <div class="parttime-chip-title">
                                        {{ formatShiftLabel(cell.event) }}
                                    </div>
                                    <div
                                        v-if="
                                            getVisibleAssignedMembers(
                                                cell.event,
                                            ).length
                                        "
                                        class="parttime-member-list"
                                    >
                                        <div
                                            v-for="memberName in getVisibleAssignedMembers(
                                                cell.event,
                                            )"
                                            :key="memberName"
                                            class="parttime-member-item"
                                        >
                                            {{ memberName }}
                                        </div>
                                        <div
                                            v-if="
                                                getRemainingAssignedCount(
                                                    cell.event,
                                                ) > 0
                                            "
                                            class="parttime-member-more"
                                        >
                                            +
                                            {{
                                                getRemainingAssignedCount(
                                                    cell.event,
                                                )
                                            }}
                                            {{ $t("calendar.personnel") }}
                                        </div>
                                    </div>
                                </div>
                            </v-chip>
                        </template>

                        <div class="shift-tooltip">
                            <div class="shift-tooltip-title">
                                {{ formatShiftLabel(cell.event) }}
                            </div>
                            <div class="shift-tooltip-subtitle">
                                {{ $t("calendar.personnel") }}
                            </div>
                            <div class="shift-tooltip-list">
                                <div
                                    v-for="memberName in getAllAssignedMemberNames(
                                        cell.event,
                                    )"
                                    :key="memberName"
                                    class="shift-tooltip-item"
                                >
                                    {{ memberName }}
                                </div>
                            </div>
                        </div>
                    </v-tooltip>
                </div>
            </td>
        </tr>
    </tbody>

    <tbody v-else>
        <tr>
            <td
                :colspan="calendarColumns.length"
                class="calendar-cell text-center empty-parttime-cell"
            >
                {{ $t("calendar.noShifts") }}
            </td>
        </tr>
    </tbody>
</template>

<script>
import { buildDayModeCells, formatTimeRange } from "./calendarShared";

const DEFAULT_SHIFT_COLOR = "#2e7d32";

export default {
    name: "CalendarParttimeBody",
    props: {
        shifts: {
            type: Array,
            default: () => [],
        },
        calendarColumns: {
            type: Array,
            default: () => [],
        },
        currentMode: {
            type: String,
            default: "week",
        },
        baseDate: {
            type: Object,
            required: true,
        },
    },

    emits: ["shiftSelected"],
    data() {
        return {
            DEFAULT_SHIFT_COLOR,
        };
    },
    computed: {
        loading() {
            return this.$store.getters["workSchedule/parttimeLoading"];
        },
        dayModeShifts() {
            return this.getShiftsByDate(this.baseDate.format("YYYY-MM-DD"));
        },
    },
    methods: {
        formatShiftLabel(shift) {
            return `${shift.title}: ${formatTimeRange(shift)}`;
        },
        getVisibleAssignedMembers(shift) {
            return this.getAllAssignedMemberNames(shift).slice(0, 5);
        },
        getRemainingAssignedCount(shift) {
            const assignedMembers = shift.assignedMembers ?? [];
            const visibleCount = this.getVisibleAssignedMembers(shift).length;

            return Math.max(assignedMembers.length - visibleCount, 0);
        },
        getAllAssignedMemberNames(shift) {
            const assignedMembers = shift.assignedMembers ?? [];

            return assignedMembers.map((member) => member.name).filter(Boolean);
        },
        getShiftsByDate(date) {
            return this.shifts
                .filter((shift) => shift.date === date)
                .sort((left, right) =>
                    left.startTime.localeCompare(right.startTime),
                );
        },
        getDayModeRowCells(shift) {
            return buildDayModeCells([shift], (event) => event.id);
        },
    },
};
</script>

<style scoped>
.parttime-events-container {
    align-items: stretch;
}

.calendar-loading-cell {
    padding: 32px 16px !important;
}

.calendar-loading-wrap {
    min-height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.parttime-chip,
.day-mode-shift-chip {
    cursor: pointer;
    height: auto !important;
    min-height: 38px;
    justify-content: flex-start;
    padding: 7px 10px;
    border-radius: 18px;
}

.parttime-chip-content {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    line-height: 1.3;
    gap: 4px;
}

.parttime-chip-title {
    width: 100%;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    letter-spacing: 0.1px;
}

.parttime-member-list {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.parttime-member-item,
.parttime-member-more {
    width: 100%;
    font-size: 11px;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    opacity: 0.96;
}

.parttime-member-item::before {
    content: "• ";
}

.parttime-member-more {
    font-weight: 600;
    opacity: 0.9;
}

.shift-tooltip {
    min-width: 180px;
    max-width: 260px;
    padding: 4px 2px;
}

.shift-tooltip-title {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 4px;
}

.shift-tooltip-subtitle {
    font-size: 11px;
    opacity: 0.8;
    margin-bottom: 6px;
}

.shift-tooltip-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.shift-tooltip-item {
    font-size: 12px;
    line-height: 1.3;
    padding: 3px 8px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.12);
}

.shift-tooltip-item::before {
    content: "• ";
}

.empty-parttime-cell {
    padding-block: 32px !important;
    color: #757575;
}
</style>
