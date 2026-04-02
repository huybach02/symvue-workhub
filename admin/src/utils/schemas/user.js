import * as yup from "yup";
import {
    buildStringRule,
    buildEmailRule,
    buildPhoneRule,
    buildDateRule,
    buildImageRule,
    buildCccdRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const userSchema = yup.object({
    avatar: buildImageRule(t("field.anh_dai_dien"), {
        required: false,
    }),

    // Thông tin cá nhân
    maNhanVien: buildStringRule(t("field.ma_nhan_vien"), {
        required: true,
        max: 50,
    }),

    name: buildStringRule(t("field.ho_va_ten"), {
        required: true,
        min: 2,
        max: 255,
    }),

    gender: buildStringRule(t("field.gioi_tinh"), {
        required: true,
    }),

    birthday: buildDateRule(t("field.ngay_sinh"), {
        required: true,
    }),

    cmnd: buildCccdRule(t("field.cmnd"), {
        required: true,
    }),

    ngayCapCmnd: buildDateRule(t("field.ngay_cap_cmnd"), {
        required: true,
    }),

    noiCapCmnd: buildStringRule(t("field.noi_cap_cmnd"), {
        required: true,
        max: 255,
    }),

    status: buildStringRule(t("field.trang_thai"), {
        required: true,
    }),

    // Thông tin liên hệ
    email: buildEmailRule(t("field.email"), {
        required: true,
    }),

    phone: buildPhoneRule(t("field.so_dien_thoai"), {
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
});
