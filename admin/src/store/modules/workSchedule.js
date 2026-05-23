import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { functionHelper } from "@/helpers/functionHelper";
import {
    getDataById,
    getDataSelect,
    getAllData,
    getListData,
} from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
import { postData } from "@/services/bases/postData";

const getDefaultState = () => ({
    departments: [],
    departmentsLoading: false,
    membersByDepartment: {},
    holidaySchedule: [],
    fulltimeDataByDepartment: {},
    parttimeDataByDepartment: {},
    fulltimeLoading: false,
    parttimeLoading: false,
});

const state = getDefaultState();

const getters = {
    departments: (state) => state.departments,
    departmentsLoading: (state) => state.departmentsLoading,
    membersByDepartment: (state) => (departmentId) =>
        state.membersByDepartment[departmentId] ?? [],
    holidaySchedule: (state) => state.holidaySchedule,
    fulltimeDataByDepartment: (state) => (departmentId) =>
        state.fulltimeDataByDepartment[departmentId] ?? {},
    parttimeDataByDepartment: (state) => (departmentId) =>
        state.parttimeDataByDepartment[departmentId]?.data ?? { shifts: [] },
    parttimeCachedRangesByDepartment: (state) => (departmentId) =>
        state.parttimeDataByDepartment[departmentId]?.ranges ?? [],
    fulltimeLoading: (state) => state.fulltimeLoading,
    parttimeLoading: (state) => state.parttimeLoading,
};

const mutations = {
    SET_DEPARTMENTS(state, departments) {
        state.departments = departments;
    },
    SET_DEPARTMENTS_LOADING(state, value) {
        state.departmentsLoading = value;
    },
    SET_MEMBERS_BY_DEPARTMENT(state, { departmentId, members }) {
        state.membersByDepartment = {
            ...state.membersByDepartment,
            [departmentId]: members,
        };
    },
    SET_HOLIDAY_SCHEDULES(state, scheduleList) {
        state.holidaySchedule = scheduleList;
    },
    SET_FULLTIME_DATA(state, { departmentId, data }) {
        state.fulltimeDataByDepartment = {
            ...state.fulltimeDataByDepartment,
            [departmentId]: data,
        };
    },
    CLEAR_FULLTIME_DATA(state, departmentId) {
        state.fulltimeDataByDepartment = {
            ...state.fulltimeDataByDepartment,
            [departmentId]: {},
        };
    },
    MERGE_PARTTIME_DATA(state, { departmentId, data, range }) {
        const currentDepartmentData = state.parttimeDataByDepartment[
            departmentId
        ] ?? {
            data: { shifts: [] },
            ranges: [],
        };
        const shiftsByKey = new Map();
        const getShiftKey = (shift) =>
            [
                shift.id,
                shift.workShiftId,
                shift.date,
                shift.startTime,
                shift.endTime,
            ].join("-");

        (currentDepartmentData.data.shifts ?? []).forEach((shift) => {
            shiftsByKey.set(getShiftKey(shift), shift);
        });
        (data?.shifts ?? []).forEach((shift) => {
            shiftsByKey.set(getShiftKey(shift), shift);
        });

        state.parttimeDataByDepartment = {
            ...state.parttimeDataByDepartment,
            [departmentId]: {
                data: {
                    ...currentDepartmentData.data,
                    ...data,
                    shifts: [...shiftsByKey.values()],
                },
                ranges: functionHelper.mergeDateRanges([
                    ...currentDepartmentData.ranges,
                    range,
                ]),
            },
        };
    },
    CLEAR_PARTTIME_DATA(state, departmentId) {
        state.parttimeDataByDepartment = {
            ...state.parttimeDataByDepartment,
            [departmentId]: {
                data: { shifts: [] },
                ranges: [],
            },
        };
    },
    SET_FULLTIME_LOADING(state, value) {
        state.fulltimeLoading = value;
    },
    SET_PARTTIME_LOADING(state, value) {
        state.parttimeLoading = value;
    },
    RESET_STATE(state) {
        Object.assign(state, getDefaultState());
    },
};

