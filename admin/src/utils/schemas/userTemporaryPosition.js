import { buildNumberRule, buildStringRule } from "../validationBuilder";
import * as yup from "yup";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const userTemporaryPositionSchema = yup.object({
    departmentId: buildNumberRule(t("field.department"), {
        required: true,
        min: 1,
        integer: true,
    }),
    positionId: buildNumberRule(t("field.position"), {
        required: true,
        min: 1,
        integer: true,
    }),
    startTempDate: buildStringRule(t("field.start_temp_date"), {
        required: true,
        max: 50,
    }),
    startTempTime: buildStringRule(t("field.start_temp_time"), {
        required: true,
        max: 10,
    }),
    endTempDate: buildStringRule(t("field.end_temp_date"), {
        required: true,
        max: 50,
    }),
    endTempTime: buildStringRule(t("field.end_temp_time"), {
        required: true,
        max: 10,
    }),
});
