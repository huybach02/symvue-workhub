import * as yup from "yup";
import { buildStringRule, buildNumberRule } from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const unitSchema = yup.object({
    name: buildStringRule(t("field.name"), {
        required: true,
        min: 3,
        max: 255,
    }),

    code: buildStringRule(t("unit.columns.code"), {
        required: true,
        max: 255,
    }),

    symbol: buildStringRule(t("unit.columns.symbol"), {
        required: false,
        max: 255,
    }),

    status: buildNumberRule(t("field.trang_thai"), {
        required: true,
    }),
});
