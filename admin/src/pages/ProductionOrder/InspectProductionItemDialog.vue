<template>
    <v-dialog
        :model-value="modelValue"
        max-width="1600"
        scrollable
        persistent
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between py-3 px-4">
                <span class="font-weight-bold text-h6 text-grey-darken-3">
                    {{ $t("production_order.inspection.title", { product: productName }) }}
                </span>
                <v-btn icon="mdi-close" variant="text" size="small" @click="$emit('update:modelValue', false)" />
            </v-card-title>

            <v-divider />

            <v-card-text v-if="item" class="pa-4">
                <v-expansion-panels v-model="openedItems" multiple variant="accordion" class="mb-4">
                    <v-expansion-panel class="border rounded">
                        <v-expansion-panel-title class="bg-grey-lighten-4 py-3 px-4">
                            <div class="d-flex align-center justify-space-between flex-wrap ga-2 w-100 pr-4">
                                <div class="d-flex align-center ga-2">
                                    <v-chip size="small" color="primary" variant="flat">1</v-chip>
                                    <span class="text-subtitle-1 font-weight-bold text-grey-darken-3">{{ productName }}</span>
                                </div>
                                <div class="d-flex align-center flex-wrap ga-2">
                                    <span class="text-grey-darken-2 d-flex align-center font-weight-medium"><v-icon icon="mdi-package-variant-closed-check" color="primary" class="mr-2" size="24" />{{ $t("production_order.inspection.expected") }}:</span>
                                    <v-chip color="primary" variant="tonal" class="font-weight-medium px-3"><strong class="text-primary font-weight-bold">{{ formatQty(item.plannedBaseQuantity) }}</strong></v-chip>
                                    <v-chip v-if="hasPlannedSupplement" color="info" variant="tonal" class="font-weight-medium px-3">{{ $t("production_order.inspection.production_target") }}: <strong class="ml-1">{{ formatQty(productionTarget) }}</strong></v-chip>
                                    <v-chip color="secondary" variant="tonal" class="font-weight-medium px-3">{{ baseUnitLabel }}</v-chip>
                                    <v-chip :color="balanceColor" variant="flat" class="font-weight-bold text-uppercase px-3 elevation-1">{{ balanceLabel }}</v-chip>
                                </div>
                            </div>
                        </v-expansion-panel-title>
                        <v-expansion-panel-text class="pt-4 bg-white">
                            <div class="pa-2">
                <v-sheet rounded="lg" border color="blue-lighten-5" class="pa-4 mb-4">
                    <v-row dense>
                        <v-col cols="12" sm="6" md="2"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.planned") }}</div><div class="font-weight-bold">{{ formatQty(item.plannedBaseQuantity) }} {{ baseUnitLabel }}</div></v-col>
                        <v-col cols="12" sm="6" md="2"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.minimum_target") }}</div><div class="font-weight-bold">{{ formatQty(minimumTarget) }} {{ baseUnitLabel }}</div></v-col>
                        <v-col cols="12" sm="6" md="2"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.previous_received") }}</div><div class="font-weight-bold">{{ formatQty(item.acceptedBaseQuantity) }} {{ baseUnitLabel }}</div></v-col>
                        <v-col cols="12" sm="6" md="2"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.external_fulfilled") }}</div><div class="font-weight-bold">{{ formatQty(externalFulfilled) }} {{ baseUnitLabel }}</div></v-col>
                        <v-col v-if="hasPlannedSupplement" cols="12" sm="6" md="2"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.production_target") }}</div><div class="font-weight-bold text-primary">{{ formatQty(productionTarget) }} {{ baseUnitLabel }}</div></v-col>
                        <v-col v-if="hasPlannedSupplement" cols="12" sm="6" md="2"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.planned_supplement") }}</div><div class="font-weight-bold text-info">{{ formatQty(plannedSupplement) }} {{ baseUnitLabel }}</div></v-col>
                    </v-row>
                </v-sheet>

                <v-expansion-panels v-model="openedLots" multiple variant="accordion" class="mb-4">
                    <v-expansion-panel v-for="(lot, lotIndex) in lots" :key="lot.clientLineUuid" class="border rounded mb-3">
                        <v-expansion-panel-title class="py-3 px-4 bg-grey-lighten-4">
                            <div class="d-flex align-center justify-space-between flex-wrap ga-2 w-100 pr-3">
                                <div class="d-flex align-center ga-2">
                                    <v-chip size="small" color="primary" variant="flat">{{ lotIndex + 1 }}</v-chip>
                                    <span class="font-weight-bold">{{ lotTitle(lot, lotIndex) }}</span>
                                </div>
                                <div class="d-flex align-center ga-2">
                                    <v-chip size="small" color="success" variant="tonal">{{ $t("production_order.inspection.accepted") }}: {{ formatQty(lot.acceptedQuantity) }}</v-chip>
                                    <v-chip size="small" color="error" variant="tonal">{{ $t("production_order.inspection.rejected") }}: {{ formatQty(lotRejected(lot)) }}</v-chip>
                                    <v-btn icon="mdi-delete" color="error" variant="text" size="small" :disabled="lots.length === 1" @click.stop="removeLot(lotIndex)" />
                                </div>
                            </div>
                        </v-expansion-panel-title>

                        <v-expansion-panel-text class="pt-4 bg-white">
                            <v-row dense>
                                <v-col cols="12" sm="6" md="3">
                                    <div class="mb-2 font-weight-medium">{{ $t("production_order.inspection.received_current") }} <span class="text-red">*</span></div>
                                    <v-text-field v-model.number="lot.receivedQuantity" type="number" min="0" variant="outlined" density="compact" hide-details="auto" :error-messages="lotReceivedError(lot)" />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <div class="mb-2 font-weight-medium">{{ $t("production_order.inspection.accepted_current") }} <span class="text-red">*</span></div>
                                    <v-text-field v-model.number="lot.acceptedQuantity" type="number" min="0" variant="outlined" density="compact" hide-details="auto" :error-messages="lotAcceptedError(lot)" />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <div class="mb-2 font-weight-medium">{{ $t("field.rejected_quantity") }}</div>
                                    <v-text-field :model-value="formatQty(lotRejected(lot))" variant="outlined" density="compact" hide-details readonly bg-color="grey-lighten-4" />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <div class="mb-2 font-weight-medium">{{ $t("field.unit") }}</div>
                                    <v-text-field :model-value="baseUnitLabel" variant="outlined" density="compact" hide-details readonly bg-color="grey-lighten-4" />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <div class="mb-2 font-weight-medium">{{ $t("field.manufacture_date") }} <span class="text-red">*</span></div>
                                    <DatePicker v-model="lot.manufactureDate" density="compact" hide-details="auto" :error-messages="showErrors && !lot.manufactureDate ? requiredMsg($t('field.manufacture_date')) : ''" />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <div class="mb-2 font-weight-medium">{{ $t("field.expiry_date") }} <span class="text-red">*</span></div>
                                    <DatePicker v-model="lot.expiryDate" density="compact" hide-details="auto" :error-messages="lotExpiryError(lot)" />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <div class="mb-2 font-weight-medium">{{ $t("production_order.inspection.production_lot_code") }}</div>
                                    <v-text-field v-model="lot.productionLotCode" variant="outlined" density="compact" hide-details="auto" />
                                </v-col>
                                <v-col cols="12" sm="6" md="3">
                                    <div class="mb-2 font-weight-medium">{{ $t("base.note") }}</div>
                                    <v-text-field v-model="lot.note" variant="outlined" density="compact" hide-details="auto" />
                                </v-col>
                                <v-col v-if="lotRejected(lot) > 0" cols="12">
                                    <div class="mb-2 font-weight-medium">{{ $t("field.rejection_reason") }} <span class="text-red">*</span></div>
                                    <v-textarea v-model="lot.rejectionReason" variant="outlined" density="compact" rows="2" auto-grow hide-details="auto" :error-messages="showErrors && !lot.rejectionReason ? requiredMsg($t('field.rejection_reason')) : ''" />
                                </v-col>
                            </v-row>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>

                <v-btn size="small" color="primary" variant="tonal" prepend-icon="mdi-plus" @click="addLot">{{ $t("production_order.inspection.add_lot") }}</v-btn>

                <v-sheet rounded="lg" border class="pa-4 mt-4 bg-grey-lighten-5">
                    <div class="font-weight-bold mb-3">{{ $t("production_order.inspection.total_inspection") }}</div>
                    <v-row dense>
                        <v-col cols="12" sm="4"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.total_received") }}</div><v-chip color="info" variant="tonal">{{ formatQty(totalReceived) }} {{ baseUnitLabel }}</v-chip></v-col>
                        <v-col cols="12" sm="4"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.total_accepted") }}</div><v-chip color="success" variant="tonal">{{ formatQty(totalAccepted) }} {{ baseUnitLabel }}</v-chip></v-col>
                        <v-col cols="12" sm="4"><div class="text-caption text-medium-emphasis">{{ $t("production_order.inspection.total_rejected") }}</div><v-chip color="error" variant="tonal">{{ formatQty(totalRejected) }} {{ baseUnitLabel }}</v-chip></v-col>
                    </v-row>
                    <div class="text-body-2 mt-3">{{ $t("production_order.inspection.effective_after") }}: <strong>{{ formatQty(effectiveAfter) }} {{ baseUnitLabel }}</strong></div>
                </v-sheet>

                <v-card v-if="hasShortage" variant="tonal" color="warning" class="pa-4 mt-4">
                    <div class="font-weight-bold mb-1">{{ $t("production_order.inspection.shortage_resolution") }}</div>
                    <div class="text-body-2 mb-2">{{ $t("production_order.inspection.shortage_description") }}</div>
                    <v-radio-group v-model="shortageResolution" hide-details="auto" :error-messages="showErrors && !shortageResolution ? $t('production_order.inspection.shortage_required') : ''">
                        <v-radio value="ACCEPT_SHORTAGE" :label="$t('production_order.inspection.accept_shortage')" />
                        <v-radio value="SUPPLEMENT_LATER" :label="$t('production_order.inspection.supplement_later')" />
                    </v-radio-group>
                </v-card>
                            </div>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>
            </v-card-text>

            <v-divider />
            <v-card-actions class="pa-4 justify-end">
                <v-btn variant="text" :disabled="submitting" @click="$emit('update:modelValue', false)">{{ $t("base.cancel") }}</v-btn>
                <v-btn color="primary" variant="flat" :loading="submitting" :disabled="submitting" @click="submit">{{ $t("production_order.inspection.confirm") }}</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import { mapActions } from "vuex";
