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
import QRCode from "qrcode";
import axiosInstance from "@/configs/axios";
import { EventSourcePolyfill } from "event-source-polyfill";

const ATTENDANCE_TOPIC = "https://app.com/attendance/:channel";

export default {
    name: "AttendanceQrDisplay",

    data() {
        return {
            loading: true,
            qrToken: null,
            expiresAtMs: null,
            totalDurationMs: 30000,
            serverClientOffset: 0,
            remainingMs: 0,
            timer: null,
            channel: null,
            eventSource: null,
            connectionStatus: "connecting",
            hasRequestedInitialQr: false,
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
        this.channel = this.createChannel();
        this.connectMercure();
    },

    beforeUnmount() {
        this.clearTimer();
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    },

    methods: {
        createChannel() {
            if (window.crypto?.randomUUID) {
                return window.crypto.randomUUID();
            }

            return `attendance-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        },

        connectMercure() {
            const mercureToken = localStorage.getItem("mercure_token");

            if (!mercureToken) {
                this.connectionStatus = "error";
                return;
            }

            const url = new URL(import.meta.env.VITE_MERCURE_URL);
            url.searchParams.append(
                "topic",
                ATTENDANCE_TOPIC.replace(":channel", this.channel),
            );

            this.eventSource = new EventSourcePolyfill(url, {
                headers: {
                    Authorization: "Bearer " + mercureToken,
                },
            });

            this.eventSource.onopen = () => {
                this.connectionStatus = "connected";

                if (this.hasRequestedInitialQr) {
                    return;
                }

                this.hasRequestedInitialQr = true;
                this.requestQrGeneration();
            };

            this.eventSource.onmessage = (event) => {
                if (!event?.data) {
                    return;
                }

                try {
                    const data = JSON.parse(event.data);
                    this.handleMercureMessage(data);
                } catch (error) {
                    console.error("Mercure attendance payload invalid:", error);
                }
            };

            this.eventSource.onerror = (error) => {
                console.error("Mercure attendance error:", error);
                this.connectionStatus = "error";
            };
        },

        async requestQrGeneration() {
            this.loading = true;

            try {
                const response = await axiosInstance.get(
                    "/attendance/qr-attendance",
                    {
                        params: {
                            channel: this.channel,
                        },
                    },
                );

                if (!response?.success) {
                    throw new Error("Invalid response");
                }
            } catch (error) {
                console.error("Failed to request QR:", error);
                this.loading = false;
            }
        },

        handleMercureMessage(data) {
            if (
                data?.type !== "attendance_qr" ||
                data?.channel !== this.channel
            ) {
                return;
            }

            this.applyQrPayload(data);
        },

        async applyQrPayload(payload) {
            const clientReceivedAt = Date.now();
            const { token, serverTime, expiresAt, serverTimeMs, expiresAtMs } =
                payload;

            this.qrToken = token;
            this.expiresAtMs =
                Number(expiresAtMs) || new Date(expiresAt).getTime();

            const resolvedServerTimeMs =
                Number(serverTimeMs) || new Date(serverTime).getTime();
            this.serverClientOffset = resolvedServerTimeMs - clientReceivedAt;
            this.totalDurationMs = Math.max(
                1,
                this.expiresAtMs - resolvedServerTimeMs,
            );

            await this.renderQr(token);
            this.startCountdown();
            this.loading = false;
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
                    this.requestQrGeneration();
                }
            }, 250);
        },

        clearTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
    },
};
</script>
