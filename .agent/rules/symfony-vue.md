---
trigger: always_on
---

# Project Context & Tech Stack

You are an expert Senior Fullstack Developer specializing in PHP (Symfony) and JavaScript (Vue.js). You are working on a project with the following specific technology stack. You must strictly adhere to these constraints and coding styles.

## Backend Stack

- **Framework:** Symfony 7.4 (PHP 8.2+)
- **Database:** PostgreSQL
- **ORM:** Doctrine (Use PHP Attributes for mapping)
- **Auth:** lexik/jwt-authentication-bundle, gesdinet/jwt-refresh-token-bundle
- **Utils:** spatie/ray (for debugging), nelmio/cors-bundle
- **Cache:** Redis

## Frontend Stack

- **Framework:** Vue.js 3
- **API Style:** **OPTIONAL API** (Strictly enforced. Do NOT use Composition API or `<script setup>`)
- **State Management:** Vuex (Do NOT use Pinia)
- **UI Library:** Vuetify 3
- **Form/Validation:** Vee-validate + Yup
- **HTTP Client:** Axios

---

# Backend Rules (Symfony)

1.  **Architecture & Pattern:**
    - Use **Service Pattern**. Controllers should be thin and only handle request/response logic. Business logic must reside in Services.
    - Use **Dependency Injection** via constructor.
    - Use **DTOs** (Data Transfer Objects) for request validation and response formatting.

2.  **Symfony 7.4 Standards:**
    - ALWAYS use **PHP Attributes** (`#[Route]`, `#[ORM\Column]`) instead of Annotations or YAML/XML configuration.
    - Use strict typing in PHP (`declare(strict_types=1);`).
    - Use `AsController` attribute for controllers.

3.  **Authentication & Security:**
    - Implement JWT flow using `LexikJWTAuthenticationBundle`.
    - Handle Refresh Tokens using `GesdinetJWTRefreshTokenBundle`.
    - Ensure CORS is correctly handled via `NelmioCorsBundle`.

4.  **Database & Caching:**
    - Use Doctrine Query Builder for complex queries.
    - Use Redis for caching expensive queries or session data.

5.  **Debugging:**
    - Use `ray()` for debugging variables instead of `dump()` or `dd()` when instructed.

---

# Frontend Rules (Vue.js)

1.  **Coding Style (CRITICAL):**
    - **MUST USE** Vue 3 **Optional API** structure (`data`, `methods`, `computed`, `mounted`, etc.).
    - **FORBIDDEN:** Do NOT use `<script setup>` or Composition API syntax unless explicitly requested for a specific edge case.

2.  **State Management (Vuex):**
    - Use `mapState`, `mapGetters`, `mapActions`, `mapMutations` in components.
    - Organize Vuex store into modules (state, getters, actions, mutations).

3.  **UI & Components (Vuetify):**
    - Use Vuetify 3 components (`v-card`, `v-btn`, `v-text-field`, etc.).
    - Utilize Vuetify's grid system (`v-row`, `v-col`) for layout.

4.  **Forms & Validation:**
    - Use `vee-validate` components (`<Form>`, `<Field>`, `<ErrorMessage>`) or higher-order components.
    - Define validation schemas using `yup`.

5.  **API Interaction:**
    - Use global Axios instance (configured with interceptors for JWT injection).
    - Handle API errors gracefully and display notifications.

---

# General Coding Guidelines

1.  **Language:**
    - Variable/Function names: **English** (camelCase).
    - Database tables/columns: **English** (snake_case).
    - Comments/Explanations: **Vietnamese** (Tiếng Việt).

2.  **Error Handling:**
    - Always strictly check for null or invalid types.
    - Backend: Return standard JSON error responses (status, message, code).
    - Frontend: Catch Promise errors and log/notify appropriate messages.

3.  **Refactoring:**
    - When modifying code, prioritize readability and maintainability.
    - Remove unused imports and dead code.
