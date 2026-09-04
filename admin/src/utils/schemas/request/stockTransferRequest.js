import * as yup from "yup";
import {
    buildNumberRule,
    buildStringRule,
    buildUuidRule,
} from "../../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key, params) => i18n.global.t(key, params);

const stockTransferItemSchema = yup.object({
    lineId: buildUuidRule("lineId", { required: true }),
    sourceBalanceId: yup
        .number()
        .positive()
        .integer()
        .required(t("request.stock_transfer.select_lot")),
    quantity: buildNumberRule(t("field.quantity"), {
        required: true,
        min: 0.000001,
    }),
});

export const stockTransferRequestSchema = yup.object({
    destinationWarehouseId: yup
        .number()
        .positive()
        .integer()
        .required(t("request.stock_transfer.destination_required")),
    items: yup
        .array()
        .of(stockTransferItemSchema)
        .min(1, t("request.stock_transfer.items_required")),
    reason: buildStringRule(t("request.stock_transfer.reason"), {
        required: true,
        max: 1000,
    }),
    note: buildStringRule(t("field.ghi_chu"), {
        required: false,
        max: 1000,
    }),
});
