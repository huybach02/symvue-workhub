import * as yup from "yup";
import {
    buildCurrencyRule,
    buildGreaterThanFieldNumberRule,
    buildNumberRule,
    buildSelectRule,
    buildStringRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const positionSchema = yup.object({
    name: buildStringRule(t("field.ten_chuc_vu"), {
        required: true,
        min: 2,
        max: 255,
    }),
    code: buildStringRule(t("field.ma_chuc_vu"), {
        required: true,
        max: 255,
    }),
    employmentType: buildSelectRule(
        t("field.hinh_thuc_lam_viec"),
        ["FULL_TIME", "PART_TIME", "INTERN", "CONTRACTOR"],
        { required: true },
    ),
    minSalary: buildCurrencyRule(t("field.luong_toi_thieu"), {
        required: true,
        min: 0,
    }),
    maxSalary: buildGreaterThanFieldNumberRule(
        t("field.luong_toi_da"),
        "minSalary",
        t("field.luong_toi_thieu"),
        {
            required: true,
            min: 0,
        },
    ),
    currency: buildStringRule(t("field.tien_te"), {
        required: true,
        max: 20,
    }),
    probationMonths: buildNumberRule(t("field.so_thang_thu_viec"), {
        required: true,
        min: 0,
        integer: true,
    }),
    probationSalaryRate: buildNumberRule(t("field.ty_le_luong_thu_viec"), {
        required: true,
        min: 0,
        max: 100,
        integer: true,
    }),
    annualLeaveDays: buildNumberRule(t("field.so_ngay_nghi_phep_nam"), {
        required: true,
        min: 0,
        integer: true,
    }),
    reviewCycleMonths: buildNumberRule(t("field.chu_ky_danh_gia_thang"), {
        required: true,
        min: 0,
        integer: true,
    }),
    noticePeriodDays: buildNumberRule(t("field.so_ngay_bao_truoc"), {
        required: true,
        min: 0,
        integer: true,
    }),
    status: buildSelectRule(t("field.trang_thai"), [0, 1], {
        required: true,
    }),
    description: buildStringRule(t("field.mo_ta"), {
        required: false,
        max: 5000,
    }),
});
