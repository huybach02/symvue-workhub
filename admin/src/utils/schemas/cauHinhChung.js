import * as yup from "yup";
import {
    buildBooleanRule,
    buildNumberRule,
    buildStringRule,
} from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key) => i18n.global.t(key);

export const cauHinhChungSchema = yup.object({
    soLanDangNhapSai: buildNumberRule(t("field.soLanDangNhapSai"), {
        required: true,
        min: 0,
        max: 100,
    }),
    thoiGianTamKhoaTaiKhoan: buildNumberRule(
        t("field.thoiGianTamKhoaTaiKhoan"),
        {
            required: true,
            min: 0,
        },
    ),
    xacThuc2YeuTo: buildBooleanRule(t("field.xacThuc2YeuTo"), {
        required: true,
    }),
    thoiGianHetHanMaOtp: buildNumberRule(t("field.thoiGianHetHanMaOtp"), {
        required: true,
        min: 0,
    }),
    soThietBiDangNhapToiDa: buildNumberRule(t("field.soThietBiDangNhapToiDa"), {
        required: true,
        min: 0,
    }),
    thoiHanXacThucLaiThietBi: buildNumberRule(
        t("field.thoiHanXacThucLaiThietBi"),
        {
            required: true,
            min: 0,
        },
    ),
    kiemTraThoiGianLamViec: buildBooleanRule(
        t("field.kiemTraThoiGianLamViec"),
        {
            required: true,
        },
    ),
    checkInGraceMinutes: buildNumberRule(t("field.checkInGraceMinutes"), {
        required: true,
        min: 0,
    }),
    lateLimitMinutes: buildNumberRule(t("field.lateLimitMinutes"), {
        required: true,
        min: 0,
    }),
    checkInEarliestMinutes: buildNumberRule(t("field.checkInEarliestMinutes"), {
        required: true,
        min: 0,
    }),
    checkOutGraceMinutes: buildNumberRule(t("field.checkOutGraceMinutes"), {
        required: true,
        min: 0,
    }),
    checkOutLatestMinutes: buildNumberRule(t("field.checkOutLatestMinutes"), {
        required: true,
        min: 0,
    }),
    latitude: buildNumberRule(t("field.latitude"), {
        required: true,
    }),
    longitude: buildNumberRule(t("field.longitude"), {
        required: true,
    }),
    radiusMeters: buildNumberRule(t("field.radiusMeters"), {
        required: true,
        min: 0,
    }),
    addressDisplay: buildStringRule(t("field.addressDisplay"), {
        required: true,
    }),
    ipAddress: buildStringRule(t("field.ipAddress"), {
        required: true,
    }),
    qrTtlSeconds: buildNumberRule(t("field.qrTtlSeconds"), {
        required: true,
        min: 0,
    }),
    photoRetentionDays: buildNumberRule(t("field.photoRetentionDays"), {
        required: true,
        min: 0,
    }),
    maxDevicesPerEmployee: buildNumberRule(t("field.maxDevicesPerEmployee"), {
        required: true,
        min: 0,
    }),
    sameDeviceMaxEmployees: buildNumberRule(t("field.sameDeviceMaxEmployees"), {
        required: true,
        min: 0,
    }),
    checkInReminderMinutesBefore: buildNumberRule(
        t("field.checkInReminderMinutesBefore"),
        {
            required: true,
            min: 0,
        },
    ),
    checkOutReminderMinutesBefore: buildNumberRule(
        t("field.checkOutReminderMinutesBefore"),
        {
            required: true,
            min: 0,
        },
    ),
    currency: buildStringRule(t("field.currency"), {
        required: true,
    }),
});
