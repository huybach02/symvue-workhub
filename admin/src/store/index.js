import { createStore } from "vuex";
import auth from "./modules/auth";
import media from "./modules/media";
import mercure from "./modules/mercure";

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
    },
});

export default store;
