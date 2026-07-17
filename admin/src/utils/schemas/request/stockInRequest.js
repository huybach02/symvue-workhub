import * as yup from "yup";
import { buildNumberRule, buildStringRule } from "../../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key, params) => i18n.global.t(key, params);

const stockInItemSchema = yup.object({
    merchandiseId: yup
        .mixed()
        .nullable()
        .required(
            t("validation.mixed.required", {
                field: t("field.stock_in_merchandise"),
            }),
        ),
    quantity: buildNumberRule(t("field.quantity"), {
        required: true,
        min: 0.0001,
    }),
    unitId: yup
        .mixed()
        .nullable()
        .required(
            t("validation.mixed.required", {
                field: t("field.unit"),
            }),
        ),
    price: buildNumberRule(t("field.import_price"), {
        required: true,
        min: 0,
    }),
    note: buildStringRule(t("field.ghi_chu"), {
        required: false,
        max: 1000,
    }),
});

const stockInProviderSchema = yup.object({
    providerId: yup
        .mixed()
        .nullable()
        .required(
            t("validation.mixed.required", {
                field: t("field.provider"),
            }),
        ),
    items: yup
        .array()
        .of(stockInItemSchema)
        .min(1, t("request.stock_in.items_required")),
});

export const stockInRequestSchema = yup.object({
    providers: yup
        .array()
        .of(stockInProviderSchema)
        .min(1, t("request.stock_in.providers_required")),
});
