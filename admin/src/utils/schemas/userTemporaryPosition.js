import { buildNumberRule, buildStringRule } from "../validationBuilder";
import * as yup from "yup";

export const userTemporaryPositionSchema = yup.object({
    departmentId: buildNumberRule("Bo phan", {
        required: true,
        min: 1,
        integer: true,
    }),
    positionId: buildNumberRule("Chuc vu", {
        required: true,
        min: 1,
        integer: true,
    }),
    startTempDate: buildStringRule("Ngay bat dau hieu luc", {
        required: true,
        max: 50,
    }),
    startTempTime: buildStringRule("Gio bat dau hieu luc", {
        required: true,
        max: 10,
    }),
    endTempDate: buildStringRule("Ngay het hieu luc", {
        required: true,
        max: 50,
    }),
    endTempTime: buildStringRule("Gio het hieu luc", {
        required: true,
        max: 10,
    }),
});
