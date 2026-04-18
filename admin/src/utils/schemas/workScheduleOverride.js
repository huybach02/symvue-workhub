import {
    buildDateRule,
    buildTimeRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";
import * as yup from "yup";

const t = (key) => i18n.global.t(key);

export const workScheduleOverrideSchema = yup.object({
    startDate: buildDateRule(t("field.ngay_bat_dau"), {
        required: true,
    }),
    endDate: buildDateRule(t("field.ngay_ket_thuc"), {
        required: true,
    }),
    startTime: buildTimeRule(t("field.thoi_gian_bat_dau"), {
        required: true,
        includeSeconds: false,
    }),
    endTime: buildTimeRule(t("field.thoi_gian_ket_thuc"), {
        required: true,
        includeSeconds: false,
    }),
});
