import { createStore } from "vuex";
import auth from "./modules/auth";
import media from "./modules/media";
import mercure from "./modules/mercure";
import chat from "./modules/chat";
import workSchedule from "./modules/workSchedule";
import user from "./modules/user";
import generalSettings from "./modules/generalSettings";
import workingTime from "./modules/workingTime";
import department from "./modules/department";
import importHistory from "./modules/importHistory";
import notification from "./modules/notification";
import request from "./modules/request";
import attendance from "./modules/attendance";
import branch from "./modules/branch";
import category from "./modules/category";
import unit from "./modules/unit";
import provider from "./modules/provider";
import merchandise from "./modules/merchandise";
import businessProduct from "./modules/businessProduct";
import warehouse from "./modules/warehouse";
import stockReceipt from "./modules/stockReceipt";

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
        user,
        generalSettings,
        workingTime,
        department,
        importHistory,
        notification,
        request,
            attendance,
        branch,
        category,
        unit,
        provider,
        merchandise,
        businessProduct,
        warehouse,
        stockReceipt,
},
});

export default store;
