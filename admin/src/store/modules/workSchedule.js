const state = {
    holidaySchedule: [],
};

const getters = {
    holidaySchedule: (state) => state.holidaySchedule,
};

const mutations = {
    SET_HOLIDAY_SCHEDULES(state, scheduleList) {
        state.holidaySchedule = scheduleList;
    },
};

const actions = {};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
