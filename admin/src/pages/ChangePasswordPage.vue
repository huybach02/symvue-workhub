<template>
    <VeeForm
        :validation-schema="schema"
        :initial-values="initialValues"
        @submit="onSubmit"
    >
        <VeeField v-slot="{ field, errorMessage }" name="password">
            <v-text-field
                v-bind="field"
                :error-messages="errorMessage"
                :label="$t('auth.new_password')"
                class="mb-4"
                variant="outlined"
                prepend-inner-icon="mdi-lock"
                type="password"
            />
        </VeeField>
        <VeeField v-slot="{ field, errorMessage }" name="confirm_password">
            <v-text-field
                v-bind="field"
                :error-messages="errorMessage"
                :label="$t('auth.confirm_password')"
                class="mb-4"
                variant="outlined"
                prepend-inner-icon="mdi-lock"
                type="password"
            />
        </VeeField>

        <v-btn
            :loading="this.$store.state.isLoading"
            color="primary"
            :text="$t('auth.change_password')"
            type="submit"
            block
            size="large"
        />
    </VeeForm>
</template>

<script>
import { Form, Field } from "vee-validate";
import { changePasswordSchema } from "@/utils/schemas/auth";
import { authService } from "@/services/authService";
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";

export default {
    name: "ChangePasswordPage",
    components: {
        VeeForm: Form,
        VeeField: Field,
    },
    data() {
        return {
            schema: changePasswordSchema,
            loading: false,
            initialValues: {
                password: "",
                confirm_password: "",
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
            const response = await authService.changePassword(values);
            if (response.success) {
                this.$router.push({ name: NAME_ROUTES_CONFIG.login });
            }
            this.$store.commit("unsetIsLoading");
        },
    },
};
</script>
