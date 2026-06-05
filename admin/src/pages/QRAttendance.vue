<template>
    <v-container class="fill-height" style="min-height: 100vh">
        <v-row class="fill-height justify-center" no-gutters>
            <v-col cols="12" sm="10" md="8" lg="6" class="d-flex">
                <v-card
                    rounded="xl"
                    elevation="4"
                    class="flex-grow-1 d-flex flex-column"
                >
                    <v-card-title
                        class="text-h4 font-weight-bold text-center w-100 d-block"
                    >
                        QR chấm công
                    </v-card-title>

                    <v-alert
                        v-if="accessError"
                        type="error"
                        variant="tonal"
                        class="mx-8 mt-4"
                    >
                        {{ accessError }}
                    </v-alert>

                    <v-card-text class="pa-8 flex-grow-1 d-flex align-center">
                        <v-row justify="center" align="center" class="ga-8">
                            <v-col
                                cols="12"
                                sm="7"
                                class="d-flex justify-center"
                            >
                                <v-sheet
                                    class="pa-5 d-inline-flex align-center justify-center position-relative"
                                    rounded="xl"
                                    color="blue-lighten-5"
                                    elevation="0"
                                >
                                    <canvas ref="qrCanvas"></canvas>

                                    <v-overlay
                                        :model-value="loading"
                                        contained
                                        scrim="white"
                                        class="d-flex align-center justify-center"
                                    >
                                        <v-progress-circular
                                            indeterminate
                                            color="primary"
                                            :size="56"
                                            :width="6"
                                        />
                                    </v-overlay>
                                </v-sheet>
                            </v-col>

                            <v-col
                                cols="12"
                                sm="5"
                                class="d-flex justify-center"
                            >
                                <v-progress-circular
                                    :model-value="countdownProgress * 100"
                                    :size="180"
                                    :width="14"
                                    color="primary"
                                    bg-color="blue-lighten-4"
                                >
                                    <div class="text-center">
                                        <div
                                            class="text-caption text-medium-emphasis"
                                        >
                                            Còn lại
                                        </div>
                                        <div class="text-h4 font-weight-bold">
                                            {{ remainingSeconds }}s
                                        </div>
                                    </div>
                                </v-progress-circular>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axios from "axios";
import QRCode from "qrcode";

const RELOAD_GUARD_KEY = "qr-attendance-reload-guard";
const QR_ATTENDANCE_ACCESS_KEY = "qr-attendance-display-access";
const QR_ATTENDANCE_HARD_RELOAD_KEY = "qr-attendance-hard-reload-key";
const RELOAD_LIMIT = 10;
const RELOAD_WINDOW_MS = 5 * 60 * 1000;
const RELOAD_DELAY_MS = 2000;
const publicQrAxios = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api",
    timeout: 10000,
    headers: {
        "Content-Type": "application/json",
    },
});

