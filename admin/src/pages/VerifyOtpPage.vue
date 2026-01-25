<template>
    <VeeForm
        :validation-schema="schema"
        :initial-values="initialValues"
        @submit="onSubmit"
    >
        <VeeField v-slot="{ field, errorMessage }" name="otp">
            <v-otp-input
                v-model="field.value"
                :error-messages="errorMessage"
                class="mb-4"
                variant="outlined"
                type="number"
                length="6"
                @update:model-value="field.onChange"
            />
        </VeeField>

        <v-btn
            :loading="this.$store.state.isLoading"
            color="primary"
            :text="$t('auth.verify_otp')"
            type="submit"
            block
            size="large"
        />
    </VeeForm>
</template>

<script>
import { Form, Field } from "vee-validate";
import { verifyOtpSchema } from "@/utils/schemas/auth";
import { authService } from "@/services/authService";
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";

export default {
    name: "VerifyOtpPage",
    components: {
        VeeForm: Form,
        VeeField: Field,
    },
    data() {
        return {
            schema: verifyOtpSchema,
            loading: false,
            initialValues: {
                otp: "",
            },
        };
    },
    created() {
        if (!this.$store.state.auth.dataLogin.email) {
            this.$router.push({ name: NAME_ROUTES_CONFIG.login });
        }
    },
    methods: {
        async onSubmit(values) {
            this.$store.commit("setIsLoading");
            values.email = this.$store.state.auth.dataLogin.email;
            const response = await authService.verifyOtp(values);
            if (response.success) {
                const res = await authService.login({
                    email: this.$store.state.auth.dataLogin.email,
                    password: this.$store.state.auth.dataLogin.password,
                });
                if (res.success) {
                    this.$router.push({ name: NAME_ROUTES_CONFIG.dashboard });
                }
            }
            this.$store.commit("unsetIsLoading");
        },
    },
};
</script>
