import * as yup from "yup";
import { messageValidate, renderMessage } from "../constants/messageValidate";

export const emailRule = yup
    .string()
    .required(renderMessage(messageValidate.required, "Email"))
    .email(renderMessage(messageValidate.email, "Email"));

export const passwordRule = yup
    .string()
    .required(renderMessage(messageValidate.required, "Mật khẩu"));

export const minNumberRule = yup
    .number()
    .transform((value, originalValue) => (originalValue === "" ? null : value))
    .nullable()
    .min(0, ({ min }) =>
        renderMessage(messageValidate.minNumber).replace("{min}", min),
    );

export const minNumberRuleRequired = yup
    .number()
    .transform((value, originalValue) =>
        originalValue === "" ? undefined : value,
    )
    .required(renderMessage(messageValidate.required))
    .min(0, ({ min }) =>
        renderMessage(messageValidate.minNumber).replace("{min}", min),
    );

export const maxNumberRule = yup
    .number()
    .transform((value, originalValue) => (originalValue === "" ? null : value))
    .nullable()
    .max(0, ({ max }) =>
        renderMessage(messageValidate.maxNumber).replace("{max}", max),
    );

export const maxNumberRuleRequired = yup
    .number()
    .transform((value, originalValue) =>
        originalValue === "" ? undefined : value,
    )
    .required(renderMessage(messageValidate.required))
    .max(0, ({ max }) =>
        renderMessage(messageValidate.maxNumber).replace("{max}", max),
    );

export const booleanRule = yup
    .boolean()
    .required(renderMessage(messageValidate.required));

export const confirmPasswordRule = yup
    .string()
    .required("Xác nhận mật khẩu là bắt buộc")
    .oneOf([yup.ref("password")], "Mật khẩu không khớp");
