import { createI18n } from "vue-i18n";
import vi from "../locales/locale.vi.json";
import en from "../locales/locale.en.json";
import validateVi from "../locales/validate/validate.vi.json";
import validateEn from "../locales/validate/validate.en.json";
import fieldVi from "../locales/field/field.vi.json";
import fieldEn from "../locales/field/field.en.json";
import baseVi from "../locales/base/base.vi.json";
import baseEn from "../locales/base/base.en.json";

const i18n = createI18n({
    legacy: true, // Bắt buộc true với Options API
    globalInjection: true, // Cho phép inject $t, $i18n vào tất cả component
    locale: localStorage.getItem("lang") || "en", // Lấy từ local storage hoặc mặc định
    fallbackLocale: "vi",
    messages: {
        vi: {
            ...vi,
            ...validateVi,
            ...fieldVi,
            ...baseVi,
        },
        en: {
            ...en,
            ...validateEn,
            ...fieldEn,
            ...baseEn,
        },
    },
});

export default i18n;
export { i18n };