const actions = {
    async fetchDepartments({ commit }) {
        commit("SET_DEPARTMENTS_LOADING", true);

        try {
            const departments =
                (await getDataSelect(API_ROUTES_CONFIG.department)) ?? [];
            commit("SET_DEPARTMENTS", departments);

            return departments;
        } finally {
            commit("SET_DEPARTMENTS_LOADING", false);
        }
    },
    async fetchMembersByDepartment({ commit }, departmentId) {
        if (!departmentId) {
            return [];
        }

        const members =
            (await getDataById(
                API_ROUTES_CONFIG.department,
                departmentId,
                "members",
            )) ?? [];
        commit("SET_MEMBERS_BY_DEPARTMENT", { departmentId, members });

        return members;
    },
    async fetchHolidaySchedule({ commit }) {
        const scheduleList =
            (await getListData(
                API_ROUTES_CONFIG.workSchedule + "/holiday-schedule",
            )) ?? [];
        commit("SET_HOLIDAY_SCHEDULES", scheduleList);

        return scheduleList;
    },
    async fetchFulltimeSchedule({ commit, state }, { departmentId, force = false }) {
        if (!departmentId) {
            commit("SET_FULLTIME_LOADING", false);
            return {};
        }

        const cachedData = state.fulltimeDataByDepartment[departmentId];
        if (!force && cachedData) {
            return cachedData;
        }

        commit("SET_FULLTIME_LOADING", true);

        try {
            const data =
                (await getListData(
                    `${API_ROUTES_CONFIG.workSchedule}/fulltime/${departmentId}`,
                )) ?? {};
            commit("SET_FULLTIME_DATA", { departmentId, data });

            return data;
        } finally {
            commit("SET_FULLTIME_LOADING", false);
        }
    },
    async createFulltimeSchedule(
        { dispatch },
        { departmentId, startDate, endDate, userIds },
    ) {
        await postData(API_ROUTES_CONFIG.workSchedule + "/fulltime", {
            startDate,
            endDate,
            userIds,
        });

        return dispatch("fetchFulltimeSchedule", {
            departmentId,
            force: true,
        });
    },
    async clearFulltimeSchedule({ dispatch }, { departmentId, userId }) {
        await postData(API_ROUTES_CONFIG.workSchedule + "/fulltime/clear", {
            departmentId,
            userId,
        });

        return dispatch("fetchFulltimeSchedule", {
            departmentId,
            force: true,
        });
    },
    async checkFulltimeOverride(_, { userId, startDate, endDate }) {
        return postData(
            API_ROUTES_CONFIG.workSchedule + "/fulltime/check-override",
            {
                userId,
                startDate,
                endDate,
            },
            () => {},
            true,
        );
    },
    async createFulltimeOverride(
        _,
        {
            userId,
            startDate,
            endDate,
            startTime,
            endTime,
            weekendOption,
            selectedDates,
        },
    ) {
        return postData(API_ROUTES_CONFIG.workSchedule + "/fulltime/override", {
            userId,
            startDate,
            endDate,
            startTime,
            endTime,
            weekendOption,
            selectedDates,
        });
    },
    async fetchSpecialDays(_, { startDate, endDate }) {
        return (
            (await getAllData(API_ROUTES_CONFIG.workSchedule + "/special-days", {
                startDate,
                endDate,
            })) ?? []
        );
    },
    async ensureParttimeShifts(
        { commit, state },
        { departmentId, startDate, endDate, force = false },
    ) {
        if (!departmentId) {
            commit("SET_PARTTIME_LOADING", false);
            return { shifts: [] };
        }

        const range = { startDate, endDate };
        const cachedRanges =
            state.parttimeDataByDepartment[departmentId]?.ranges ?? [];

        if (!force && functionHelper.isDateRangeCovered(range, cachedRanges)) {
            return state.parttimeDataByDepartment[departmentId]?.data ?? {
                shifts: [],
            };
        }

        commit("SET_PARTTIME_LOADING", true);

        try {
            const data =
                (await getListData(
                    `${API_ROUTES_CONFIG.workSchedule}/parttime/shifts`,
                    {
                        departmentId,
                        startDate,
                        endDate,
                    },
                )) ?? { shifts: [] };

            commit("MERGE_PARTTIME_DATA", {
                departmentId,
                data,
                range,
            });

            return state.parttimeDataByDepartment[departmentId]?.data ?? data;
        } finally {
            commit("SET_PARTTIME_LOADING", false);
        }
    },
    async fetchParttimeMembers(_, { shiftId, departmentId, date }) {
        return (
            (await getListData(
                `${API_ROUTES_CONFIG.workSchedule}/parttime/members`,
                {
                    shiftId,
                    departmentId,
                    date,
                },
            )) ?? {
                optionMembers: [],
                memberAssigneds: [],
            }
        );
    },
    async assignParttimeMembers(_, { workShiftId, userIds, date }) {
        return postData(`${API_ROUTES_CONFIG.workSchedule}/parttime/assign`, {
            workShiftId,
            userIds,
            date,
        });
    },
    async deleteParttimeAssignment(_, assignmentId) {
        return deleteData(
            `${API_ROUTES_CONFIG.workSchedule}/parttime/assign`,
            assignmentId,
        );
    },
    resetWorkScheduleState({ commit }) {
        commit("RESET_STATE");
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
