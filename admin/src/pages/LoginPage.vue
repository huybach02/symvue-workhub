<template>
    <VeeForm
        :validation-schema="schema"
        :initial-values="initialValues"
        @submit="onSubmit"
    >
        <VeeField v-slot="{ field, errorMessage }" name="email">
            <v-text-field
                v-bind="field"
                :error-messages="errorMessage"
                class="mb-4"
                label="Email"
                prepend-inner-icon="mdi-account"
                variant="outlined"
                persistent-placeholder
            />
        </VeeField>

        <VeeField v-slot="{ field, errorMessage }" name="password">
            <v-text-field
                v-bind="field"
                :error-messages="errorMessage"
                type="password"
                :label="$t('auth.password')"
                prepend-inner-icon="mdi-lock"
                variant="outlined"
                persistent-placeholder
            />
        </VeeField>

        <VeeField
            v-slot="{ field }"
            name="rememberMe"
            type="checkbox"
            :value="true"
            :unchecked-value="false"
        >
            <v-checkbox
                v-bind="field"
                color="primary"
                :label="$t('auth.remember_me')"
                hide-details
                class="mb-3"
            />
        </VeeField>

        <v-btn
            :loading="this.$store.state.isLoading"
            color="primary"
            :text="$t('auth.sign_in')"
            type="submit"
            block
            size="large"
        />

        <v-btn
            color="primary"
            variant="text"
            :text="$t('auth.forgot_password')"
            type="button"
            block
            class="mt-5"
            @click="$router.push('/forgot-password')"
        />
    </VeeForm>
</template>

<script>
import { Form, Field } from "vee-validate";
import { loginSchema } from "@/utils/schemas/auth";
import { authService } from "@/services/authService";
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";

export default {
    name: "LoginPage",
    components: {
        VeeForm: Form,
        VeeField: Field,
    },
    data() {
        return {
            appName: import.meta.env.VITE_APP_NAME,
            subName: import.meta.env.VITE_APP_SUBNAME,
            schema: loginSchema,
            loading: false,
            initialValues: {
                email: import.meta.env.VITE_EMAIL_ACCOUNT_DEFAULT || "",
                password: import.meta.env.VITE_PASSWORD_ACCOUNT_DEFAULT || "",
                rememberMe: false,
            },
        };
    },
    methods: {
        async onSubmit(values) {
            this.$store.commit("setIsLoading");
            const response = await authService.login(values);

            if (response.success) {
                this.$router.push({ name: NAME_ROUTES_CONFIG.dashboard });
            }

            if (response.code === "VERIFY_OTP") {
                const dataLogin = {
                    email: values.email,
                    password: values.password,
                };
                this.$store.commit("auth/SET_DATA_LOGIN", dataLogin);
                this.$router.push({ name: NAME_ROUTES_CONFIG.verifyOtp });
            }

            this.$store.commit("unsetIsLoading");
        },
    },
};
</script>
