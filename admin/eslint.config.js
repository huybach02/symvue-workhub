import js from "@eslint/js";
import pluginVue from "eslint-plugin-vue";
import globals from "globals";

export default [
    js.configs.recommended,
    ...pluginVue.configs["flat/recommended"],
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
        rules: {
            "no-undef": "error",
            "no-unused-vars": "warn",
            "vue/multi-word-component-names": "off",
            "vue/no-v-for-template-key": "off",
            indent: ["error", 4],
            "vue/html-indent": ["error", 4],
            "vue/script-indent": ["error", 4, { baseIndent: 0 }],
            "vue/max-attributes-per-line": "off",
            "vue/first-attribute-linebreak": "off",
            "vue/singleline-html-element-content-newline": "off",
        },
    },
];
