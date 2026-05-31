<template>
    <v-container fluid class="calendar-container pa-0">
        <v-toolbar flat color="white" class="border-bottom calendar-toolbar">
            <v-btn icon @click="changeDate(-1)">
                <v-icon>mdi-chevron-left</v-icon>
            </v-btn>

            <div class="calendar-title-group">
                <v-toolbar-title
                    class="font-weight-bold text-h6 calendar-title"
                >
                    {{ currentLabel }}
                </v-toolbar-title>

                <DatePicker
                    :model-value="baseDate.format('YYYY-MM-DD')"
                    placeholder="Chọn ngày"
                    icon-only
                    icon="mdi-calendar-month-outline"
                    icon-variant="text"
                    density="compact"
                    class="calendar-picker-trigger"
                    @update:model-value="handleDatePicked"
                />
            </div>

            <v-btn icon @click="changeDate(1)">
                <v-icon>mdi-chevron-right</v-icon>
            </v-btn>

            <v-btn
                class="ml-4"
                variant="outlined"
                size="small"
                @click="goToToday"
            >
                {{ $t("calendar.today") }}
            </v-btn>

            <v-spacer></v-spacer>

            <v-btn-toggle
                v-model="currentMode"
                mandatory
                color="primary"
                variant="outlined"
                density="compact"
            >
                <v-btn value="day">{{ $t("calendar.day") }}</v-btn>
                <v-btn value="week">{{ $t("calendar.week") }}</v-btn>
                <v-btn value="month">{{ $t("calendar.month") }}</v-btn>
            </v-btn-toggle>
        </v-toolbar>

        <div class="calendar-legend">
            <div class="legend-item">
                <span class="legend-color legend-color-normal"></span>
                <span>{{ $t("calendar.normal_day") }}</span>
            </div>
            <div class="legend-item">
                <span class="legend-color legend-color-weekend"></span>
                <span>{{ $t("calendar.weekend") }}</span>
            </div>
            <div class="legend-item">
                <span class="legend-color legend-color-holiday"></span>
                <span>{{ $t("calendar.holiday") }}</span>
            </div>
        </div>

        <v-table
            ref="calendarTable"
            fixed-header
            height="650px"
            density="comfortable"
            class="calendar-table"
        >
            <thead class="header-custom">
                <tr>
                    <th
                        v-if="showPersonnelColumn"
                        class="sticky-col-left column-header header-custom personnel-header"
                    >
                        {{ $t("calendar.personnel") }}
                    </th>

                    <th
                        v-for="col in calendarColumns"
                        :key="col.value"
                        :data-date="col.value"
                        :title="col.holidayName || null"
                        class="text-center column-header header-custom"
                        :class="{
                            'text-primary font-weight-bold today-header':
                                col.isToday,
                        }"
                    >
                        <div
                            v-if="col.weekdayLabel && col.dateLabel"
                            class="calendar-header-label"
                        >
                            <div class="calendar-header-weekday">
                                {{ col.weekdayLabel }}
                            </div>
                            <div class="calendar-header-date">
                                {{ col.dateLabel }}
                            </div>
                        </div>
                        <template v-else>
                            {{ col.label }}
                        </template>
                    </th>
                </tr>
            </thead>

            <CalendarFulltimeBody
                v-if="isFulltime"
                :users="users"
                :events="events"
                :calendar-columns="calendarColumns"
                :current-mode="currentMode"
                :base-date="baseDate"
                @add-override="handleAddOverride"
                @clear-schedule="handleClearSchedule"
            />

            <CalendarParttimeBody
                v-else
                :shifts="shifts"
                :calendar-columns="calendarColumns"
                :current-mode="currentMode"
                :base-date="baseDate"
                @shift-selected="handleShiftSelected"
            />
        </v-table>
    </v-container>
</template>

<script>
import dayjs from "dayjs";
import "dayjs/locale/vi";
import updateLocale from "dayjs/plugin/updateLocale";
import DatePicker from "@/components/DatePicker.vue";
import CalendarFulltimeBody from "./calendar/CalendarFulltimeBody.vue";
import CalendarParttimeBody from "./calendar/CalendarParttimeBody.vue";
import {
    DAY_MODE_END_HOUR,
    DAY_MODE_START_HOUR,
} from "./calendar/calendarShared";

dayjs.extend(updateLocale);
dayjs.updateLocale("vi", {
    weekStart: 1,
});
dayjs.locale("vi");

