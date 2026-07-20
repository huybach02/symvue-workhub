<template>
    <v-dialog
        :model-value="modelValue"
        max-width="1600"
        scrollable
        persistent
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <v-card>
            <v-card-title
                class="d-flex align-center justify-space-between py-3 px-4"
            >
                <span class="font-weight-bold text-h6 text-grey-darken-3">
                    {{ $t("stock_receipt.inspection.title") || "Kiểm hàng" }}:
                    {{ provider?.providerSnapshot?.name || "--" }}
                </span>
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    @click="$emit('update:modelValue', false)"
                />
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-4">
                <div
                    v-if="items.length === 0"
                    class="text-center text-grey py-8"
                >
                    {{ $t("stock_receipt.inspection.items_required") }}
                </div>

                <!-- Card cho từng merchandise -->
                <v-expansion-panels variant="accordion" multiple class="mb-4">
                    <v-expansion-panel
                        v-for="(item, itemIndex) in items"
                        :key="item.receiptItemId"
                        class="border rounded mb-4"
                    >
                        <v-expansion-panel-title
                            class="bg-grey-lighten-4 py-3 px-4"
                        >
                            <div
                                class="d-flex align-center justify-space-between flex-wrap ga-2 w-100 pr-4"
                            >
                                <div class="d-flex align-center ga-2">
                                    <v-chip
                                        size="small"
                                        color="primary"
                                        variant="flat"
                                    >
                                        {{ itemIndex + 1 }}
                                    </v-chip>
                                    <span
                                        class="text-subtitle-1 font-weight-bold text-grey-darken-3"
                                    >
                                        {{ item.merchandiseName }}
                                    </span>
                                </div>
                                <div class="d-flex align-center flex-wrap ga-2">
                                    <span
                                        class="text-grey-darken-2 d-flex align-center font-weight-medium"
                                    >
                                        <v-icon
                                            icon="mdi-package-variant-closed-check"
                                            color="primary"
                                            class="mr-2"
                                            size="24"
                                        />
                                        {{
                                            $t(
                                                "stock_receipt.inspection.expected",
                                            )
                                        }}:
                                    </span>

                                    <template
                                        v-for="(
                                            part, partIndex
                                        ) in getQtyBreakdownList(
                                            item,
                                            item.expectedBaseQuantity,
                                        )"
                                        :key="partIndex"
                                    >
                                        <span
                                            v-if="partIndex > 0"
                                            class="text-grey-darken-1 font-weight-bold"
                                        >
                                            =
                                        </span>
                                        <v-chip
                                            color="primary"
                                            variant="tonal"
                                            class="font-weight-medium px-3"
                                        >
                                            <strong
                                                class="text-primary font-weight-bold"
                                            >
                                                {{ part.qty }}
                                            </strong>
                                        </v-chip>
                                        <v-chip
                                            color="secondary"
                                            variant="tonal"
                                            class="font-weight-medium px-3"
                                        >
                                            {{ part.unitLabel }}
                                        </v-chip>
                                    </template>

                                    <!-- Trạng thái -->
                                    <v-chip
                                        :color="
                                            balanceColor(balanceStatus(item))
                                        "
                                        variant="flat"
                                        class="font-weight-bold text-uppercase px-3 elevation-1"
                                    >
                                        {{
                                            $t(
                                                "stock_receipt.inspection." +
                                                    balanceStatus(item),
                                            )
                                        }}
                                    </v-chip>
                                </div>
                            </div>
                        </v-expansion-panel-title>

                        <v-expansion-panel-text class="pt-4 bg-white">
                            <div class="pa-2">
                                <!-- Card cho từng lô -->
                                <v-expansion-panels
                                    variant="accordion"
                                    multiple
                                    class="mb-3"
                                >
                                    <v-expansion-panel
                                        v-for="(lot, lotIndex) in item.lots"
                                        :key="lot.clientLineUuid"
                                        class="border rounded mb-3"
                                    >
                                        <v-expansion-panel-title class="py-3">
                                            <span
                                                class="font-weight-bold text-subtitle-2"
                                            >
                                                <template
                                                    v-if="
                                                        lot.manufactureDate &&
                                                        lot.expiryDate
                                                    "
                                                >
                                                    Lô
                                                    {{
                                                        formatDate(
                                                            lot.manufactureDate,
                                                        )
                                                    }}
                                                    -
                                                    {{
                                                        formatDate(
                                                            lot.expiryDate,
                                                        )
                                                    }}
                                                </template>
                                                <template v-else>
                                                    {{
                                                        $t(
                                                            "stock_receipt.inspection.lot_label",
                                                            {
                                                                index:
                                                                    lotIndex +
                                                                    1,
                                                            },
                                                        )
                                                    }}
                                                </template>
                                            </span>
                                            <v-spacer />
                                            <v-btn
                                                icon="mdi-delete"
                                                color="error"
                                                variant="text"
                                                size="small"
                                                class="mr-2"
                                                :disabled="
                                                    item.lots.length === 1
                                                "
                                                @click.stop="
                                                    removeLot(item, lotIndex)
                                                "
                                            />
                                        </v-expansion-panel-title>

                                        <v-expansion-panel-text
                                            class="pt-4 bg-white"
                                        >
                                            <v-row dense>
                                                <v-col cols="12" sm="6" md="3">
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t(
                                                                "field.received_quantity",
                                                            ) || "Số lượng nhận"
                                                        }}
                                                        <span class="text-red">
                                                            *
                                                        </span>
                                                    </div>
                                                    <v-text-field
                                                        v-model.number="
                                                            lot.receivedQuantity
                                                        "
                                                        type="number"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details="auto"
                                                        :error-messages="
                                                            lotReceivedError(
                                                                lot,
                                                            )
                                                        "
                                                    />
                                                </v-col>
                                                <v-col cols="12" sm="6" md="3">
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t("field.unit") ||
                                                            "Đơn vị"
                                                        }}
                                                        <span class="text-red">
                                                            *
                                                        </span>
                                                    </div>
                                                    <v-select
                                                        v-model="
                                                            lot.receivedUnitId
                                                        "
                                                        :items="
                                                            getUnitOptions(item)
                                                        "
                                                        item-title="label"
                                                        item-value="value"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details="auto"
                                                        :loading="loadingUnits"
                                                        :placeholder="
                                                            $t(
                                                                'field.select_unit',
                                                            ) || 'Chọn đơn vị'
                                                        "
                                                        :error-messages="
                                                            showErrors &&
                                                            !lot.receivedUnitId
                                                                ? requiredMsg(
                                                                      $t(
                                                                          'field.unit',
                                                                      ),
                                                                  )
                                                                : ''
                                                        "
                                                    />
                                                </v-col>
                                                <v-col cols="12" sm="6" md="3">
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t(
                                                                "field.accepted_quantity",
                                                            ) ||
                                                            "Số lượng chấp nhận"
                                                        }}
                                                        <span class="text-red">
                                                            *
                                                        </span>
                                                    </div>
                                                    <v-text-field
                                                        v-model.number="
                                                            lot.acceptedQuantity
                                                        "
                                                        type="number"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details="auto"
                                                        :error-messages="
                                                            lotAcceptedError(
                                                                lot,
                                                            )
                                                        "
                                                    />
                                                </v-col>
                                                <v-col cols="12" sm="6" md="3">
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t(
                                                                "field.rejected_quantity",
                                                            ) ||
                                                            "Số lượng từ chối"
                                                        }}
                                                    </div>
                                                    <v-text-field
                                                        :model-value="
                                                            formatQty(
                                                                lotRejected(
                                                                    lot,
                                                                ),
                                                            )
                                                        "
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        readonly
                                                        bg-color="grey-lighten-4"
                                                    />
                                                </v-col>
                                                <v-col cols="12" sm="6" md="3">
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t(
                                                                "field.manufacture_date",
                                                            ) || "Ngày sản xuất"
                                                        }}
                                                        <span class="text-red">
                                                            *
                                                        </span>
                                                    </div>
                                                    <DatePicker
                                                        v-model="
                                                            lot.manufactureDate
                                                        "
                                                        density="compact"
                                                        hide-details="auto"
                                                        :error-messages="
                                                            showErrors &&
                                                            !lot.manufactureDate
                                                                ? requiredMsg(
                                                                      $t(
                                                                          'field.manufacture_date',
                                                                      ),
                                                                  )
                                                                : ''
                                                        "
                                                    />
                                                </v-col>
                                                <v-col cols="12" sm="6" md="3">
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t(
                                                                "field.expiry_date",
                                                            ) || "Hạn sử dụng"
                                                        }}
                                                        <span class="text-red">
                                                            *
                                                        </span>
                                                    </div>
                                                    <DatePicker
                                                        v-model="lot.expiryDate"
                                                        density="compact"
                                                        hide-details="auto"
                                                        :error-messages="
                                                            lotExpiryError(lot)
                                                        "
                                                    />
                                                </v-col>
                                                <v-col cols="12" sm="6" md="3">
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t(
                                                                "field.supplier_lot_code",
                                                            ) || "Mã lô NCC"
                                                        }}
                                                    </div>
                                                    <v-text-field
                                                        v-model="
                                                            lot.supplierLotCode
                                                        "
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details="auto"
                                                    />
                                                </v-col>
                                                <v-col cols="12" sm="6" md="3">
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t(
                                                                "field.ghi_chu",
                                                            ) || "Ghi chú"
                                                        }}
                                                    </div>
                                                    <v-text-field
                                                        v-model="lot.note"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details="auto"
                                                    />
                                                </v-col>
                                                <v-col
                                                    v-if="lotRejected(lot) > 0"
                                                    cols="12"
                                                >
                                                    <div
                                                        class="mb-2 font-weight-medium"
                                                    >
                                                        {{
                                                            $t(
                                                                "field.rejection_reason",
                                                            ) || "Lý do từ chối"
                                                        }}
                                                        <span class="text-red">
                                                            *
                                                        </span>
                                                    </div>
                                                    <v-textarea
                                                        v-model="
                                                            lot.rejectionReason
                                                        "
                                                        variant="outlined"
                                                        density="compact"
                                                        rows="2"
                                                        auto-grow
                                                        hide-details="auto"
                                                        :error-messages="
                                                            showErrors &&
                                                            !lot.rejectionReason
                                                                ? requiredMsg(
                                                                      $t(
                                                                          'field.rejection_reason',
                                                                      ) ||
                                                                          'Lý do từ chối',
                                                                  )
                                                                : ''
                                                        "
                                                    />
                                                </v-col>
                                            </v-row>

                                            <!-- Hint quy đổi về base unit -->
                                            <div class="mt-4">
                                                <div
                                                    class="text-caption text-grey-darken-1 font-weight-medium mb-2"
                                                >
                                                    {{
                                                        $t(
                                                            "stock_receipt.inspection.base_convert",
                                                        )
                                                    }}:
                                                </div>
                                                <div
                                                    class="d-flex flex-column ga-2 pl-3"
                                                >
                                                    <!-- Nhận -->
                                                    <div
                                                        class="d-flex align-center flex-wrap ga-2"
                                                    >
                                                        <span
                                                            class="text-caption text-grey-darken-1 font-weight-medium"
                                                            style="
                                                                min-width: 120px;
                                                            "
                                                        >
                                                            {{
                                                                $t(
                                                                    "stock_receipt.inspection.total_received",
                                                                )
                                                            }}:
                                                        </span>
                                                        <template
                                                            v-for="(
                                                                part, partIndex
                                                            ) in getQtyBreakdownList(
                                                                item,
                                                                toBase(
                                                                    item,
                                                                    lot.receivedQuantity,
                                                                    lot.receivedUnitId,
                                                                ),
                                                            )"
                                                            :key="
                                                                'received-' +
                                                                partIndex
                                                            "
                                                        >
                                                            <span
                                                                v-if="
                                                                    partIndex >
                                                                    0
                                                                "
                                                                class="text-grey-darken-1 font-weight-bold"
                                                            >
                                                                =
                                                            </span>
                                                            <v-chip
                                                                color="info"
                                                                variant="tonal"
                                                                class="font-weight-medium px-2"
                                                                size="small"
                                                            >
                                                                <strong>{{
                                                                    part.qty
                                                                }}</strong>
                                                            </v-chip>
                                                            <v-chip
                                                                color="secondary"
                                                                variant="tonal"
                                                                class="font-weight-medium px-2"
                                                                size="small"
                                                            >
                                                                {{
                                                                    part.unitLabel
                                                                }}
                                                            </v-chip>
                                                        </template>
                                                    </div>

                                                    <!-- Chấp nhận -->
                                                    <div
                                                        class="d-flex align-center flex-wrap ga-2"
                                                    >
                                                        <span
                                                            class="text-caption text-grey-darken-1 font-weight-medium"
                                                            style="
                                                                min-width: 120px;
                                                            "
                                                        >
                                                            {{
                                                                $t(
                                                                    "stock_receipt.inspection.total_accepted",
                                                                )
                                                            }}:
                                                        </span>
                                                        <template
                                                            v-for="(
                                                                part, partIndex
                                                            ) in getQtyBreakdownList(
                                                                item,
                                                                toBase(
                                                                    item,
                                                                    lot.acceptedQuantity,
                                                                    lot.receivedUnitId,
                                                                ),
                                                            )"
                                                            :key="
                                                                'accepted-' +
                                                                partIndex
                                                            "
                                                        >
                                                            <span
                                                                v-if="
                                                                    partIndex >
                                                                    0
                                                                "
                                                                class="text-grey-darken-1 font-weight-bold"
                                                            >
                                                                =
                                                            </span>
                                                            <v-chip
                                                                color="success"
                                                                variant="tonal"
                                                                class="font-weight-medium px-2"
                                                                size="small"
                                                            >
                                                                <strong>{{
                                                                    part.qty
                                                                }}</strong>
                                                            </v-chip>
                                                            <v-chip
                                                                color="secondary"
                                                                variant="tonal"
                                                                class="font-weight-medium px-2"
                                                                size="small"
                                                            >
                                                                {{
                                                                    part.unitLabel
                                                                }}
                                                            </v-chip>
                                                        </template>
                                                    </div>

                                                    <!-- Từ chối -->
                                                    <div
                                                        class="d-flex align-center flex-wrap ga-2"
                                                    >
                                                        <span
                                                            class="text-caption text-grey-darken-1 font-weight-medium"
                                                            style="
                                                                min-width: 120px;
                                                            "
                                                        >
                                                            {{
                                                                $t(
                                                                    "stock_receipt.inspection.total_rejected",
                                                                )
                                                            }}:
                                                        </span>
                                                        <template
                                                            v-for="(
                                                                part, partIndex
                                                            ) in getQtyBreakdownList(
                                                                item,
                                                                toBase(
                                                                    item,
                                                                    lotRejected(
                                                                        lot,
                                                                    ),
                                                                    lot.receivedUnitId,
                                                                ),
                                                            )"
                                                            :key="
                                                                'rejected-' +
                                                                partIndex
                                                            "
                                                        >
                                                            <span
                                                                v-if="
                                                                    partIndex >
                                                                    0
                                                                "
                                                                class="text-grey-darken-1 font-weight-bold"
                                                            >
                                                                =
                                                            </span>
                                                            <v-chip
                                                                color="error"
                                                                variant="tonal"
                                                                class="font-weight-medium px-2"
                                                                size="small"
                                                            >
                                                                <strong>{{
                                                                    part.qty
                                                                }}</strong>
                                                            </v-chip>
                                                            <v-chip
                                                                color="secondary"
                                                                variant="tonal"
                                                                class="font-weight-medium px-2"
                                                                size="small"
                                                            >
                                                                {{
                                                                    part.unitLabel
                                                                }}
                                                            </v-chip>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <v-alert
                                                v-if="
                                                    showErrors &&
                                                    isDuplicateDates(item, lot)
                                                "
                                                type="error"
                                                density="compact"
                                                variant="tonal"
                                                class="mt-4 text-caption"
                                            >
                                                {{
                                                    $t(
                                                        "stock_receipt.inspection.duplicate_lot_dates",
                                                    )
                                                }}
                                            </v-alert>
                                        </v-expansion-panel-text>
                                    </v-expansion-panel>
                                </v-expansion-panels>

                                <v-btn
                                    color="primary"
                                    size="small"
                                    prepend-icon="mdi-plus"
                                    @click="addLot(item)"
                                >
                                    {{ $t("stock_receipt.inspection.add_lot") }}
                                </v-btn>

                                <!-- Tổng kết theo base unit -->
                                <v-sheet
                                    variant="flat"
                                    color="grey-lighten-4"
                                    border
                                    rounded="lg"
                                    class="pa-3 mt-4"
                                >
                                    <div
                                        class="d-flex align-center font-weight-bold text-subtitle-2 text-grey-darken-3 mb-2"
                                    >
                                        {{
                                            $t(
                                                "stock_receipt.inspection.total_convert_summary",
                                            )
                                        }}
                                    </div>

                                    <div class="d-flex flex-column ga-2 pl-1">
                                        <!-- Nhận -->
                                        <div
                                            class="d-flex align-center flex-wrap ga-2"
                                        >
                                            <span
                                                class="text-caption text-grey-darken-1 font-weight-medium"
                                                style="min-width: 120px"
                                            >
                                                {{
                                                    $t(
                                                        "stock_receipt.inspection.total_received",
                                                    )
                                                }}:
                                            </span>
                                            <template
                                                v-for="(
                                                    part, partIndex
                                                ) in getQtyBreakdownList(
                                                    item,
                                                    totalReceivedBase(item),
                                                )"
                                                :key="
                                                    'summary-rec-' + partIndex
                                                "
                                            >
                                                <span
                                                    v-if="partIndex > 0"
                                                    class="text-grey-darken-1 font-weight-bold"
                                                >
                                                    =
                                                </span>
                                                <v-chip
                                                    color="info"
                                                    variant="flat"
                                                    class="font-weight-medium px-2"
                                                    size="small"
                                                >
                                                    <strong>{{
                                                        part.qty
                                                    }}</strong>
                                                </v-chip>
                                                <v-chip
                                                    color="secondary"
                                                    variant="tonal"
                                                    class="font-weight-medium px-2"
                                                    size="small"
                                                >
                                                    {{ part.unitLabel }}
                                                </v-chip>
                                            </template>
                                        </div>

                                        <!-- Chấp nhận -->
                                        <div
                                            class="d-flex align-center flex-wrap ga-2"
                                        >
                                            <span
                                                class="text-caption text-grey-darken-1 font-weight-medium"
                                                style="min-width: 120px"
                                            >
                                                {{
                                                    $t(
                                                        "stock_receipt.inspection.total_accepted",
                                                    )
                                                }}:
                                            </span>
                                            <template
                                                v-for="(
                                                    part, partIndex
                                                ) in getQtyBreakdownList(
                                                    item,
                                                    totalAcceptedBase(item),
                                                )"
                                                :key="
                                                    'summary-acc-' + partIndex
                                                "
                                            >
                                                <span
                                                    v-if="partIndex > 0"
                                                    class="text-grey-darken-1 font-weight-bold"
                                                >
                                                    =
                                                </span>
                                                <v-chip
                                                    color="success"
                                                    variant="flat"
                                                    class="font-weight-medium px-2"
                                                    size="small"
                                                >
                                                    <strong>{{
                                                        part.qty
                                                    }}</strong>
                                                </v-chip>
                                                <v-chip
                                                    color="secondary"
                                                    variant="tonal"
                                                    class="font-weight-medium px-2"
                                                    size="small"
                                                >
                                                    {{ part.unitLabel }}
                                                </v-chip>
                                            </template>
                                        </div>

                                        <!-- Từ chối -->
                                        <div
                                            class="d-flex align-center flex-wrap ga-2"
                                        >
                                            <span
                                                class="text-caption text-grey-darken-1 font-weight-medium"
                                                style="min-width: 120px"
                                            >
                                                {{
                                                    $t(
                                                        "stock_receipt.inspection.total_rejected",
                                                    )
                                                }}:
                                            </span>
                                            <template
                                                v-for="(
                                                    part, partIndex
                                                ) in getQtyBreakdownList(
                                                    item,
                                                    totalRejectedBase(item),
                                                )"
                                                :key="
                                                    'summary-rej-' + partIndex
                                                "
                                            >
                                                <span
                                                    v-if="partIndex > 0"
                                                    class="text-grey-darken-1 font-weight-bold"
                                                >
                                                    =
                                                </span>
                                                <v-chip
                                                    color="error"
                                                    variant="flat"
                                                    class="font-weight-medium px-2"
                                                    size="small"
                                                >
                                                    <strong>{{
                                                        part.qty
                                                    }}</strong>
                                                </v-chip>
                                                <v-chip
                                                    color="secondary"
                                                    variant="tonal"
                                                    class="font-weight-medium px-2"
                                                    size="small"
                                                >
                                                    {{ part.unitLabel }}
                                                </v-chip>
                                            </template>
                                        </div>
                                    </div>
                                </v-sheet>
                            </div>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>

                <!-- Xử lý thiếu hàng -->
                <v-card
                    v-if="hasShortage"
                    variant="tonal"
                    color="warning"
                    class="pa-4"
                >
                    <div class="font-weight-bold mb-1">
                        {{ $t("stock_receipt.inspection.shortage_resolution") }}
                    </div>
                    <div class="text-body-2 mb-2">
                        {{
                            $t("stock_receipt.inspection.shortage_description")
                        }}
                    </div>
                    <v-radio-group
                        v-model="shortageResolution"
                        hide-details="auto"
                        :error-messages="
                            showErrors && !shortageResolution
                                ? $t(
                                      'stock_receipt.inspection.shortage_required',
                                  )
                                : ''
                        "
                    >
                        <v-radio
                            value="ACCEPT_SHORTAGE"
                            :label="
                                $t('stock_receipt.inspection.accept_shortage')
                            "
                        />
                        <v-radio
                            value="CREATE_BACKORDER"
                            :label="
                                $t('stock_receipt.inspection.create_backorder')
                            "
                        />
                    </v-radio-group>
                </v-card>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4">
                <v-btn color="primary" variant="flat" @click="submit">
                    {{ $t("stock_receipt.inspection.submit") }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import { mapActions } from "vuex";
import DatePicker from "@/components/DatePicker.vue";
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "InspectStockReceiptDialog",
    components: {
        DatePicker,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        provider: {
            type: Object,
            default: null,
        },
    },
    emits: ["update:modelValue", "saved"],
    data() {
        return {
            items: [],
            unitOptionsMap: {},
            loadingUnits: false,
            showErrors: false,
            shortageResolution: null,
        };
    },
    computed: {
        hasShortage() {
            return this.items.some(
                (item) => this.balanceStatus(item) === "shortage",
            );
        },
    },
    watch: {
        modelValue(isOpen) {
            if (isOpen) {
                this.showErrors = false;
                this.shortageResolution = null;
                this.buildItems();
                this.loadUnitOptions();
            }
        },
    },
    methods: {
        ...mapActions("merchandise", {
            fetchMerchandiseDetail: "fetchItemDetail",
        }),

        // Khởi tạo danh sách merchandise, mỗi merchandise tạo sẵn 1 lô trống
        buildItems() {
            this.items = (this.provider?.items || []).map((row) => ({
                receiptItemId: row.id,
                merchandiseId: row.merchandiseId,
                merchandiseName: row.merchandiseNameSnapshot,
                expectedQuantity: Number(row.expectedQuantity) || 0,
                expectedFactorToBase: Number(row.expectedFactorToBase) || null,
                expectedBaseQuantity: Number(row.expectedBaseQuantity) || 0,
                expectedUnitLabel: row.expectedUnitLabelSnapshot,
                baseUnitLabel: row.baseUnitLabelSnapshot,
                defaultUnitId: row.expectedUnitId,
                lots: [this.createEmptyLot(row)],
            }));
        },
        createEmptyLot(row = {}) {
            return {
                clientLineUuid: functionHelper.createRandomString(6),
                receivedQuantity: Number(row.expectedQuantity) || null,
                receivedUnitId: row.expectedUnitId || null,
                acceptedQuantity: Number(row.expectedQuantity) || null,
                rejectionReason: "",
                manufactureDate: "",
                expiryDate: "",
                supplierLotCode: "",
                note: "",
            };
        },
        addLot(item) {
            item.lots.push(
                this.createEmptyLot({ expectedUnitId: item.defaultUnitId }),
            );
        },
        removeLot(item, lotIndex) {
            item.lots.splice(lotIndex, 1);
        },

        // Lấy đơn vị (kèm hệ số quy đổi về base) của từng merchandise
        async loadUnitOptions() {
            this.loadingUnits = true;
            try {
                const map = {};
                await Promise.all(
                    this.items.map(async (item) => {
                        if (map[item.merchandiseId]) return;
                        const detail = await this.fetchMerchandiseDetail({
                            id: item.merchandiseId,
                        });
                        map[item.merchandiseId] = this.extractUnitOptions(
                            detail,
                            item,
                        );
                    }),
                );
                this.unitOptionsMap = map;
            } finally {
                this.loadingUnits = false;
            }
        },
        // Ưu tiên snapshot của đơn vị trên phiếu nếu cấu hình hiện tại đã đổi
        extractUnitOptions(detail, item) {
            const providerConfig = (detail?.providers || []).find(
                (provider) =>
                    String(provider.providerId) ===
                    String(this.provider?.providerId),
            );
            const options = new Map();

            (providerConfig?.units || []).forEach((unit) => {
                const factor = Number(unit.factorToBase);
                if (unit.value && Number.isFinite(factor) && factor > 0) {
                    options.set(String(unit.value), {
                        value: unit.value,
                        label: unit.label,
                        factor,
                    });
                }
            });

            if (item.defaultUnitId && item.expectedFactorToBase !== null) {
                options.set(String(item.defaultUnitId), {
                    value: item.defaultUnitId,
                    label: item.expectedUnitLabel,
                    factor: item.expectedFactorToBase,
                });
            }

            return [...options.values()];
        },
        getUnitOptions(item) {
            return this.unitOptionsMap[item.merchandiseId] || [];
        },
        getFactor(item, unitId) {
            const unit = this.getUnitOptions(item).find(
                (option) => String(option.value) === String(unitId),
            );
            if (unit) return unit.factor;
            return 1;
        },
        toBase(item, quantity, unitId) {
            return (Number(quantity) || 0) * this.getFactor(item, unitId);
        },

        // Số lượng từ chối = số lượng nhận - số lượng chấp nhận
        lotRejected(lot) {
            return (
                (Number(lot.receivedQuantity) || 0) -
                (Number(lot.acceptedQuantity) || 0)
            );
        },
        totalReceivedBase(item) {
            return item.lots.reduce(
                (sum, lot) =>
                    sum +
                    this.toBase(item, lot.receivedQuantity, lot.receivedUnitId),
                0,
            );
        },
        totalAcceptedBase(item) {
            return item.lots.reduce(
                (sum, lot) =>
                    sum +
                    this.toBase(item, lot.acceptedQuantity, lot.receivedUnitId),
                0,
            );
        },
        totalRejectedBase(item) {
            return item.lots.reduce(
                (sum, lot) =>
                    sum +
                    this.toBase(
                        item,
                        this.lotRejected(lot),
                        lot.receivedUnitId,
                    ),
                0,
            );
        },
        // So sánh tổng chấp nhận với yêu cầu để ra trạng thái cân bằng
        balanceStatus(item) {
            if (!this.unitOptionsMap[item.merchandiseId]) {
                return "balanced";
            }
            const accepted = Number(this.totalAcceptedBase(item).toFixed(6));
            const expected = Number(item.expectedBaseQuantity.toFixed(6));
            if (accepted < expected) return "shortage";
            if (accepted > expected) return "surplus";
            return "balanced";
        },
        balanceColor(status) {
            const colors = {
                shortage: "error",
                surplus: "warning",
                balanced: "success",
            };
            return colors[status] || "grey";
        },

        // Check trùng cặp NSX/HSD trong cùng một merchandise
        isDuplicateDates(item, lot) {
            if (!lot.manufactureDate || !lot.expiryDate) return false;
            return item.lots.some(
                (other) =>
                    other.clientLineUuid !== lot.clientLineUuid &&
                    other.manufactureDate === lot.manufactureDate &&
                    other.expiryDate === lot.expiryDate,
            );
        },

        lotReceivedError(lot) {
            if (!this.showErrors) return "";
            if (!lot.receivedQuantity || +lot.receivedQuantity <= 0) {
                return this.requiredMsg(this.$t("field.received_quantity"));
            }
            return "";
        },
        lotAcceptedError(lot) {
            if (
                lot.receivedQuantity !== null &&
                lot.acceptedQuantity !== null &&
                this.lotRejected(lot) < 0
            ) {
                return this.$t(
                    "stock_receipt.inspection.received_eq_accepted_rejected",
                );
            }
            if (!this.showErrors) return "";
            if (
                lot.acceptedQuantity === null ||
                lot.acceptedQuantity === "" ||
                +lot.acceptedQuantity < 0
            ) {
                return this.requiredMsg(this.$t("field.accepted_quantity"));
            }
            return "";
        },
        lotExpiryError(lot) {
            if (!this.showErrors) return "";
            if (!lot.expiryDate) {
                return this.requiredMsg(this.$t("field.expiry_date"));
            }
            if (lot.manufactureDate && lot.expiryDate <= lot.manufactureDate) {
                return this.$t(
                    "stock_receipt.inspection.expiry_after_manufacture",
                );
            }
            return "";
        },
        requiredMsg(field) {
            return this.$t("validation.mixed.required", { field });
        },

        validate() {
            for (const item of this.items) {
                if (!item.lots.length) return false;
                for (const lot of item.lots) {
                    if (!lot.receivedQuantity || +lot.receivedQuantity <= 0)
                        return false;
                    if (!lot.receivedUnitId) return false;
                    if (
                        lot.acceptedQuantity === null ||
                        lot.acceptedQuantity === "" ||
                        +lot.acceptedQuantity < 0 ||
                        this.lotRejected(lot) < 0
                    )
                        return false;
                    if (this.lotRejected(lot) > 0 && !lot.rejectionReason)
                        return false;
                    if (!lot.manufactureDate || !lot.expiryDate) return false;
                    if (lot.expiryDate <= lot.manufactureDate) return false;
                    if (this.isDuplicateDates(item, lot)) return false;
                }
            }
            if (this.hasShortage && !this.shortageResolution) return false;
            return true;
        },

        submit() {
            this.showErrors = true;
            if (!this.validate()) return;

            // Gom dữ liệu kiểm hàng để gửi BE
            const payload = {
                shortageResolution: this.hasShortage
                    ? this.shortageResolution
                    : null,
                items: this.items.map((item) => ({
                    receiptItemId: item.receiptItemId,
                    lots: item.lots.map((lot) => ({
                        clientLineUuid: lot.clientLineUuid,
                        receivedQuantity: Number(lot.receivedQuantity),
                        receivedUnitId: lot.receivedUnitId,
                        acceptedQuantity: Number(lot.acceptedQuantity),
                        rejectedQuantity: this.lotRejected(lot),
                        rejectionReason: lot.rejectionReason || null,
                        manufactureDate: lot.manufactureDate,
                        expiryDate: lot.expiryDate,
                        supplierLotCode: lot.supplierLotCode || null,
                        note: lot.note || null,
                    })),
                })),
            };

            this.$emit("saved", payload);
            this.$emit("update:modelValue", false);
        },

        formatQty(val) {
            if (val === null || val === undefined || val === "" || isNaN(val))
                return "--";
            return parseFloat(Number(val).toFixed(4)).toString();
        },

        formatDate(date) {
            return functionHelper.formatDate(date);
        },

        getQtyBreakdownList(item, baseQty) {
            const isNegative = baseQty < 0;
            const absoluteQty = Math.abs(baseQty);

            const units = [...this.getUnitOptions(item)];
            const hasBaseUnit = units.some((u) => u.factor === 1);
            if (!hasBaseUnit && item.baseUnitLabel) {
                units.push({
                    value: "base",
                    label: item.baseUnitLabel,
                    factor: 1,
                });
            }

            units.sort((a, b) => b.factor - a.factor);

            return units.map((unit) => {
                const converted = absoluteQty / unit.factor;
                const formattedVal = parseFloat(converted.toFixed(4));
                return {
                    qty: `${isNegative ? "-" : ""}${this.formatQty(formattedVal)}`,
                    unitLabel: unit.label,
                };
            });
        },
    },
};
</script>
