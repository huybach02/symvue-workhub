import {
    buildCurrencyRule,
    buildDateRule,
    buildNumberRule,
    buildStringRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";
import * as yup from "yup";

const t = (key) => i18n.global.t(key);

export const userPositionSchema = yup.object({
    departmentId: buildNumberRule(t("field.bo_phan"), {
        required: true,
        min: 1,
        integer: true,
    }),
    positionId: buildNumberRule(t("field.chuc_vu"), {
        required: true,
        min: 1,
        integer: true,
    }),
    salary: buildCurrencyRule("Lương thỏa thuận", {
        required: true,
        min: 0,
    }),
    insuranceSalary: buildCurrencyRule("Lương đóng bảo hiểm", {
        required: true,
        min: 0,
    }),
    insuranceCode: buildStringRule("Mã số bảo hiểm", {
        required: true,
        max: 255,
    }),
    effectiveFrom: buildDateRule("Ngày hiệu lực", {
        required: true,
    }),
    effectiveTo: buildDateRule("Ngày hết hiệu lực", {
        required: true,
    }),
    probationFrom: buildDateRule("Ngày bắt đầu thử việc", {
        required: true,
    }),
    probationTo: buildDateRule("Ngày kết thúc thử việc", {
        required: true,
    }),
    note: buildStringRule(t("field.ghi_chu"), {
        required: false,
        max: 5000,
    }),
});