export default {
    name: "ResourceCalendar",
    components: {
        DatePicker,
        CalendarFulltimeBody,
        CalendarParttimeBody,
    },
    props: {
        dataCalendar: {
            type: Object,
            default: () => ({}),
        },
        type: {
            type: String,
            default: "fulltime",
        },
    },
    emits: [
        "addOverride",
        "clearSchedule",
        "userSelected",
        "shiftSelected",
        "dateRangeChange",
    ],
    data() {
        return {
            currentMode: "week",
            baseDate: dayjs(),
        };
    },
    computed: {
        isFulltime() {
            return this.type === "fulltime";
        },
        showPersonnelColumn() {
            return this.isFulltime;
        },
        loading() {
            return this.isFulltime
                ? this.$store.getters["workSchedule/fulltimeLoading"]
                : this.$store.getters["workSchedule/parttimeLoading"];
        },
        users() {
            return this.dataCalendar?.users ?? [];
        },
        events() {
            const events = this.dataCalendar?.events ?? [];

            if (this.currentMode === "day") {
                return events.filter(
                    (event) => event.startTime && event.endTime,
                );
            }

            return events;
        },
        shifts() {
            return (
                this.dataCalendar?.shifts?.filter(
                    (shift) => shift.startTime && shift.endTime,
                ) ?? []
            );
        },
        currentLabel() {
            if (this.currentMode === "day") {
                const weekday = this.baseDate.format("dddd");
                const capitalizedWeekday =
                    weekday.charAt(0).toUpperCase() + weekday.slice(1);

                return `${capitalizedWeekday} (${this.baseDate.format("DD/MM/YYYY")})`;
            }

            if (this.currentMode === "week") {
                const start = this.baseDate
                    .startOf("week")
                    .format("DD/MM/YYYY");
                const end = this.baseDate.endOf("week").format("DD/MM/YYYY");

                return `Tuần: ${start} - ${end}`;
            }

            return `Tháng ${this.baseDate.format("MM / YYYY")}`;
        },
        calendarColumns() {
            const columns = [];
            const today = dayjs();

            if (this.currentMode === "month") {
                const daysInMonth = this.baseDate.daysInMonth();
                const startOfMonth = this.baseDate.startOf("month");

                for (let i = 0; i < daysInMonth; i++) {
                    const currentDay = startOfMonth.add(i, "day");
                    columns.push(this.buildDateColumn(currentDay, today));
                }

                return columns;
            }

            if (this.currentMode === "week") {
                const startOfWeek = this.baseDate.startOf("week");

                for (let i = 0; i < 7; i++) {
                    const currentDay = startOfWeek.add(i, "day");
                    columns.push(this.buildDateColumn(currentDay, today));
                }

                return columns;
            }

            for (let i = DAY_MODE_START_HOUR; i <= DAY_MODE_END_HOUR; i++) {
                const hourStr = i < 10 ? `0${i}:00` : `${i}:00`;
                columns.push({
                    label: `${i}:00`,
                    value: hourStr,
                    isWeekend: false,
                    isHoliday: false,
                    holidayName: "",
                    isToday: false,
                });
            }

            return columns;
        },
    },
    watch: {
        currentMode() {
            this.emitDateRangeChange();
        },
        baseDate() {
            this.emitDateRangeChange();
        },
    },
    mounted() {
        this.emitDateRangeChange();
    },
    methods: {
        buildDateColumn(currentDay, today) {
            const holiday = this.getHolidayInfo(currentDay);

            return {
                label: this.formatWeekdayLabel(currentDay),
                weekdayLabel: this.getWeekdayDisplay(currentDay),
                dateLabel: `(${currentDay.format("DD/MM")})`,
                value: currentDay.format("YYYY-MM-DD"),
                isWeekend: currentDay.day() === 0 || currentDay.day() === 6,
                isHoliday: Boolean(holiday),
                holidayName: holiday?.name ?? "",
                isToday: currentDay.isSame(today, "day"),
            };
        },
        getHolidayInfo(date) {
            const dateValue = date.format("YYYY-MM-DD");
            const year = date.year();
            const holidayRanges =
                this.$store.getters["workSchedule/holidaySchedule"][year] ?? [];

            return holidayRanges.find(
                (holiday) =>
                    dateValue >= holiday.start && dateValue <= holiday.end,
            );
        },
        formatWeekdayLabel(date) {
            return `${this.getWeekdayDisplay(date)} (${date.format("DD/MM")})`;
        },
        getWeekdayDisplay(date) {
            const weekday = date.format("dddd");
            return weekday.charAt(0).toUpperCase() + weekday.slice(1);
        },
        getVisibleDateRange() {
            if (this.currentMode === "day") {
                return {
                    baseDate: this.baseDate.format("YYYY-MM-DD"),
                    startDate: this.baseDate.format("YYYY-MM-DD"),
                    endDate: this.baseDate.format("YYYY-MM-DD"),
                    mode: this.currentMode,
                };
            }

            if (this.currentMode === "week") {
                return {
                    baseDate: this.baseDate.format("YYYY-MM-DD"),
                    startDate: this.baseDate
                        .startOf("week")
                        .format("YYYY-MM-DD"),
                    endDate: this.baseDate.endOf("week").format("YYYY-MM-DD"),
                    mode: this.currentMode,
                };
            }

            return {
                baseDate: this.baseDate.format("YYYY-MM-DD"),
                startDate: this.baseDate.startOf("month").format("YYYY-MM-DD"),
                endDate: this.baseDate.endOf("month").format("YYYY-MM-DD"),
                mode: this.currentMode,
            };
        },
        emitDateRangeChange() {
            this.$emit("dateRangeChange", this.getVisibleDateRange());
        },
        changeDate(amount) {
            this.baseDate = this.baseDate.add(amount, this.currentMode);
            this.scrollTodayIntoView();
        },
        goToToday() {
            this.baseDate = dayjs();
            this.scrollTodayIntoView();
        },
        handleDatePicked(value) {
            const normalizedDate = dayjs(value);

            if (!normalizedDate.isValid()) {
                return;
            }

            this.baseDate = normalizedDate;
            this.scrollTodayIntoView();
        },
        scrollTodayIntoView() {
            if (this.currentMode !== "month") {
                return;
            }

            this.$nextTick(() => {
                const tableRef = this.$refs.calendarTable;
                const tableElement = tableRef?.$el || tableRef;
                const wrapper =
                    tableElement?.querySelector(".v-table__wrapper");
                const todayColumn = tableElement?.querySelector(
                    `th[data-date="${dayjs().format("YYYY-MM-DD")}"]`,
                );

                if (!wrapper || !todayColumn) {
                    return;
                }

                const stickyColumnWidth =
                    tableElement.querySelector(".sticky-col-left")
                        ?.offsetWidth || 0;
                const targetScrollLeft =
                    todayColumn.offsetLeft - stickyColumnWidth - 100;

                wrapper.scrollTo({
                    left: Math.max(0, targetScrollLeft),
                    behavior: "smooth",
                });
            });
        },
        handleClearSchedule(user) {
            this.$emit("clearSchedule", user);
            this.$emit("userSelected", user);
        },
        handleAddOverride(user) {
            this.$emit("addOverride", user);
            this.$emit("userSelected", user);
        },
        handleShiftSelected(shift) {
            this.$emit("shiftSelected", shift);
        },
    },
};
</script>

