import { createI18n } from "vue-i18n";
import vi from "../locales/locale.vi.json";
import en from "../locales/locale.en.json";

const i18n = createI18n({
    legacy: true, // Bắt buộc true với Options API
    globalInjection: true, // Cho phép inject $t, $i18n vào tất cả component
    locale: localStorage.getItem("lang") || "en", // Lấy từ local storage hoặc mặc định
    fallbackLocale: "vi",
    messages: {
        vi,
        en,
    },
});

export default i18n;
