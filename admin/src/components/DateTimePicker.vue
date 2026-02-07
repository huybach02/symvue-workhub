<template>
    <v-menu
        v-model="menuOpen"
        :close-on-content-click="false"
        transition="scale-transition"
        offset-y
        min-width="auto"
    >
        <template #activator="{ props: menuProps }">
            <v-text-field
                v-bind="{ ...menuProps, ...$attrs }"
                :model-value="displayValue"
                :label="label"
                :error-messages="errorMessages"
                :readonly="readonly"
                :disabled="disabled"
                :variant="variant"
                :density="density"
                :prepend-inner-icon="prependInnerIcon"
                :append-inner-icon="appendInnerIcon"
                :clearable="clearable"
                @click:clear="handleClear"
            >
                <template v-if="$slots.label" #label>
                    <slot name="label" />
                </template>
            </v-text-field>
        </template>

        <v-card min-width="600">
            <v-card-title class="text-subtitle-1">
                Chọn ngày và giờ
            </v-card-title>

            <v-card-text>
                <v-row>
                    <!-- Date Picker -->
                    <v-col cols="12" md="7">
                        <div class="text-caption mb-2">Ngày</div>
                        <v-date-picker
                            v-model="internalDate"
                            no-title
                            scrollable
                            width="100%"
                        />
                    </v-col>

                    <!-- Time Picker -->
                    <v-col cols="12" md="5">
                        <div class="text-caption mb-2">Giờ</div>
                        <v-time-picker
                            v-model="internalTime"
                            format="24hr"
                            scrollable
                            width="100%"
                        />
                    </v-col>
                </v-row>
            </v-card-text>

            <v-card-actions>
                <!-- Preview ngày và giờ đã chọn -->
                <div v-if="internalDate || internalTime" class="text-caption">
                    <span v-if="internalDate"> 📅 {{ previewDate }} </span>
                    <span v-if="internalTime" class="ml-2">
                        🕐 {{ previewTime }}
                    </span>
                </div>

                <v-spacer />

                <v-btn variant="text" @click="handleCancel">Hủy</v-btn>
                <v-btn color="primary" variant="text" @click="handleConfirm">
                    OK
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-menu>
</template>

