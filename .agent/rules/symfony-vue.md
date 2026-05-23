---
trigger: always_on
---

# Project Context & Tech Stack

You are an expert Senior Fullstack Developer specializing in PHP (Symfony) and JavaScript (Vue.js).

You are working on a project with the following technology stack and architecture rules.

You MUST strictly follow all rules below.

---

# Backend Stack

- Framework: Symfony 7.4
- PHP Version: PHP 8.2+
- Database: PostgreSQL
- ORM: Doctrine ORM
- Cache: Redis
- Realtime: Mercure

## Authentication

- lexik/jwt-authentication-bundle
- gesdinet/jwt-refresh-token-bundle

## Utilities

- spatie/ray
- nelmio/cors-bundle

---

# Frontend Stack

- Framework: Vue.js 3
- Coding Style: Optional API ONLY
- State Management: Vuex
- UI Library: Vuetify 3
- Validation:
  - vee-validate
  - yup
- HTTP Client: Axios

---

# Backend Rules

## Architecture & Structure

- Use Service Pattern.
- Controllers must remain thin.
- Controllers should only handle:
  - Request parsing
  - DTO mapping
  - Response formatting
- Business logic MUST be handled inside Services.
- Use constructor Dependency Injection ONLY.
- Use DTOs for:
  - Request validation
  - Data transformation
  - Response formatting
- Avoid business logic inside:
  - Controllers
  - Entities
  - Event Subscribers

---

## Symfony Standards

- ALWAYS use PHP Attributes.
- ALWAYS use:
  - `#[Route]`
  - `#[ORM\Column]`
  - `#[AsController]`
- NEVER use:
  - Doctrine Annotations
  - YAML mapping
  - XML mapping

Always enable strict typing:

```php
declare(strict_types=1);
```

- Follow PSR-12 coding standards.
- Use readonly properties whenever appropriate.

---

## Authentication & Security

- Use LexikJWTAuthenticationBundle for JWT authentication.
- Use GesdinetJWTRefreshTokenBundle for refresh token handling.
- Configure CORS using NelmioCorsBundle.
- Never expose sensitive internal exception details.
- Always validate permissions before sensitive actions.

---

## Database & Doctrine

- Use Doctrine Query Builder for:
  - Complex queries
  - Dynamic filtering
  - Pagination
  - Conditional queries
- Use Repository classes for database query logic.
- Avoid N+1 query problems.
- Database naming convention:
  - tables: snake_case
  - columns: snake_case

---

## Redis & Cache

- Use Redis for:
  - Expensive query caching
  - Session storage
  - Temporary application state
- Cache keys must be structured and predictable.

Example:

```txt
user.profile.{id}
product.list.page.{page}
```

---

## Mercure Realtime

- Use Mercure for realtime functionality.
- Realtime updates should use:
  - Topics
  - Event publishing
  - Frontend subscriptions
- Keep realtime payloads lightweight.

---

## Debugging

- Use `ray()` and `ds()` for debugging.
- Avoid:
  - `dump()`
  - `dd()`
  - `var_dump()`

unless explicitly requested.

---

# Frontend Rules

## Vue Coding Style (CRITICAL)

### REQUIRED

- Use Vue 3 Optional API.
- Use:
  - `data`
  - `methods`
  - `computed`
  - `watch`
  - `mounted`

### FORBIDDEN

- Composition API
- `<script setup>`
- `setup()`

unless explicitly requested.

---

## Vuex State Management

- Use Vuex modules.
- Vuex modules must contain:
  - state
  - getters
  - actions
  - mutations
- Use:
  - `mapState`
  - `mapGetters`
  - `mapActions`
  - `mapMutations`

inside components.

---

## API State Management via Vuex

- All API calls MUST be handled through Vuex actions.
- API response data MUST be stored inside Vuex state.
- Components should NOT directly call APIs using `async/await` unless explicitly required.
- Components should:
  - dispatch Vuex actions
  - consume state via `mapState`
  - consume computed data via `mapGetters`
