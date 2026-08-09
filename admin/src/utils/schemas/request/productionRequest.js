import * as yup from "yup";
import {
    buildNumberRule,
    buildPercentageRule,
    buildStringRule,
    buildUuidRule,
} from "../../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key, params) => i18n.global.t(key, params);

const productionMaterialSchema = yup.object({
    ingredientId: yup
        .mixed()
        .nullable()
        .required(
            t("validation.mixed.required", {
                field: t("merchandise.ingredient"),
            }),
        ),
    quantity: buildNumberRule(t("field.quantity"), {
        required: true,
        min: 0,
    }),
    unitId: yup
        .mixed()
        .nullable()
        .required(
            t("validation.mixed.required", {
                field: t("field.unit"),
            }),
        ),
    wasteRate: buildPercentageRule(t("merchandise.recipe.waste_rate"), {
        required: false,
    }),
    note: buildStringRule(t("field.ghi_chu"), {
        required: false,
        max: 1000,
    }),
});

const productionItemSchema = yup.object({
    lineId: buildUuidRule("lineId", { required: true }),
    finishedProductId: yup
        .mixed()
        .nullable()
        .required(
            t("validation.mixed.required", {
                field: t("merchandise.finished_product"),
            }),
        ),
    quantity: buildNumberRule(t("field.quantity"), {
        required: true,
        min: 0.0001,
    }),
    outputUnitId: yup
        .mixed()
        .nullable()
        .required(
            t("validation.mixed.required", {
                field: t("merchandise.recipe.output_unit"),
            }),
        ),
    expectedWastePercent: buildPercentageRule(
        t("merchandise.recipe.waste_rate"),
        { required: false },
    ),
    materials: yup
        .array()
        .of(productionMaterialSchema)
        .min(1, t("request.production.materials_required")),
    supplementSelections: yup
        .array()
        .of(
            yup.object({
                productionOrderItemId: yup.number().positive().integer().required(),
                mode: yup.string().oneOf(["MINIMUM", "FULL"]).required(),
            }),
        )
        .default([]),
});

export const productionRequestSchema = yup.object({
    items: yup
        .array()
        .of(productionItemSchema)
        .min(1, t("request.production.items_required")),
});
