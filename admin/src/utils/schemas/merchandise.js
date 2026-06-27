import * as yup from "yup";
import {
    buildStringRule,
    buildNumberRule,
    buildPercentageRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const merchandiseSchema = yup.object({
    code: buildStringRule(t("field.merchandise_code"), {
        required: true,
        min: 3,
        max: 255,
    }),
    name: buildStringRule(t("field.merchandise_name"), {
        required: true,
        min: 3,
        max: 255,
    }),
    categoryId: yup.mixed().nullable().notRequired(),
    profit: buildPercentageRule(t("field.merchandise_profit"), {
        required: false,
    }),
    stockAlertQuantity: buildNumberRule(
        t("field.merchandise_stock_alert_quantity"),
        {
            required: false,
            min: 0,
        },
    ),
    description: buildStringRule(t("field.merchandise_description"), {
        required: false,
        max: 500,
    }),
    notes: buildStringRule(t("field.merchandise_notes"), {
        required: false,
        max: 500,
    }),
    status: yup.number().required().oneOf([0, 1]),
});
