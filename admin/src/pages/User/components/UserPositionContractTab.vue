<template>
    <div class="d-flex flex-column ga-4">
        <div v-if="loading" class="d-flex justify-center py-8">
            <v-progress-circular indeterminate color="primary" size="48" />
        </div>

        <template v-else>
            <v-alert v-if="!hasUserPosition" type="warning" variant="tonal">
                {{ $t("contract.please_update_position") }}
            </v-alert>

            <template v-else>
                <v-card variant="outlined">
                    <v-card-item>
                        <v-card-title>
                            {{ $t("contract.upload_contract") }}
                        </v-card-title>
                        <v-card-subtitle>
                            {{ $t("contract.format_contract") }}
                        </v-card-subtitle>
                    </v-card-item>

                    <v-divider />

                    <v-card-text>
                        <div class="d-flex flex-column ga-4">
                            <v-file-input
                                v-model="files"
                                accept=".doc,.docx,.pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf"
                                :label="$t('contract.upload_contract')"
                                variant="outlined"
                                prepend-icon="mdi-paperclip"
                                chips
                                multiple
                                show-size
                                hide-details
                            />

                            <div class="d-flex justify-end">
                                <v-btn
                                    color="primary"
                                    prepend-icon="mdi-upload"
                                    :loading="uploading"
                                    :disabled="!files.length"
                                    @click="uploadContracts"
                                >
                                    {{ $t("contract.upload_contract") }}
                                </v-btn>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>

                <v-card variant="outlined">
                    <v-card-item>
                        <v-card-title>
                            {{ $t("contract.contract_list") }}
                        </v-card-title>
                        <v-card-subtitle>
                            {{
                                $t("contract.total_contracts", {
                                    count: contracts.length,
                                })
                            }}
                        </v-card-subtitle>
                    </v-card-item>

                    <v-divider />

                    <v-card-text>
                        <v-table v-if="contracts.length">
                            <thead>
                                <tr>
                                    <th class="text-left">
                                        {{ $t("contract.file_name") }}
                                    </th>
                                    <th class="text-left">
                                        {{ $t("contract.file_format") }}
                                    </th>
                                    <th class="text-left">
                                        {{ $t("contract.file_size") }}
                                    </th>
                                    <th class="text-left">
                                        {{ $t("contract.uploaded_at") }}
                                    </th>
                                    <th class="text-right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(contract, index) in contracts"
                                    :key="`${contract.url}-${index}`"
                                >
                                    <td>{{ contract.name || "--" }}</td>
                                    <td class="text-uppercase">
                                        {{
                                            contract.extension ||
                                            getExtension(contract.name)
                                        }}
                                    </td>
                                    <td>{{ formatFileSize(contract.size) }}</td>
                                    <td>{{ contract.uploadedAt || "--" }}</td>
                                    <td class="text-right">
                                        <v-btn
                                            color="primary"
                                            variant="text"
                                            prepend-icon="mdi-download"
                                            :href="contract.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            :download="contract.name || true"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>

                        <v-empty-state
                            v-else
                            icon="mdi-file-document-outline"
                            :title="$t('contract.no_contract')"
                        />
                    </v-card-text>
                </v-card>
            </template>
        </template>
    </div>
</template>

<script>
import { getDataById } from "@/services/bases/getData";
import { postDataWithFile } from "@/services/bases/postData";

export default {
    props: {
        item: {
            type: Object,
            default: null,
        },
        path: {
            type: String,
            default: "",
        },
        active: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["reload"],
    data() {
        return {
            loading: false,
            uploading: false,
            files: [],
            hasUserPosition: false,
            contracts: [],
        };
    },
    watch: {
        active: {
            async handler(isActive) {
                if (isActive) {
                    await this.loadContracts();
                }
            },
            immediate: true,
        },
    },
    methods: {
        async loadContracts() {
            this.loading = true;

            try {
                const userPosition = await getDataById(
                    this.path,
                    this.item.id,
                    "vi-tri-cong-viec",
                );

                this.hasUserPosition = Boolean(userPosition?.id);
                this.contracts = userPosition?.contracts ?? [];
            } finally {
                this.loading = false;
            }
        },
        async uploadContracts() {
            if (!this.files.length) {
                return;
            }

            this.uploading = true;

            try {
                const formData = new FormData();
                this.files.forEach((file) => {
                    formData.append("files[]", file);
                });

                const response = await postDataWithFile(
                    `${this.path}/${this.item.id}/hop-dong`,
                    formData,
                );

                if (response) {
                    this.contracts = response.contracts ?? [];
                    this.files = [];
                    this.$emit("reload");
                }
            } finally {
                this.uploading = false;
            }
        },
        formatFileSize(size) {
            if (!size) {
                return "--";
            }

            if (size < 1024) {
                return `${size} B`;
            }

            if (size < 1024 * 1024) {
                return `${(size / 1024).toFixed(1)} KB`;
            }

            return `${(size / (1024 * 1024)).toFixed(2)} MB`;
        },
        getExtension(name = "") {
            const parts = String(name).split(".");
            return parts.length > 1 ? parts.pop() : "--";
        },
    },
};
</script>