import DatePicker from "@/components/DatePicker.vue";
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "InspectProductionItemDialog",
    components: { DatePicker },
    props: {
        modelValue: { type: Boolean, default: false },
        item: { type: Object, default: null },
    },
    emits: ["update:modelValue", "posted"],
    data() {
        return {
            lots: [],
            openedItems: [0],
            openedLots: [0],
            shortageResolution: null,
            showErrors: false,
            submitting: false,
        };
    },
    computed: {
        productName() { return this.item?.finishedProduct?.name || this.item?.productSnapshot?.name || "--"; },
        baseUnitLabel() { return this.item?.baseUnit?.name || this.item?.productSnapshot?.baseUnitName || ""; },
        minimumTarget() { return Number(this.item?.plannedBaseQuantity || 0) * (1 - Number(this.item?.expectedWastePercent || 0) / 100); },
        productionTarget() { return Number(this.item?.supplementData?.productionTargetBaseQuantity || this.item?.plannedBaseQuantity || 0); },
        plannedSupplement() { return Math.max(this.productionTarget - Number(this.item?.plannedBaseQuantity || 0), 0); },
        hasPlannedSupplement() { return this.plannedSupplement > 0; },
        externalFulfilled() { return Number(this.item?.shortageData?.externalFulfilledBaseQuantity || 0); },
        totalReceived() { return this.lots.reduce((total, lot) => total + (Number(lot.receivedQuantity) || 0), 0); },
        totalAccepted() { return this.lots.reduce((total, lot) => total + (Number(lot.acceptedQuantity) || 0), 0); },
        totalRejected() { return this.lots.reduce((total, lot) => total + this.lotRejected(lot), 0); },
        acceptedAfter() { return Number(this.item?.acceptedBaseQuantity || 0) + this.totalAccepted; },
        effectiveAfter() { return Number(this.item?.acceptedBaseQuantity || 0) + this.externalFulfilled + this.totalAccepted; },
        hasShortage() { return this.effectiveAfter < this.minimumTarget; },
        hasIncompleteProductionTarget() { return this.hasPlannedSupplement && this.acceptedAfter < this.productionTarget; },
        balanceStatus() {
            if (this.hasShortage) return "shortage";
            if (this.hasIncompleteProductionTarget) return "partial_target";
            const balanceTarget = this.hasPlannedSupplement
                ? this.productionTarget
                : Number(this.item?.plannedBaseQuantity || 0);
            const actual = this.hasPlannedSupplement ? this.acceptedAfter : this.effectiveAfter;
            return actual > balanceTarget ? "surplus" : "balanced";
        },
        balanceColor() { return { shortage: "error", partial_target: "warning", surplus: "warning", balanced: "success" }[this.balanceStatus] || "grey"; },
        balanceLabel() { return this.$t(`production_order.inspection.${this.balanceStatus}`); },
    },
    watch: {
        modelValue(isOpen) {
            if (isOpen) this.resetForm();
        },
    },
    methods: {
        ...mapActions("productionOrder", ["inspectItem"]),
        resetForm() {
            this.lots = [this.createEmptyLot()];
            this.openedItems = [0];
            this.openedLots = [0];
            this.shortageResolution = null;
            this.showErrors = false;
        },
        createEmptyLot() {
            return { clientLineUuid: crypto.randomUUID(), receivedQuantity: null, acceptedQuantity: null, manufactureDate: "", expiryDate: "", productionLotCode: "", rejectionReason: "", note: "" };
        },
        addLot() {
            this.lots.push(this.createEmptyLot());
            this.openedLots = [...this.openedLots, this.lots.length - 1];
        },
        removeLot(index) { this.lots.splice(index, 1); },
        lotRejected(lot) { return (Number(lot.receivedQuantity) || 0) - (Number(lot.acceptedQuantity) || 0); },
        lotTitle(lot, index) {
            if (!lot.manufactureDate || !lot.expiryDate) return this.$t("production_order.inspection.lot", { index: index + 1 });
            return `${this.$t("production_order.inspection.lot", { index: index + 1 })}: ${functionHelper.formatDate(lot.manufactureDate)} - ${functionHelper.formatDate(lot.expiryDate)}`;
        },
        lotReceivedError(lot) {
            if (!this.showErrors) return "";
            return Number(lot.receivedQuantity) > 0 ? "" : this.requiredMsg(this.$t("production_order.inspection.received_current"));
        },
        lotAcceptedError(lot) {
            if (this.lotRejected(lot) < 0) return this.$t("production_order.inspection.accepted_must_not_exceed");
            if (!this.showErrors) return "";
            return lot.acceptedQuantity === null || lot.acceptedQuantity === "" || Number(lot.acceptedQuantity) < 0 ? this.requiredMsg(this.$t("production_order.inspection.accepted_current")) : "";
        },
        lotExpiryError(lot) {
            if (!this.showErrors) return "";
            if (!lot.expiryDate) return this.requiredMsg(this.$t("field.expiry_date"));
            if (lot.manufactureDate && lot.expiryDate <= lot.manufactureDate) return this.$t("production_order.inspection.expiry_after_manufacture");
            return this.isDuplicateDates(lot) ? this.$t("production_order.inspection.duplicate_lot_dates") : "";
        },
        isDuplicateDates(lot) {
            if (!lot.manufactureDate || !lot.expiryDate) return false;
            return this.lots.some((other) => other.clientLineUuid !== lot.clientLineUuid && other.manufactureDate === lot.manufactureDate && other.expiryDate === lot.expiryDate);
        },
        requiredMsg(field) { return this.$t("validation.mixed.required", { field }); },
        validate() {
            if (!this.lots.length) return false;
            for (const lot of this.lots) {
                if (Number(lot.receivedQuantity) <= 0 || lot.acceptedQuantity === null || lot.acceptedQuantity === "" || Number(lot.acceptedQuantity) < 0 || this.lotRejected(lot) < 0 || !lot.manufactureDate || !lot.expiryDate || lot.expiryDate <= lot.manufactureDate || this.isDuplicateDates(lot) || (this.lotRejected(lot) > 0 && !lot.rejectionReason)) return false;
            }
            return !this.hasShortage || !!this.shortageResolution;
        },
        async submit() {
            this.showErrors = true;
            if (!this.validate()) return;
            this.submitting = true;
            try {
                const result = await this.inspectItem({
                    id: this.item.id,
                    values: {
                        clientRequestUuid: crypto.randomUUID(),
                        shortageResolution: this.hasShortage ? this.shortageResolution : null,
                        lots: this.lots.map((lot) => ({
                            ...lot,
                            receivedQuantity: Number(lot.receivedQuantity),
                            acceptedQuantity: Number(lot.acceptedQuantity),
                            rejectionReason: lot.rejectionReason || null,
                            productionLotCode: lot.productionLotCode || null,
                            note: lot.note || null,
                        })),
                    },
                });
                if (result) {
                    this.$emit("posted");
                    this.$emit("update:modelValue", false);
                }
            } finally {
                this.submitting = false;
            }
        },
        formatQty(value) {
            if (value === null || value === undefined || value === "" || Number.isNaN(Number(value))) return "0";
            return parseFloat(Number(value).toFixed(6)).toString();
        },
    },
};
</script>
