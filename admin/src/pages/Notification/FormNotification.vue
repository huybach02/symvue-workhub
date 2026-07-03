<template>
    <div>
        <VeeForm
            v-if="mode === 'create' || (mode === 'update' && item)"
            ref="formRef"
            as="form"
            :validation-schema="validationSchema"
            :initial-values="initialValues"
            @submit="handleSubmit"
        >
            <v-row>
                <v-col cols="12">
                    <v-row>
                        <!-- Trường "Gửi đến" -->
                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="sendTo"
                            >
                                <div class="mb-2">
                                    {{ $t("field.sendTo") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-select
                                    :model-value="field.value"
                                    :items="sendToOptions"
                                    item-title="text"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.sendTo')}`"
                                    @update:model-value="
                                        (value) => {
                                            handleChange(value);
                                            handleSendToChange(value);
                                        }
                                    "
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Autocomplete chọn bộ phận (hiện khi sendTo = 'department') -->
                        <v-col
                            v-if="selectedSendTo === 'department'"
                            cols="12"
                            md="4"
                        >
                            <VeeField
                                v-slot="{ field, errorMessage, handleChange }"
                                name="departmentId"
                            >
                                <div class="mb-2">
                                    {{ $t("field.bo_phan") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-autocomplete
                                    :model-value="field.value"
                                    name="departmentId"
                                    :items="boPhanData"
                                    item-title="label"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    clearable
                                    :loading="isLoadingBoPhan"
                                    :placeholder="`${$t('base.enter')} ${$t('field.bo_phan')}`"
                                    @update:model-value="handleChange"
                                    @blur="field.onBlur"
                                />
                            </VeeField>
                        </v-col>

                        <!-- Autocomplete chọn người dùng (hiện khi sendTo = 'user') -->
                        <v-col
                            v-if="selectedSendTo === 'user'"
                            cols="12"
                            md="4"
                        >
                            <VeeField
                                v-slot="{ field, errorMessage, handleChange }"
                                name="userId"
                            >
                                <div class="mb-2">
                                    {{ $t("field.nguoi_nhan") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-autocomplete
                                    :model-value="field.value"
                                    name="userId"
                                    :items="userData"
                                    item-title="label"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    clearable
                                    :loading="isLoadingUser"
                                    :placeholder="`${$t('base.enter')} ${$t('field.nguoi_nhan')}`"
                                    @update:model-value="handleChange"
                                    @blur="field.onBlur"
                                />
                            </VeeField>
                        </v-col>

                        <v-col cols="12" md="4">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="type"
                            >
                                <div class="mb-2">
                                    {{ $t("field.type") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-select
                                    :model-value="field.value"
                                    :items="typeOptions"
                                    item-title="text"
                                    item-value="value"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.type')}`"
                                    @update:model-value="handleChange"
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-col>

                <!-- Trường tên thông báo (dòng mới) -->
                <v-col cols="12">
                    <v-row>
                        <v-col cols="12">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="title"
                            >
                                <div class="mb-2">
                                    {{ $t("field.title") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.title')}`"
                                />
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-col>

                <v-col cols="12">
                    <v-row>
                        <v-col cols="12">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="body"
                            >
                                <div class="mb-2">
                                    {{ $t("field.content") }}
                                    <span class="text-red"> * </span>
                                </div>
                                <v-textarea
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    :placeholder="`${$t('base.enter')} ${$t('field.content')}`"
                                />
                            </VeeField>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>

            <!-- Nút cancel và create/update -->
            <div class="sticky-actions-bar">
                <div class="d-flex justify-end ga-2">
                    <v-btn color="grey" @click="handleCancel">
                        {{ $t("button.cancel") }}
                    </v-btn>
                    <v-btn
                        color="primary"
                        type="submit"
                        :loading="this.$store.state.isLoading"
                    >
                        {{ submitButtonText }}
                    </v-btn>
                </div>
            </div>
        </VeeForm>
        <LoadingForm v-if="mode === 'update' && !item" :is-loading="true" />
    </div>
</template>

<script>
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { constant } from "@/utils/constants/constant";
import LoadingForm from "@/components/LoadingForm.vue";
import { thongBaoSchema } from "@/utils/schemas/thongBao";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        LoadingForm,
        VeeForm,
        VeeField,
    },
    props: {
        submitButtonText: {
            type: String,
            default: "",
        },
        item: {
            type: Object,
            default: null,
        },
        mode: {
            type: String,
            default: "create",
        },
    },
    emits: ["submit", "cancel"],
    data() {
        return {
            validationSchema: thongBaoSchema,
            initialValues: {
                sendTo: "all",
                type: "primary",
                departmentId: null,
                userId: null,
                title: "",
                body: "",
            },
            selectedSendTo: "all",
        };
    },
    computed: {
        ...mapGetters("notification", [
            "activeUserOptions",
            "activeUserOptionsLoading",
            "departmentOptions",
            "departmentOptionsLoading",
        ]),
        boPhanData() {
            return this.departmentOptions;
        },
        isLoadingBoPhan() {
            return this.departmentOptionsLoading;
        },
        userData() {
            return this.activeUserOptions;
        },
        isLoadingUser() {
            return this.activeUserOptionsLoading;
        },
        sendToOptions() {
            return constant.SEND_TO_OPTIONS.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
        typeOptions() {
            return constant.TYPE_THONG_BAO.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
    },
    watch: {
        item: {
            handler(value) {
                if (value) {
                    // Đồng bộ selectedSendTo khi load dữ liệu update
                    if (value.sendTo) {
                        this.selectedSendTo = value.sendTo;
                        this.fetchDataForSendTo(value.sendTo);
                    }
                    this.$nextTick(() => {
                        if (this.$refs.formRef) {
                            this.$refs.formRef.setValues(value);
                        }
                    });
                }
            },
            deep: true,
            immediate: true,
        },
    },
    methods: {
        ...mapActions("notification", [
            "fetchActiveUserOptions",
            "fetchDepartmentOptions",
        ]),
        handleSubmit(values) {
            this.$emit("submit", values);
        },
        handleCancel() {
            this.$emit("cancel");
        },
        handleSendToChange(value) {
            this.selectedSendTo = value;
            this.fetchDataForSendTo(value);
        },
        async fetchDataForSendTo(value) {
            if (value === "department" && this.boPhanData.length === 0) {
                await this.getBoPhan();
            } else if (value === "user" && this.userData.length === 0) {
                await this.getUsers();
            }
        },
        async getBoPhan() {
            await this.fetchDepartmentOptions();
        },
        async getUsers() {
            await this.fetchActiveUserOptions();
        },
    },
};
</script>

<style scoped></style>
