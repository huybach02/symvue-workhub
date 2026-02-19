import * as yup from "yup";
import { buildStringRule } from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const boPhanSchema = yup.object({
    quanLyBoPhanId: buildStringRule(t("field.quan_ly_bo_phan"), {
        required: true,
    }),

    tenBoPhan: buildStringRule(t("field.ten_bo_phan"), {
        required: true,
        min: 2,
        max: 255,
    }),

    maBoPhan: buildStringRule(t("field.ma_bo_phan"), {
        required: true,
    }),

    status: buildStringRule(t("field.trang_thai"), {
        required: true,
    }),
});
