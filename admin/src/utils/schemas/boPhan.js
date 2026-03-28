import * as yup from "yup";
import { buildStringRule } from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const boPhanSchema = yup.object({
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

    ghiChu: buildStringRule(t("field.ghi_chu"), {
        required: false,
    }),
});
