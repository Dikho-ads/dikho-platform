<script setup lang="ts">
// Create or edit a campaign: name, goal, offer, dates, wallet budget and an optional occasion
// (picking one fills the name and dates). Emits `saved` with the API's campaign.
const props = defineProps<{ campaign?: any | null }>();
const emit = defineEmits<{ saved: [campaign: any]; cancel: [] }>();

const api = useApi();
const toast = useToast();
const editing = computed(() => !!props.campaign?.id);

const today = isoDate();
const inTwoWeeks = isoDate(new Date(Date.now() + 13 * 86400000));
const form = reactive({
    name: props.campaign?.name ?? '',
    goal: props.campaign?.goal ?? '',
    offer: props.campaign?.offer ?? '',
    occasion_id: (props.campaign?.occasion?.id ?? '') as number | '',
    starts_on: props.campaign?.starts_on ?? today,
    ends_on: props.campaign?.ends_on ?? inTwoWeeks,
    budget: props.campaign ? Math.round((props.campaign.budget_paise ?? 0) / 100) : 0,
});
const errors = ref<Record<string, string>>({});
const saving = ref(false);

const occasions = ref<any[]>([]);
const occLoading = ref(true);
onMounted(async () => {
    try {
        occasions.value = (await api<any>('occasions')).data ?? [];
    } catch {
        occasions.value = [];
    } finally {
        occLoading.value = false;
    }
});
function pickOccasion() {
    const o = occasions.value.find((x) => x.id === form.occasion_id);
    if (!o || editing.value) return;
    if (!form.name.trim()) form.name = o.name;
    form.starts_on = o.starts_on < today ? today : o.starts_on;
    form.ends_on = o.ends_on < form.starts_on ? form.starts_on : o.ends_on;
}

async function save() {
    errors.value = {};
    if (!form.name.trim()) {
        errors.value = { name: 'Give the campaign a name.' };
        return;
    }
    saving.value = true;
    try {
        const body = {
            name: form.name.trim(),
            goal: form.goal.trim() || null,
            offer: form.offer.trim() || null,
            occasion_id: form.occasion_id || null,
            starts_on: form.starts_on,
            ends_on: form.ends_on,
            budget_paise: Math.max(0, Math.round(Number(form.budget) || 0)) * 100,
        };
        const res = await api<any>(editing.value ? `campaigns/${props.campaign.id}` : 'campaigns', { method: editing.value ? 'PATCH' : 'POST', body });
        toast.ok(editing.value ? 'Campaign saved' : `“${res.data.name}” created as a draft`);
        emit('saved', res.data);
    } catch (e: any) {
        const err = toApiError(e);
        errors.value = Object.fromEntries(Object.keys(err.errors).map((k) => [k, err.field(k)!]));
        if (errors.value.budget_paise) errors.value.budget = errors.value.budget_paise;
        toast.error(err.message);
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="panel pb rise cf" @submit.prevent="save">
        <div>
            <span class="eyb"><b>{{ editing ? 'EDIT' : 'NEW' }}</b>·<span>{{ editing ? 'Campaign details' : 'Start a campaign' }}</span></span>
        </div>
        <div class="cf-grid">
            <div v-if="!editing" class="fld">
                <label class="lab" for="cf-occ">Occasion (optional)</label>
                <select id="cf-occ" v-model="form.occasion_id" class="in" :disabled="occLoading" @change="pickOccasion">
                    <option value="">{{ occLoading ? 'Loading occasions…' : 'No occasion' }}</option>
                    <option v-for="o in occasions" :key="o.id" :value="o.id">{{ o.name }} · {{ day(o.starts_on) }}{{ o.ends_on !== o.starts_on ? ' – ' + day(o.ends_on) : '' }}</option>
                </select>
                <span class="hint">Picking one fills the name and dates.</span>
            </div>
            <div class="fld">
                <label class="lab" for="cf-name">Name</label>
                <input id="cf-name" v-model="form.name" class="in" maxlength="80" placeholder="e.g. Winter laddoo week" />
                <span v-if="errors.name" class="err">{{ errors.name }}</span>
            </div>
            <div class="fld">
                <label class="lab" for="cf-goal">Goal</label>
                <input id="cf-goal" v-model="form.goal" class="in" maxlength="120" placeholder="e.g. 50 WhatsApp enquiries" />
                <span v-if="errors.goal" class="err">{{ errors.goal }}</span>
            </div>
            <div class="fld">
                <label class="lab" for="cf-offer">Offer</label>
                <input id="cf-offer" v-model="form.offer" class="in" maxlength="160" placeholder="e.g. 250 g free on a 1 kg box" />
                <span v-if="errors.offer" class="err">{{ errors.offer }}</span>
            </div>
            <div class="fld">
                <label class="lab" for="cf-from">Starts</label>
                <input id="cf-from" v-model="form.starts_on" type="date" class="in" />
                <span v-if="errors.starts_on" class="err">{{ errors.starts_on }}</span>
            </div>
            <div class="fld">
                <label class="lab" for="cf-to">Ends</label>
                <input id="cf-to" v-model="form.ends_on" type="date" class="in" :min="form.starts_on" />
                <span v-if="errors.ends_on" class="err">{{ errors.ends_on }}</span>
            </div>
            <div class="fld">
                <label class="lab" for="cf-budget">Wallet budget</label>
                <div class="iw"><span class="pre">₹</span><input id="cf-budget" v-model.number="form.budget" type="number" min="0" step="100" class="in" inputmode="numeric" /></div>
                <span v-if="errors.budget" class="err">{{ errors.budget }}</span>
                <span v-else class="hint">For ads and messages. Nothing is charged until they go out.</span>
            </div>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end">
            <button type="button" class="btn bs" :disabled="saving" @click="emit('cancel')">Cancel</button>
            <button type="submit" class="btn bp" :disabled="saving"><svg v-if="saving" class="i s spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9"></path></svg>{{ saving ? 'Saving…' : editing ? 'Save changes' : 'Create campaign' }}</button>
        </div>
    </form>
</template>

<style scoped>
.cf{display:flex;flex-direction:column;gap:14px}
.cf-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(220px,100%),1fr));gap:12px}
</style>
