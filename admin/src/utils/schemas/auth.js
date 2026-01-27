import * as yup from "yup";
import { emailRule, passwordRule, confirmPasswordRule } from "./common";

// Schema cho form Đăng nhập
export const loginSchema = yup.object({
    email: emailRule,
    password: passwordRule,
});

// Schema cho form Xác thực OTP
export const verifyOtpSchema = yup.object({
    otp: yup
        .string()
        .required("Mã OTP là bắt buộc")
        .length(6, "Mã OTP phải có đúng 6 chữ số")
        .matches(/^[0-9]+$/, "Mã OTP chỉ được chứa số"),
});

export const forgotPasswordSchema = yup.object({
    email: emailRule,
});

export const changePasswordSchema = yup.object({
    password: passwordRule,
    confirm_password: confirmPasswordRule,
});