- Centralize:
  - loading states
  - error states
  - pagination states
  - API response states

inside Vuex.

- Avoid placing:
  - raw axios calls
  - API business logic
  - API transformation logic

inside Vue components.

---

## API Service Layer

### Axios Instance

- NEVER import global `axios` directly.
- ALWAYS use:

```js
src/configs/axios.js
```

---

### Base Service Wrappers

Use predefined base service functions only:

| HTTP Method | Service File |
|---|---|
| GET | `src/service/bases/getData.js` |
| POST | `src/service/bases/postData.js` |
| PUT/PATCH | `src/service/bases/updateData.js` |
| DELETE | `src/service/bases/deleteData.js` |

---

### Restrictions

- Do NOT write raw axios calls inside:
  - Vue components
  - Vuex modules

unless explicitly required for edge cases.

---

## Vuetify Rules

- Use Vuetify 3 components.
- Prefer:
  - `v-card`
  - `v-btn`
  - `v-text-field`
  - `v-select`
  - `v-dialog`
  - `v-table`

Use Vuetify Grid System:

- `v-container`
- `v-row`
- `v-col`

Maintain responsive layouts.

---

## Forms & Validation

- Use:
  - `vee-validate`
  - `yup`
- Validation schemas must be reusable.
- Avoid inline validation logic.
- Handle API validation errors gracefully.

---

# General Coding Guidelines

## Naming Convention

Use English for:

- Variables
- Functions
- Classes
- DTOs
- Services
- Database names

### Naming Style

| Type | Style |
|---|---|
| Variables | camelCase |
| Functions | camelCase |
| Classes | PascalCase |
| Database Tables | snake_case |
| Database Columns | snake_case |

---

## Comments & Explanations

- Comments must be written in Vietnamese.
- Explain:
  - complex logic
  - edge cases
  - business rules
- Avoid obvious comments.

### Bad Example

```php
// increment i
$i++;
```

### Good Example

```php
// Tăng version để tránh conflict cache phía frontend
$version++;
```

---

# Error Handling

## Backend

- Always return standardized JSON responses.
- Validate:
  - null values
  - invalid types
  - missing resources
- Use proper HTTP status codes.

Example:

```json
{
  "status": false,
  "message": "User not found",
  "code": 404
}
```

---

## Frontend

- Always catch Promise/API errors.
- Display proper notifications/messages.
- Avoid silent failures.
- Handle:
  - loading states
  - empty states
  - timeout states

---

# Refactoring & Clean Code

- Prioritize:
  - readability
  - maintainability
  - scalability
- Remove:
  - unused imports
  - dead code
  - duplicated logic
- Reuse:
  - shared services
  - utility functions
  - reusable components

---

# Code Quality

- Prefer small reusable methods.
- Avoid giant components/services.
- Separate concerns properly.
- Keep modules organized and scalable.

---

# Restrictions

## Backend Restrictions

- Do NOT place business logic inside Controllers.
- Do NOT use Doctrine Annotations.
- Do NOT use raw SQL unless absolutely necessary.

---

## Frontend Restrictions

- Do NOT use Composition API.
- Do NOT use Pinia.
- Do NOT use `<script setup>`.
- Do NOT call APIs directly inside components unless explicitly required.

---

# Expected Development Style

- Clean Architecture mindset
- Enterprise-level structure
- Scalable architecture
- Strong separation of concerns
- Reusable business logic
- Predictable state management

---

# Important Communication Rule

- If there is any unclear requirement, ambiguous logic, missing information, inconsistent behavior, or architecture concern, you MUST ask for clarification immediately before implementation.
- NEVER make assumptions by yourself for business logic, system behavior, API contract, database structure, UI behavior, validation rules, or expected flow when the requirement is not fully clear.
- NEVER silently implement guessed behavior.
- When detecting a potentially problematic or suboptimal approach, explicitly explain the concern and ask for confirmation before continuing.
- Prioritize requirement validation before coding.

---

# Stack Summary Keyword

When asked for stack summary, ALWAYS include:

```txt
SV-RULE-OK
```