<style scoped>
.calendar-container {
    padding-top: 0 !important;
}

.calendar-toolbar {
    padding-inline: 0 !important;
    min-height: 56px !important;
}

.calendar-title-group {
    min-width: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.calendar-title {
    min-width: 200px;
    text-align: center;
}

.calendar-picker-trigger {
    flex-shrink: 0;
}

.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 16px;
    padding: 8px 0 12px;
    font-size: 13px;
    color: #4f4f4f;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-color {
    width: 14px;
    height: 14px;
    border: 1px solid #d0d0d0;
    border-radius: 3px;
    display: inline-block;
}

.legend-color-normal {
    background-color: #ffffff;
}

.legend-color-weekend {
    background-color: #fff3e0;
}

.legend-color-holiday {
    background-color: #ffebee;
}

.calendar-table {
    border: 1px solid #e0e0e0;
}

:deep(.calendar-table table) {
    border-collapse: separate;
    border-spacing: 0;
}

:deep(.column-header) {
    min-width: 120px;
    border-right: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
}

:deep(.calendar-cell) {
    border-right: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
    padding: 8px !important;
}

:deep(.day-mode-cell) {
    padding: 6px !important;
}

:deep(.weekend-cell) {
    background-color: #fff3e0 !important;
}

:deep(.holiday-cell) {
    background-color: #ffebee !important;
}

:deep(.holiday-tag) {
    max-width: 100%;
}

:deep(.events-container) {
    min-height: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

:deep(.day-mode-event-wrapper) {
    min-height: 40px;
    display: flex;
    align-items: center;
}

:deep(.day-mode-chip) {
    min-height: 32px;
}

:deep(.sticky-col-left) {
    position: sticky;
    left: 0;
    background-color: white;
    background-clip: padding-box;
    z-index: 4;
    border-right: 2px solid #bdbdbd !important;
}

:deep(thead th.sticky-col-left) {
    z-index: 6;
}

:deep(.user-cell) {
    width: 250px;
    min-width: 250px;
    max-width: 250px;
    border-right: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
    padding: 8px 16px !important;
}

:deep(th.personnel-header) {
    width: 250px;
    min-width: 250px;
    max-width: 250px;
}

:deep(.today-cell) {
    box-shadow:
        inset 2px 0 0 #f44336,
        inset -2px 0 0 #f44336;
}

:deep(tbody tr:last-child .today-cell) {
    border-bottom: 2px solid #f44336 !important;
}

.personnel-header {
    background-color: #1da2ac !important;
}

.calendar-header-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    min-height: 48px;
    line-height: 1.2;
}

.calendar-header-weekday,
.calendar-header-date {
    display: block;
    width: 100%;
    white-space: normal;
    text-align: center;
}
</style>
