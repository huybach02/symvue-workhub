import * as yup from "yup";
import { emailRule, passwordRule } from "./common";

// Schema cho form Đăng nhập
export const loginSchema = yup.object({
    email: emailRule,
    password: passwordRule,
});
