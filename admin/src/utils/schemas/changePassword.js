import * as yup from "yup";
import { buildPasswordRule, buildConfirmPasswordRule } from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const changePasswordSchema = yup.object({
    currentPassword: buildPasswordRule(t("auth.current_password"), {
        required: true,
        min: 6,
    }),
    newPassword: buildPasswordRule(t("auth.new_password"), {
        required: true,
        min: 6,
    }),
    confirmPassword: buildConfirmPasswordRule(t("auth.confirm_password"), "newPassword"),
});
