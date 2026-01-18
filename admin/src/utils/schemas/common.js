import * as yup from "yup";
import { messageValidate, renderMessage } from "../constants/messageValidate";

export const emailRule = yup
    .string()
    .required(renderMessage(messageValidate.required, "Email"))
    .email(renderMessage(messageValidate.email, "Email"));

export const passwordRule = yup
    .string()
    .required(renderMessage(messageValidate.required, "Mật khẩu"));
