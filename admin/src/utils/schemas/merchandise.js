import * as yup from "yup";
import {
    buildStringRule,
    buildNumberRule,
    buildPercentageRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const merchandiseSchema = yup.object({
    code: buildStringRule(t("field.merchandise_code"), {
        required: true,
        min: 3,
        max: 255,
    }),
    name: buildStringRule(t("field.merchandise_name"), {
        required: true,
        min: 3,
        max: 255,
    }),
    categoryId: buildNumberRule(t("category.title"), {
        required: true,
        integer: true,
    }),
    profit: buildPercentageRule(t("field.merchandise_profit"), {
        required: false,
    }),
    stockAlertQuantity: buildNumberRule(
        t("field.merchandise_stock_alert_quantity"),
        {
            required: false,
            min: 0,
        },
    ),
    description: buildStringRule(t("field.merchandise_description"), {
        required: false,
        max: 500,
    }),
    notes: buildStringRule(t("field.merchandise_notes"), {
        required: false,
        max: 500,
    }),
    status: yup.number().required().oneOf([0, 1]),
    baseUnitId: yup.mixed().nullable().notRequired(),
    conversions: yup.array().nullable().notRequired(),
    providers: yup
        .array()
        .nullable()
        .notRequired()
        .test(
            "has-default-provider",
            t("merchandise.errors.default_provider_required") ||
                "Vui lòng chọn một nhà cung cấp làm mặc định!",
            function (value) {
                if (!value || value.length === 0) {
                    return true;
                }
                const defaultProviders = value.filter(
                    (p) => p.isDefault && p.providerId,
                );
                return defaultProviders.length === 1;
            },
        )
        .test(
            "default-provider-has-price",
            t("merchandise.errors.default_provider_price_required") ||
                "Nhà cung cấp mặc định phải có ít nhất một đơn giá!",
            function (value) {
                if (!value || value.length === 0) {
                    return true;
                }
                const defaultProvider = value.find(
                    (p) => p.isDefault && p.providerId,
                );
                if (!defaultProvider) {
                    return true;
                }
                return (
                    defaultProvider.prices &&
                    defaultProvider.prices.some(
                        (pr) =>
                            pr.price !== null &&
                            pr.price !== undefined &&
                            pr.price !== "",
                    )
                );
            },
        )
        .test(
            "price-default-required",
            t("merchandise.errors.price_default_required") ||
                "Vui lòng chọn một đơn vị giá làm mặc định!",
            function (value, context) {
                if (!value || value.length === 0) {
                    return true;
                }
                const values = context.parent;
                const configuredUnitIds = getConfiguredUnitIds(values);

                for (const prov of value) {
                    if (!prov.providerId) continue;
                    const providerPrices = (prov.prices || []).filter((price) =>
                        configuredUnitIds.length > 0
                            ? configuredUnitIds.includes(Number(price.unitId))
                            : true,
                    );
                    const hasAnyPriceFilled = providerPrices.some(hasPriceValue);
                    if (!hasAnyPriceFilled) {
                        continue;
                    }

                    const targetUnitIds =
                        configuredUnitIds.length > 0
                            ? configuredUnitIds
                            : providerPrices.map((price) =>
                                  Number(price.unitId),
                              );

                    const isAllPricesFilled = targetUnitIds.every(
                        (unitId) => {
                            const priceItem = providerPrices.find(
                                (p) => Number(p.unitId) === unitId,
                            );
                            return hasPriceValue(priceItem);
                        },
                    );

                    if (!isAllPricesFilled) {
                        const hasDefaultPrice = providerPrices.some(
                            (p) => p.isDefault && hasPriceValue(p),
                        );
                        if (!hasDefaultPrice) {
                            return false;
                        }
                    }
                }
                return true;
            },
        ),
    finishedProductSource: yup.string().nullable().notRequired(),
    recipe: yup.object().nullable().notRequired(),
});

function getConfiguredUnitIds(values) {
    const unitIds = new Set();
    if (values.baseUnitId) {
        unitIds.add(Number(values.baseUnitId));
    }
    const conversions = values.conversions || [];
    conversions.forEach((c) => {
        if (c.fromUnitId) unitIds.add(Number(c.fromUnitId));
        if (c.toUnitId) unitIds.add(Number(c.toUnitId));
    });
    return Array.from(unitIds);
}

function hasPriceValue(priceItem) {
    return (
        priceItem &&
        priceItem.price !== null &&
        priceItem.price !== undefined &&
        priceItem.price !== ""
    );
}
