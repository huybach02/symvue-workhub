import * as yup from "yup";
import {
    buildStringRule,
    buildEmailRule,
    buildPhoneRule,
    buildPasswordRule,
    buildDateRule,
    buildNumberRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const userSchema = yup.object({
    name: buildStringRule(t("field.ho_va_ten"), {
        required: true,
        min: 2,
        max: 255,
    }),

    email: buildEmailRule(t("field.email"), {
        required: true,
    }),

    phone: buildPhoneRule(t("field.so_dien_thoai"), {
        required: true,
    }),

    birthday: buildDateRule(t("field.ngay_sinh"), {
        required: true,
    }),
    gender: buildStringRule(t("field.gioi_tinh"), {
        required: true,
    }),

    province: buildStringRule(t("field.tinh_thanh_pho"), {
        required: true,
    }),
    ward: buildStringRule(t("field.xa_phuong"), {
        required: true,
    }),

    address: buildStringRule(t("field.dia_chi"), {
        required: true,
        min: 5,
        max: 500,
    }),

    maBoPhan: buildStringRule(t("field.bo_phan_mac_dinh"), {
        required: true,
    }),

    status: buildStringRule(t("field.trang_thai"), {
        required: true,
    }),
});
