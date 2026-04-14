import { createStore } from "vuex";
import auth from "./modules/auth";
import media from "./modules/media";
import mercure from "./modules/mercure";
import chat from "./modules/chat";
import workSchedule from "./modules/workSchedule";

const store = createStore({
    state() {
        return {
            isLoading: false,
        };
    },
    mutations: {
        setIsLoading(state) {
            state.isLoading = true;
        },
        unsetIsLoading(state) {
            state.isLoading = false;
        },
    },
    actions: {},
    modules: {
        auth,
        media,
        mercure,
        chat,
        workSchedule,
    },
});

export default store;
