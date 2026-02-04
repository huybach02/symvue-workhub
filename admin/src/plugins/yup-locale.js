import { setLocale } from "yup";
import { i18n } from "@/plugins/i18n"; // Đảm bảo đường dẫn import đúng

// Helper dịch nhanh
const trans = (key, values) => {
    return i18n.global.t(key, values);
};

export default function setupYupLocale() {
    setLocale({
        // 1. MIXED (Dùng chung)
        mixed: {
            required: ({ label }) =>
                trans("validation.mixed.required", { field: label }),
            default: ({ label }) =>
                trans("validation.mixed.default", { field: label }),
            oneOf: ({ label }) =>
                trans("validation.mixed.one_of", { field: label }),
            notType: ({ label }) =>
                trans("validation.mixed.default", { field: label }),
        },

        // 2. STRING
        string: {
            email: ({ label }) =>
                trans("validation.string.email", { field: label }),
            min: ({ label, min }) =>
                trans("validation.string.min", { field: label, min: min }),
            max: ({ label, max }) =>
                trans("validation.string.max", { field: label, max: max }),
            length: ({ label, length }) =>
                trans("validation.string.length", {
                    field: label,
                    length: length,
                }),
        },

        // 3. NUMBER
        number: {
            min: ({ label, min }) =>
                trans("validation.number.min", { field: label, min: min }),
            max: ({ label, max }) =>
                trans("validation.number.max", { field: label, max: max }),
            integer: ({ label }) =>
                trans("validation.number.integer", { field: label }),
        },

        // 4. DATE (Nếu dùng min/max mặc định của yup)
        date: {
            min: ({ label, min }) =>
                trans("validation.date.min", { field: label, min: min }),
            max: ({ label, max }) =>
                trans("validation.date.max", { field: label, max: max }),
        },

        // 5. ARRAY
        array: {
            min: ({ label, min }) =>
                trans("validation.array.min", { field: label, min: min }),
            max: ({ label, max }) =>
                trans("validation.array.max", { field: label, max: max }),
        },
    });
}
