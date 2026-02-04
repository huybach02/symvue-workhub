<template>
    <VeeForm
        :validation-schema="schema"
        :initial-values="initialValues"
        @submit="onSubmit"
    >
        <VeeField v-slot="{ field, errorMessage }" name="email">
            <v-text-field
                v-model="field.value"
                :error-messages="errorMessage"
                class="mb-4"
                variant="outlined"
                type="email"
                :label="$t('field.email')"
                @update:model-value="field.onChange"
            />
        </VeeField>

        <v-btn
            :loading="this.$store.state.isLoading"
            color="primary"
            :text="$t('base.confirm')"
            type="submit"
            block
            size="large"
        />
    </VeeForm>
</template>

<script>
import { Form, Field } from "vee-validate";
import { forgotPasswordSchema } from "@/utils/schemas/auth";
import { authService } from "@/services/authService";
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";

export default {
    name: "ForgotPasswordPage",
    components: {
        VeeForm: Form,
        VeeField: Field,
    },
    data() {
        return {
            schema: forgotPasswordSchema,
            loading: false,
            initialValues: {
                email: "",
            },
        };
    },
    methods: {
        async onSubmit(values) {
            this.$store.commit("setIsLoading");
            const response = await authService.forgotPassword(values);
            if (response.success) {
                this.$router.push({ name: NAME_ROUTES_CONFIG.login });
            }
            this.$store.commit("unsetIsLoading");
        },
    },
};
</script>
