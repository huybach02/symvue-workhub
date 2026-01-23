import * as yup from "yup";
import { booleanRule, minNumberRule } from "./common";

export const cauHinhChungSchema = yup.object({
    soLanDangNhapSai: minNumberRule,
    thoiGianTamKhoaTaiKhoan: minNumberRule,
    xacThuc2YeuTo: booleanRule,
    thoiGianHetHanMaOtp: minNumberRule,
    thoiHanXacThucLaiThietBi: minNumberRule,
    kiemTraThoiGianLamViec: booleanRule,
    thoiGianHetHanThietBi: minNumberRule,
});
