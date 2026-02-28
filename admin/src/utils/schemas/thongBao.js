import * as yup from "yup";
import { buildStringRule, buildConditionalRule } from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key, params) => i18n.global.t(key, params);

export const thongBaoSchema = yup.object({
    sendTo: buildStringRule(t("field.send_to"), {
        required: true,
    }),

    // Bắt buộc chọn khi sendTo = 'department'
    departmentId: buildConditionalRule(
        t("field.bo_phan"),
        "sendTo",
        "department",
    ),

    // Bắt buộc chọn khi sendTo = 'user'
    userId: buildConditionalRule(t("field.nguoi_nhan"), "sendTo", "user"),

    type: buildStringRule(t("field.type"), {
        required: true,
    }),

    title: buildStringRule(t("field.title"), {
        required: true,
    }),

    body: buildStringRule(t("field.body"), {
        required: true,
    }),
});
