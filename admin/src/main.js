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

const vuetify = createVuetify({
    components,
    directives,
});

export const toast = useToast();

const app = createApp(App);

app.use(router);
app.use(store);
app.use(vuetify);
app.use(Toast);

app.mount("#app");