<script>
export default {
    name: "DateTimePicker",
    inheritAttrs: false,

    props: {
        modelValue: {
            type: [String, Date, null],
            default: null,
        },
        label: {
            type: String,
            default: "",
        },
        errorMessages: {
            type: [String, Array],
            default: "",
        },
        readonly: {
            type: Boolean,
            default: true,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        variant: {
            type: String,
            default: "outlined",
        },
        density: {
            type: String,
            default: "default",
        },
        prependInnerIcon: {
            type: String,
            default: "mdi-calendar-clock",
        },
        appendInnerIcon: {
            type: String,
            default: "",
        },
        clearable: {
            type: Boolean,
            default: false,
        },
        // Format hiển thị ngày: dd/mm/yyyy hoặc yyyy/mm/dd
        dateFormat: {
            type: String,
            default: "dd/mm/yyyy",
            validator: (value) => ["dd/mm/yyyy", "yyyy/mm/dd"].includes(value),
        },
        // Có hiển thị giây không
        useSeconds: {
            type: Boolean,
            default: false,
        },
        // Format output: iso (YYYY-MM-DDTHH:mm:ss) hoặc sql (YYYY-MM-DD HH:mm:ss)
        outputFormat: {
            type: String,
            default: "iso",
            validator: (value) => ["iso", "sql"].includes(value),
        },
    },

    emits: ["update:modelValue"],

    data() {
        return {
            menuOpen: false,
            internalDate: null, // Giá trị ngày đang chọn
            internalTime: null, // Giá trị giờ đang chọn
        };
    },

    computed: {
        // Giá trị hiển thị trong text field
        displayValue() {
            if (!this.modelValue) return "";

            const parsed = this.parseDateTime(this.modelValue);
            if (!parsed) return "";

            const formattedDate = this.formatDate(parsed.date);
            const formattedTime = this.useSeconds
                ? parsed.time
                : parsed.time.substring(0, 5);

            return `${formattedDate} ${formattedTime}`;
        },

        // Preview ngày đã chọn
        previewDate() {
            return this.convertToDateString(this.internalDate);
        },

        // Preview giờ đã chọn
        previewTime() {
            return this.convertToTimeString(this.internalTime);
        },
    },

    watch: {
        // Đồng bộ giá trị khi mở/đóng menu
        menuOpen(isOpen) {
            if (isOpen && this.modelValue) {
                // Khi mở menu: load giá trị hiện tại
                const parsed = this.parseDateTime(this.modelValue);
                if (parsed) {
                    this.internalDate = parsed.date;
                    this.internalTime = parsed.time;
                }
            } else if (!isOpen) {
                // Khi đóng menu: reset giá trị tạm
                this.internalDate = null;
                this.internalTime = null;
            }
        },
    },

    methods: {
        // ==================== CONVERSION HELPERS ====================

        /**
         * Convert Date object hoặc string sang YYYY-MM-DD
         */
        convertToDateString(value) {
            if (!value) return "";

            if (value instanceof Date) {
                const year = value.getFullYear();
                const month = String(value.getMonth() + 1).padStart(2, "0");
                const day = String(value.getDate()).padStart(2, "0");
                return this.formatDate(`${year}-${month}-${day}`);
            }

            if (typeof value === "string") {
                return this.formatDate(value);
            }

            return "";
        },

        /**
         * Convert Date object, time object hoặc string sang HH:mm hoặc HH:mm:ss
         */
        convertToTimeString(value) {
            if (!value) return "";

            // Nếu là object từ v-time-picker: { hours, minutes, seconds }
            if (typeof value === "object" && value.hours !== undefined) {
                const hours = String(value.hours).padStart(2, "0");
                const minutes = String(value.minutes || 0).padStart(2, "0");
                const seconds = String(value.seconds || 0).padStart(2, "0");
                return this.useSeconds
                    ? `${hours}:${minutes}:${seconds}`
                    : `${hours}:${minutes}`;
            }

            // Nếu là Date object
            if (value instanceof Date) {
                const hours = String(value.getHours()).padStart(2, "0");
                const minutes = String(value.getMinutes()).padStart(2, "0");
                const seconds = String(value.getSeconds()).padStart(2, "0");
                return this.useSeconds
                    ? `${hours}:${minutes}:${seconds}`
                    : `${hours}:${minutes}`;
            }

            // Nếu là string
            if (typeof value === "string") {
                return this.useSeconds ? value : value.substring(0, 5);
            }

            return "";
        },

        // ==================== FORMATTING HELPERS ====================

        /**
         * Format date string YYYY-MM-DD theo dateFormat prop
         */
        formatDate(dateString) {
            if (!dateString) return "";

            const [year, month, day] = dateString.split("-");
            return this.dateFormat === "dd/mm/yyyy"
                ? `${day}/${month}/${year}`
                : `${year}/${month}/${day}`;
        },

        // ==================== PARSING HELPERS ====================

        /**
         * Parse datetime từ nhiều định dạng khác nhau
         * Trả về object { date: "YYYY-MM-DD", time: "HH:mm:ss" }
         */
        parseDateTime(value) {
            if (!value) return null;

            // Nếu là string
            if (typeof value === "string") {
                // ISO format: YYYY-MM-DDTHH:mm:ss
                if (value.match(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/)) {
                    const [datePart, timePart] = value.split("T");
                    return {
                        date: datePart,
                        time:
                            timePart.length === 5 ? `${timePart}:00` : timePart,
                    };
                }

                // SQL format: YYYY-MM-DD HH:mm:ss
                if (value.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/)) {
                    const [datePart, timePart] = value.split(" ");
                    return {
                        date: datePart,
                        time:
                            timePart.length === 5 ? `${timePart}:00` : timePart,
                    };
                }
            }

            // Fallback: parse as Date object
            const d = new Date(value);
            if (isNaN(d.getTime())) return null;

            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, "0");
            const day = String(d.getDate()).padStart(2, "0");
            const hours = String(d.getHours()).padStart(2, "0");
            const minutes = String(d.getMinutes()).padStart(2, "0");
            const seconds = String(d.getSeconds()).padStart(2, "0");

            return {
                date: `${year}-${month}-${day}`,
                time: `${hours}:${minutes}:${seconds}`,
            };
        },

        // ==================== EVENT HANDLERS ====================

        /**
         * Xử lý khi click nút OK
         */
        handleConfirm() {
            if (!this.internalDate || !this.internalTime) {
                this.menuOpen = false;
                return;
            }

            // Convert date sang YYYY-MM-DD
            let dateString;
            if (this.internalDate instanceof Date) {
                const year = this.internalDate.getFullYear();
                const month = String(this.internalDate.getMonth() + 1).padStart(
                    2,
                    "0",
                );
                const day = String(this.internalDate.getDate()).padStart(
                    2,
                    "0",
                );
                dateString = `${year}-${month}-${day}`;
            } else if (typeof this.internalDate === "string") {
                dateString = this.internalDate;
            } else {
                this.menuOpen = false;
                return;
            }

            // Convert time sang HH:mm:ss
            let timeString;
            if (
                typeof this.internalTime === "object" &&
                this.internalTime.hours !== undefined
            ) {
                const hours = String(this.internalTime.hours).padStart(2, "0");
                const minutes = String(this.internalTime.minutes || 0).padStart(
                    2,
                    "0",
                );
                const seconds = String(this.internalTime.seconds || 0).padStart(
                    2,
                    "0",
                );
                timeString = `${hours}:${minutes}:${seconds}`;
            } else if (this.internalTime instanceof Date) {
                const hours = String(this.internalTime.getHours()).padStart(
                    2,
                    "0",
                );
                const minutes = String(this.internalTime.getMinutes()).padStart(
                    2,
                    "0",
                );
                const seconds = String(this.internalTime.getSeconds()).padStart(
                    2,
                    "0",
                );
                timeString = `${hours}:${minutes}:${seconds}`;
            } else if (typeof this.internalTime === "string") {
                timeString = this.internalTime;
                // Đảm bảo có giây
                if (timeString.length === 5) {
                    timeString += ":00";
                }
            } else {
                this.menuOpen = false;
                return;
            }

            // Tạo datetime string theo outputFormat
            const datetimeString =
                this.outputFormat === "iso"
                    ? `${dateString}T${timeString}`
                    : `${dateString} ${timeString}`;

            // Emit giá trị mới
            this.$emit("update:modelValue", datetimeString);

            // Đóng menu
            this.menuOpen = false;
        },

        /**
         * Xử lý khi click nút Hủy
         */
        handleCancel() {
            this.menuOpen = false;
        },

        /**
         * Xử lý khi click nút Clear
         */
        handleClear() {
            this.internalDate = null;
            this.internalTime = null;
            this.$emit("update:modelValue", "");
        },
    },
};
</script>

<style scoped></style>