export default {
    name: "AttendanceQrDisplay",

    data() {
        return {
            loading: false,
            qrToken: null,
            expiresAtMs: null,
            totalDurationMs: 30000,
            serverClientOffset: 0,
            remainingMs: 0,
            timer: null,
            reloadTimeout: null,
            qrDisplayAccessToken: null,
            accessError: null,
        };
    },

    computed: {
        remainingSeconds() {
            return Math.max(0, Math.ceil(this.remainingMs / 1000));
        },

        countdownProgress() {
            if (!this.totalDurationMs) {
                return 0;
            }

            return Math.max(
                0,
                Math.min(1, this.remainingMs / this.totalDurationMs),
            );
        },
    },

    mounted() {
        this.qrDisplayAccessToken = this.resolveQrDisplayAccessToken();

        if (!this.qrDisplayAccessToken) {
            this.accessError =
                "Trang QR chấm công này cần được mở từ màn hình quản trị.";
            return;
        }

        this.generateQr();
    },

    beforeUnmount() {
        this.clearTimer();
        this.clearReloadTimeout();
    },

    methods: {
        resolveQrDisplayAccessToken() {
            const accessTokenFromQuery = this.$route.query.access;
            const accessToken =
                typeof accessTokenFromQuery === "string"
                    ? accessTokenFromQuery
                    : sessionStorage.getItem(QR_ATTENDANCE_ACCESS_KEY);

            if (!accessToken) {
                return null;
            }

            sessionStorage.setItem(QR_ATTENDANCE_ACCESS_KEY, accessToken);

            if (typeof accessTokenFromQuery === "string") {
                this.$router.replace({
                    path: this.$route.path,
                    query: {},
                });
            }

            return accessToken;
        },

        async generateQr() {
            if (!this.qrDisplayAccessToken) {
                return;
            }

            this.loading = true;
            this.accessError = null;

            try {
                const requestStartedAt = Date.now();

                const response = await publicQrAxios.get(
                    API_ROUTES_CONFIG.attendanceQr,
                    {
                        params: {
                            access: this.qrDisplayAccessToken,
                        },
                    },
                );
                const responseReceivedAt = Date.now();

                const payload = response?.data?.data ?? response?.data;

                if (!payload) {
                    throw new Error("Invalid response");
                }

                const {
                    token,
                    serverTime,
                    expiresAt,
                    serverTimeMs,
                    expiresAtMs,
                    isHardReload,
                    hardReloadKey,
                } = payload;

                if (this.shouldHardReload(isHardReload, hardReloadKey)) {
                    window.location.reload();
                    return;
                }

                this.qrToken = token;
                this.expiresAtMs =
                    Number(expiresAtMs) || new Date(expiresAt).getTime();

                const resolvedServerTimeMs =
                    Number(serverTimeMs) || new Date(serverTime).getTime();
                const clientEstimatedAt =
                    (requestStartedAt + responseReceivedAt) / 2;
                this.serverClientOffset =
                    resolvedServerTimeMs - clientEstimatedAt;
                this.totalDurationMs = Math.max(
                    1,
                    this.expiresAtMs - resolvedServerTimeMs,
                );
                this.remainingMs = Math.max(
                    0,
                    this.expiresAtMs - (Date.now() + this.serverClientOffset),
                );
                this.resetReloadGuard();

                await this.renderQr(token);

                this.startCountdown();
            } catch (error) {
                console.error("Failed to generate QR:", error);
                if ([400, 401, 403].includes(error?.response?.status)) {
                    this.accessError =
                        "Quyen truy cap trang QR cham cong da het han hoac khong hop le.";
                    this.resetReloadGuard();
                    return;
                }
                this.retryByReload();
            } finally {
                this.loading = false;
            }
        },

        async renderQr(token) {
            await QRCode.toCanvas(this.$refs.qrCanvas, token, {
                width: 400,
                margin: 2,
                errorCorrectionLevel: "M",
            });
        },

        startCountdown() {
            this.clearTimer();

            this.timer = setInterval(() => {
                const estimatedServerNow = Date.now() + this.serverClientOffset;
                this.remainingMs = Math.max(
                    0,
                    this.expiresAtMs - estimatedServerNow,
                );

                if (this.remainingMs <= 0) {
                    this.clearTimer();
                    this.generateQr();
                }
            }, 250);
        },

        clearTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        clearReloadTimeout() {
            if (this.reloadTimeout) {
                clearTimeout(this.reloadTimeout);
                this.reloadTimeout = null;
            }
        },

        resetReloadGuard() {
            sessionStorage.removeItem(RELOAD_GUARD_KEY);
            this.clearReloadTimeout();
        },

        shouldHardReload(isHardReload, hardReloadKey) {
            if (!isHardReload) {
                return false;
            }

            const resolvedKey =
                typeof hardReloadKey === "string" && hardReloadKey
                    ? hardReloadKey
                    : new Date().toISOString().slice(0, 10);

            if (
                sessionStorage.getItem(QR_ATTENDANCE_HARD_RELOAD_KEY) ===
                resolvedKey
            ) {
                return false;
            }

            sessionStorage.setItem(
                QR_ATTENDANCE_HARD_RELOAD_KEY,
                resolvedKey,
            );

            return true;
        },

        retryByReload() {
            this.clearReloadTimeout();

            const now = Date.now();
            const savedGuard = sessionStorage.getItem(RELOAD_GUARD_KEY);
            let guard = { count: 0, firstAttemptAt: 0 };

            if (savedGuard) {
                try {
                    const parsed = JSON.parse(savedGuard);
                    guard = {
                        count: Number(parsed?.count) || 0,
                        firstAttemptAt: Number(parsed?.firstAttemptAt) || 0,
                    };
                } catch {
                    // Bo qua du lieu guard loi va bat dau lai.
                }
            }

            if (
                !guard.firstAttemptAt ||
                now - guard.firstAttemptAt > RELOAD_WINDOW_MS
            ) {
                guard = {
                    count: 0,
                    firstAttemptAt: now,
                };
            }

            const nextState = {
                count: guard.count + 1,
                firstAttemptAt: guard.firstAttemptAt,
            };

            if (nextState.count > RELOAD_LIMIT) {
                console.error(
                    "[QRAttendance] Da vuot qua gioi han tu reload, dung lai de tranh lap vo tan.",
                );
                return;
            }

            sessionStorage.setItem(RELOAD_GUARD_KEY, JSON.stringify(nextState));

            this.reloadTimeout = window.setTimeout(() => {
                window.location.reload();
            }, RELOAD_DELAY_MS);
        },
    },
};
</script>
