<template>
    <v-container fluid class="calendar-container pa-0">
        <v-toolbar flat color="white" class="border-bottom calendar-toolbar">
            <v-btn icon @click="changeDate(-1)">
                <v-icon>mdi-chevron-left</v-icon>
            </v-btn>

            <v-toolbar-title
                class="font-weight-bold text-h6"
                style="min-width: 200px; text-align: center"
            >
                {{ currentLabel }}
            </v-toolbar-title>

            <v-btn icon @click="changeDate(1)">
                <v-icon>mdi-chevron-right</v-icon>
            </v-btn>

            <v-btn
                class="ml-4"
                variant="outlined"
                size="small"
                @click="goToToday"
            >
                Hôm nay
            </v-btn>

            <v-spacer></v-spacer>

            <v-btn-toggle
                v-model="currentMode"
                mandatory
                color="primary"
                variant="outlined"
                density="compact"
            >
                <v-btn value="day">Ngày</v-btn>
                <v-btn value="week">Tuần</v-btn>
                <v-btn value="month">Tháng</v-btn>
            </v-btn-toggle>
        </v-toolbar>

        <div class="calendar-legend">
            <div class="legend-item">
                <span class="legend-color legend-color-normal"></span>
                <span>Ngày thường</span>
            </div>
            <div class="legend-item">
                <span class="legend-color legend-color-weekend"></span>
                <span>Thứ 7, Chủ nhật</span>
            </div>
            <div class="legend-item">
                <span class="legend-color legend-color-holiday"></span>
                <span>Ngày nghỉ lễ/Tết</span>
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
                    <th class="sticky-col-left column-header header-custom">
                        Nhân sự
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
                        {{ col.label }}
                    </th>
                </tr>
            </thead>

            <tbody v-if="currentMode !== 'day'">
                <tr v-for="user in users" :key="user.id">
                    <td class="sticky-col-left user-cell">
                        <div class="d-flex align-center justify-space-between">
                            <v-list-item
                                :title="user.name"
                                class="px-0 flex-grow-1"
                            >
                                <template #prepend>
                                    <v-avatar
                                        v-if="user.image"
                                        :image="user.image"
                                    />
                                    <v-avatar v-else icon="mdi-account" />
                                </template>
                            </v-list-item>
                            <v-menu
                                v-if="hasUserFixedSchedule(user)"
                                :close-on-content-click="true"
                                location="top"
                            >
                                <template #activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        icon="mdi-dots-vertical"
                                        variant="text"
                                        density="compact"
                                        @click="selectedUser = user"
                                    />
                                </template>
                                <v-list density="compact">
                                    <v-list-item
                                        prepend-icon="mdi-calendar-plus"
                                        title="Thêm lịch thay thế"
                                        @click="handleAddOverride(user)"
                                    />
                                    <v-list-item
                                        prepend-icon="mdi-calendar-remove"
                                        title="Xóa lịch làm việc"
                                        class="text-error"
                                        @click="handleClearSchedule(user)"
                                    />
                                </v-list>
                            </v-menu>
                        </div>
                    </td>

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
                        <div class="events-container">
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

                            <v-chip
                                v-for="event in getEvents(user.id, col.value)"
                                :key="event.id"
                                :color="event.color"
                                size="small"
                                class="ma-1 w-100 justify-center font-weight-bold"
                                variant="flat"
                            >
                                {{ event.title }}
                            </v-chip>
                        </div>
                    </td>
                </tr>
            </tbody>

            <tbody v-else>
                <tr v-for="user in users" :key="user.id">
                    <td class="sticky-col-left user-cell">
                        <div class="d-flex align-center justify-space-between">
                            <v-list-item
                                :title="user.name"
                                class="px-0 flex-grow-1"
                            >
                                <template #prepend>
                                    <v-avatar
                                        v-if="user.image"
                                        :image="user.image"
                                    />
                                    <v-avatar v-else icon="mdi-account" />
                                </template>
                            </v-list-item>
                            <v-menu
                                v-if="hasUserFixedSchedule(user)"
                                :close-on-content-click="true"
                                location="top"
                            >
                                <template #activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        icon="mdi-dots-vertical"
                                        variant="text"
                                        density="compact"
                                        @click="selectedUser = user"
                                    />
                                </template>
                                <v-list density="compact">
                                    <v-list-item
                                        v-if="hasUserFixedSchedule(user)"
                                        prepend-icon="mdi-calendar-plus"
                                        title="Thêm lịch thay thế"
                                        @click="handleAddOverride(user)"
                                    />
                                    <v-list-item
                                        prepend-icon="mdi-calendar-remove"
                                        title="Xóa lịch làm việc"
                                        class="text-error"
                                        @click="handleClearSchedule(user)"
                                    />
                                </v-list>
                            </v-menu>
                        </div>
                    </td>

                    <td
                        v-for="cell in getDayModeRowCells(user.id)"
                        :key="cell.key"
                        class="calendar-cell text-center day-mode-cell"
                        :colspan="cell.colspan"
                    >
                        <div v-if="cell.event" class="day-mode-event-wrapper">
                            <v-chip
                                :color="cell.event.color"
                                size="small"
                                class="w-100 justify-center font-weight-bold day-mode-chip"
                                variant="flat"
                            >
                                {{ cell.event.title }}
                            </v-chip>
                        </div>
                    </td>
                </tr>
            </tbody>
        </v-table>
    </v-container>
