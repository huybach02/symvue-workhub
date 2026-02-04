import * as yup from "yup";
import {
    buildConfirmPasswordRule,
    buildEmailRule,
    buildOtpRule,
    buildStringRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const loginSchema = yup.object({
    email: buildEmailRule(t("field.email"), { required: true }),
    password: buildStringRule(t("field.password"), { required: true }),
});

export const verifyOtpSchema = yup.object({
    otp: buildOtpRule(t("field.otp"), { required: true }),
});

export const forgotPasswordSchema = yup.object({
    email: buildEmailRule(t("field.email"), { required: true }),
});

export const changePasswordSchema = yup.object({
    password: buildStringRule(t("field.password"), { required: true }),
    confirm_password: buildConfirmPasswordRule(t("field.confirm_password")),
});
