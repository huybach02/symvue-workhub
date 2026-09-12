import * as yup from "yup";
import {
    buildStringRule,
    buildNumberRule,
    buildPercentageRule,
    buildImageRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const businessProductSchema = yup.object({
    code: buildStringRule(t("field.business_product_code"), {
        required: true,
        min: 3,
        max: 255,
    }),
    name: buildStringRule(t("field.business_product_name"), {
        required: true,
        min: 3,
        max: 255,
    }),
    categoryId: buildNumberRule(t("category.title"), {
        required: true,
        integer: true,
    }),
    targetProfitMargin: buildPercentageRule(
        t("field.business_product_profit"),
        {
            required: true,
        },
    ),
    description: buildStringRule(t("field.business_product_description"), {
        required: false,
        max: 500,
    }),
    notes: buildStringRule(t("field.business_product_notes"), {
        required: false,
        max: 500,
    }),
    image: buildImageRule(t("field.image"), {
        required: false,
    }),
    status: yup.number().required().oneOf([0, 1]),
});
