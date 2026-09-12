import * as yup from "yup";
import {
    buildStringRule,
    buildEmailRule,
    buildPhoneRule,
    buildImageRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const branchSchema = yup.object({
    name: buildStringRule(t("field.name"), {
        required: true,
        min: 3,
        max: 255,
    }),

    email: buildEmailRule(t("field.email"), {
        required: true,
    }),

    phone: buildPhoneRule(t("field.so_dien_thoai"), {
        required: true,
    }),

    address: buildStringRule(t("field.dia_chi"), {
        required: false,
        max: 500,
    }),

    status: buildStringRule(t("field.trang_thai"), {
        required: false,
    }),

    note: buildStringRule(t("field.ghi_chu"), {
        required: false,
        max: 500,
    }),

    image: buildImageRule(t("field.image"), {
        required: false,
    }),
});
