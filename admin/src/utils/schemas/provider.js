import * as yup from "yup";
import { buildStringRule, buildNumberRule, buildEmailRule, buildPhoneRule } from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const providerSchema = yup.object({
    code: buildStringRule(t("provider.columns.code"), {
        required: true,
        min: 3,
        max: 255,
    }),

    name: buildStringRule(t("provider.columns.name"), {
        required: true,
        min: 3,
        max: 255,
    }),

    phone: buildPhoneRule(t("provider.columns.phone"), {
        required: false,
    }),

    email: buildEmailRule(t("provider.columns.email"), {
        required: false,
    }),

    address: buildStringRule(t("provider.columns.address"), {
        required: false,
        max: 255,
    }),

    taxNumber: buildStringRule(t("provider.columns.taxNumber"), {
        required: false,
        max: 255,
    }),

    bankName: buildStringRule(t("provider.columns.bankName"), {
        required: false,
        max: 255,
    }),

    bankNumber: buildStringRule(t("provider.columns.bankNumber"), {
        required: false,
        max: 255,
    }),

    note: buildStringRule(t("provider.columns.note"), {
        required: false,
    }),

    status: buildNumberRule(t("provider.columns.status"), {
        required: true,
    }),
});
