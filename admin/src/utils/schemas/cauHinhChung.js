import * as yup from "yup";
import { buildBooleanRule, buildNumberRule } from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const cauHinhChungSchema = yup.object({
    soLanDangNhapSai: buildNumberRule(t("field.soLanDangNhapSai"), {
        required: true,
        min: 0,
        max: 100,
    }),
    thoiGianTamKhoaTaiKhoan: buildNumberRule(
        t("field.thoiGianTamKhoaTaiKhoan"),
        {
            required: true,
            min: 0,
        },
    ),
    xacThuc2YeuTo: buildBooleanRule(t("field.xacThuc2YeuTo"), {
        required: true,
    }),
    thoiGianHetHanMaOtp: buildNumberRule(t("field.thoiGianHetHanMaOtp"), {
        required: true,
        min: 0,
    }),
    soThietBiDangNhapToiDa: buildNumberRule(t("field.soThietBiDangNhapToiDa"), {
        required: true,
        min: 0,
    }),
    thoiHanXacThucLaiThietBi: buildNumberRule(
        t("field.thoiHanXacThucLaiThietBi"),
        {
            required: true,
            min: 0,
        },
    ),
    kiemTraThoiGianLamViec: buildBooleanRule(
        t("field.kiemTraThoiGianLamViec"),
        {
            required: true,
        },
    ),
});