</template>

<script>
import dayjs from "dayjs";
import "dayjs/locale/vi";
import updateLocale from "dayjs/plugin/updateLocale";

const DAY_MODE_START_HOUR = 0;
const DAY_MODE_END_HOUR = 23;

// Cấu hình Dayjs để tuần bắt đầu vào Thứ 2
dayjs.extend(updateLocale);
dayjs.updateLocale("vi", {
    weekStart: 1,
});
dayjs.locale("vi");

export default {
    name: "ResourceCalendar",
    props: {
        dataCalendar: {
            type: Array,
            default: () => [],
        },
        type: {
            type: String,
            default: "fulltime",
        },
    },
    emits: ["addOverride", "clearSchedule", "userSelected"],
    data() {
        return {
            currentMode: "week", // 'day', 'week', 'month'
            baseDate: dayjs(), // Mốc thời gian hiện tại đang xem
            selectedUser: null, // User đang mở dropdown menu
        };
    },

    computed: {
        users() {
            return this.dataCalendar?.users ?? [];
        },

        events() {
            return (
                this.dataCalendar?.events.filter(
                    (event) => event.startTime && event.endTime,
                ) ?? []
            );
        },

        // Tiêu đề hiển thị
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

        // Logic sinh cột & Xác định ngày hôm nay
        calendarColumns() {
            const columns = [];
            const today = dayjs(); // Lấy ngày thực tế của hệ thống

            if (this.currentMode === "month") {
                const daysInMonth = this.baseDate.daysInMonth();
                const startOfMonth = this.baseDate.startOf("month");

                for (let i = 0; i < daysInMonth; i++) {
                    const currentDay = startOfMonth.add(i, "day");
                    const holiday = this.getHolidayInfo(currentDay);
                    columns.push({
                        label: this.formatWeekdayLabel(currentDay),
                        value: currentDay.format("YYYY-MM-DD"),
                        isWeekend:
                            currentDay.day() === 0 || currentDay.day() === 6,
                        isHoliday: !!holiday,
                        holidayName: holiday?.name ?? "",
                        isToday: currentDay.isSame(today, "day"), // So sánh có phải hôm nay không
                    });
                }
            } else if (this.currentMode === "week") {
                const startOfWeek = this.baseDate.startOf("week");

                for (let i = 0; i < 7; i++) {
                    const currentDay = startOfWeek.add(i, "day");
                    const holiday = this.getHolidayInfo(currentDay);
                    columns.push({
                        label: this.formatWeekdayLabel(currentDay),
                        value: currentDay.format("YYYY-MM-DD"),
                        isWeekend:
                            currentDay.day() === 0 || currentDay.day() === 6,
                        isHoliday: !!holiday,
                        holidayName: holiday?.name ?? "",
                        isToday: currentDay.isSame(today, "day"), // So sánh có phải hôm nay không
                    });
                }
            } else if (this.currentMode === "day") {
                for (let i = DAY_MODE_START_HOUR; i <= DAY_MODE_END_HOUR; i++) {
                    const hourStr = i < 10 ? `0${i}:00` : `${i}:00`;
                    columns.push({
                        label: `${i}:00`,
                        value: hourStr,
                        isWeekend: false,
                        isHoliday: false,
                        holidayName: "",
                        isToday: false, // Ở Mode ngày (hiển thị giờ) không cần highlight cả cột
                    });
                }
            }

            return columns;
        },
    },

    methods: {
        hasUserFixedSchedule(user) {
            return this.dataCalendar?.events?.some(
                (event) =>
                    event.source === "fixed_schedule" &&
                    event.user_id === user.id,
            );
        },
        getHolidayInfo(date) {
            const dateValue = date.format("YYYY-MM-DD");
            const year = date.year();
            const holidayRanges =
                this.$store.getters["workSchedule/holidaySchedule"][year] ?? [];
            const matchedHoliday = holidayRanges.find(
                (holiday) =>
                    dateValue >= holiday.start && dateValue <= holiday.end,
            );

            if (matchedHoliday) {
                return matchedHoliday;
            }
        },

        formatWeekdayLabel(date) {
            const weekday = date.format("dddd");
            const capitalizedWeekday =
                weekday.charAt(0).toUpperCase() + weekday.slice(1);

            return `${capitalizedWeekday} (${date.format("DD/MM")})`;
        },

        getDayModeEvents(userId) {
            return this.events
                .filter(
                    (event) =>
                        event.user_id === userId &&
                        event.date === this.baseDate.format("YYYY-MM-DD"),
                )
                .sort((left, right) =>
                    left.startTime.localeCompare(right.startTime),
                );
        },

        getEventSpanHours(event) {
            const startHour = Number(event.startTime.split(":")[0]);
            const endHour = Number(
                (event.endTime || event.startTime).split(":")[0],
            );

            return Math.max(1, endHour - startHour);
        },

        getDayModeRowCells(userId) {
            const cells = [];
            const events = this.getDayModeEvents(userId);
            let currentHour = DAY_MODE_START_HOUR;

            events.forEach((event) => {
                const startHour = Number(event.startTime.split(":")[0]);
                const spanHours = this.getEventSpanHours(event);

                if (startHour > currentHour) {
                    cells.push({
                        key: `empty-${userId}-${currentHour}`,
                        colspan: startHour - currentHour,
                        event: null,
                    });
                }

                cells.push({
                    key: `event-${event.id}`,
                    colspan: spanHours,
                    event,
                });

                currentHour = startHour + spanHours;
            });

            if (currentHour <= DAY_MODE_END_HOUR) {
                cells.push({
                    key: `empty-${userId}-${currentHour}-end`,
                    colspan: DAY_MODE_END_HOUR + 1 - currentHour,
                    event: null,
                });
            }

            return cells;
        },

        getEvents(userId, colValue) {
            return this.events.filter((event) => {
                if (event.user_id !== userId) return false;

                if (
                    this.currentMode === "month" ||
                    this.currentMode === "week"
                ) {
                    return event.date === colValue;
                }
            });
        },

        changeDate(amount) {
            this.baseDate = this.baseDate.add(amount, this.currentMode);
        },

        goToToday() {
            this.baseDate = dayjs();
            this.scrollTodayIntoView();
        },

        scrollTodayIntoView() {
            if (this.currentMode !== "month") return;

            this.$nextTick(() => {
                const tableRef = this.$refs.calendarTable;
                const tableElement = tableRef?.$el || tableRef;
                const wrapper =
                    tableElement?.querySelector(".v-table__wrapper");
                const todayColumn = tableElement?.querySelector(
                    `th[data-date="${dayjs().format("YYYY-MM-DD")}"]`,
                );

                if (!wrapper || !todayColumn) return;

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

.column-header {
    min-width: 120px;
    border-right: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
}

.calendar-cell {
    border-right: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
    padding: 8px !important;
}

.day-mode-cell {
    padding: 6px !important;
}

.weekend-cell {
    background-color: #fff3e0 !important;
}

.holiday-cell {
    background-color: #ffebee !important;
}

.holiday-tag {
    max-width: 100%;
}

.events-container {
    min-height: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.day-mode-event-wrapper {
    min-height: 40px;
    display: flex;
    align-items: center;
}

.day-mode-chip {
    min-height: 32px;
}

/* --- Sticky Column --- */
.sticky-col-left {
    position: sticky;
    left: 0;
    background-color: white;
    z-index: 2;
    border-right: 2px solid #bdbdbd !important;
}

thead th.sticky-col-left {
    z-index: 3;
}

.user-cell {
    min-width: 250px;
    border-right: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
    padding: 8px 16px !important;
}

.today-cell {
    box-shadow:
        inset 2px 0 0 #f44336,
        inset -2px 0 0 #f44336;
}

/* Fix viền dưới cùng cho ô today-cell cuối cùng (nếu cần) */
tbody tr:last-child .today-cell {
    border-bottom: 2px solid #f44336 !important;
}
</style>
