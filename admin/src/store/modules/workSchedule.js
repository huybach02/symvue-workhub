const state = {
    holidaySchedule: [],
    fulltimeLoading: false,
    parttimeLoading: false,
};

const getters = {
    holidaySchedule: (state) => state.holidaySchedule,
    fulltimeLoading: (state) => state.fulltimeLoading,
    parttimeLoading: (state) => state.parttimeLoading,
};

const mutations = {
    SET_HOLIDAY_SCHEDULES(state, scheduleList) {
        state.holidaySchedule = scheduleList;
    },
    SET_FULLTIME_LOADING(state, value) {
        state.fulltimeLoading = value;
    },
    SET_PARTTIME_LOADING(state, value) {
        state.parttimeLoading = value;
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
