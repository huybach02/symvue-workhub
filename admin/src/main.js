import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import store from "@/store/index.js";
import "vuetify/styles";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import Toast, { useToast } from "vue-toastification";
import "vue-toastification/dist/index.css";
import i18n from "./plugins/i18n";
import setupYupLocale from "./plugins/yup-locale";

const vuetify = createVuetify({
    components,
    directives,
});

export const toast = useToast();

setupYupLocale();

const app = createApp(App);

app.use(router);
app.use(store);
app.use(vuetify);
app.use(Toast);
app.use(i18n);

app.mount("#app");
