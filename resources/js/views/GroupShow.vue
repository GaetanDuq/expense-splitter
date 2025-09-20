<script setup>
import { ref, onMounted, watch } from 'vue'
const nfYen = new Intl.NumberFormat('ja-JP')
const yen = (cents) => nfYen.format(Math.round(Number(cents) / 100))
import { useRoute, useRouter } from 'vue-router'
import { Groups, Members, Expenses } from '../api'

const route = useRoute()
const router = useRouter()
const groupId = ref(Number(route.params.id))

const group = ref(null)
const expenses = ref([])
const loading = ref(false)
const err = ref('')

// forms
const newMemberName = ref('')
const creatingMember = ref(false)

const expense = ref({ payer_id: null, description: '', amount: '', spent_at: '' })
const creatingExpense = ref(false)

const balances = ref(null)
const loadingBalances = ref(false)

/** helper: today as YYYY-MM-DD for input[type=date] */
function today() {
  return new Date().toISOString().slice(0, 10)
}

/** helper: export any object to a .json file (5 lines that impress in demos) */
function exportJson(filename, obj) {
  const data = JSON.stringify(obj, null, 2)
  const blob = new Blob([data], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = Object.assign(document.createElement('a'), { href: url, download: filename })
  a.click(); URL.revokeObjectURL(url)
}

async function load() {
  loading.value = true
  try {
    group.value = await Groups.show(groupId.value)
    expenses.value = await Expenses.list(groupId.value)
    // sensible defaults so the form is ready to go
    expense.value.payer_id = group.value.members?.[0]?.id ?? null
    expense.value.spent_at = today()
  } finally {
    loading.value = false
  }
}

async function addMember() {
  if (!newMemberName.value.trim()) return
  creatingMember.value = true
  try {
    const m = await Members.create(groupId.value, { name: newMemberName.value.trim() })
    group.value.members.unshift(m)
    if (!expense.value.payer_id) expense.value.payer_id = m.id
    newMemberName.value = ''
  } finally {
    creatingMember.value = false
  }
}

async function removeMember(memberId) {
  if (!confirm('Delete this member? Their paid expenses will also be deleted.')) return
  await Members.destroy(memberId)
  // reload group + expenses since cascades may have removed expenses
  await load()
  balances.value = null // force recalculation to avoid stale numbers
}

async function addExpense() {
  err.value = ''
  const amt = Number(expense.value.amount)
  if (!expense.value.payer_id || !expense.value.description.trim() || !amt) {
    err.value = 'Payer, description, and amount are required'; return
  }
  creatingExpense.value = true
  try {
    const saved = await Expenses.create(groupId.value, {
      payer_id: expense.value.payer_id,
      description: expense.value.description.trim(),
      amount: amt,
      spent_at: expense.value.spent_at || null,
    })
    expenses.value.unshift(saved)
    // reset quick form (keep payer and date)
    expense.value.description = ''
    expense.value.amount = ''
  } catch (e) {
    err.value = e?.response?.data?.message || 'Failed to add expense'
  } finally {
    creatingExpense.value = false
  }
}

async function removeExpense(id) {
  if (!confirm('Delete this expense?')) return
  await Expenses.destroy(id)
  expenses.value = expenses.value.filter(e => e.id !== id)
  balances.value = null // balances changed
}

async function fetchBalances() {
  loadingBalances.value = true
  try {
    balances.value = await Groups.balances(groupId.value)
  } finally {
    loadingBalances.value = false
  }
}

function exportBalances() {
  if (!balances.value) return
  exportJson(`${group.value.name}-balances.json`, balances.value)
}

function exportExpenses() {
  // minimal, clean shape for sharing
  const data = expenses.value.map(e => ({
    id: e.id,
    description: e.description,
    payer: e.payer?.name ?? null,
    amount: (e.amount_cents / 100).toFixed(2),
    spent_at: e.spent_at,
  }))
  exportJson(`${group.value.name}-expenses.json`, data)
}

watch(() => route.params.id, async (v) => { groupId.value = Number(v); await load() })
onMounted(load)
</script>

<template>
  <section v-if="!group">Loading…</section>

  <section v-else>
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h2 style="margin:0 0 8px">{{ group.name }}</h2>
      <button @click="router.push('/')" style="font-size:13px;border:1px solid #ddd;background:white;border-radius:8px;padding:6px 10px">← All groups</button>
    </div>

    <div style="display:grid;gap:16px;grid-template-columns: 1fr; margin-top:12px">
      <!-- Members -->
      <div style="border:1px solid #eee;border-radius:12px;padding:12px">
        <h3 style="margin:0 0 8px;font-size:16px">Members</h3>
        <ul style="margin:0;padding-left:16px">
          <li v-for="m in group.members" :key="m.id" style="display:flex;gap:8px;align-items:center">
            <span>{{ m.name }}</span>
            <button @click="removeMember(m.id)" title="Delete member" style="border:none;background:transparent;color:#b00020;cursor:pointer">✕</button>
          </li>
        </ul>
        <form @submit.prevent="addMember" style="display:flex;gap:8px;margin-top:10px">
          <input v-model="newMemberName" placeholder="Add member (e.g., Alice)" style="padding:8px;border:1px solid #ddd;border-radius:8px;flex:1" />
          <button :disabled="creatingMember" style="padding:8px 12px;border:1px solid #333;border-radius:8px;background:#111;color:#fff">
            {{ creatingMember ? 'Adding…' : 'Add' }}
          </button>
        </form>
      </div>

      <!-- Add Expense -->
      <div style="border:1px solid #eee;border-radius:12px;padding:12px">
        <h3 style="margin:0 0 8px;font-size:16px">Add expense</h3>
        <form @submit.prevent="addExpense" style="display:grid;grid-template-columns:1fr 2fr 1fr 1fr;gap:8px;align-items:center">
          <select v-model.number="expense.payer_id" style="padding:8px;border:1px solid #ddd;border-radius:8px">
            <option disabled :value="null">Payer</option>
            <option v-for="m in group.members" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
          <input v-model="expense.description" placeholder="Description (e.g., Dinner)" style="padding:8px;border:1px solid #ddd;border-radius:8px" />
          <input v-model="expense.amount" type="number" step="0.01" min="0" placeholder="Amount" style="padding:8px;border:1px solid #ddd;border-radius:8px" />
          <input v-model="expense.spent_at" type="date" style="padding:8px;border:1px solid #ddd;border-radius:8px" />
          <div style="grid-column: 1 / -1; display:flex; gap:8px; align-items:center">
            <button :disabled="creatingExpense" style="padding:8px 12px;border:1px solid #333;border-radius:8px;background:#111;color:#fff">
              {{ creatingExpense ? 'Saving…' : 'Save expense' }}
            </button>
            <span v-if="err" style="color:#b00020">{{ err }}</span>
          </div>
        </form>
      </div>

      <!-- Expenses list -->
      <div style="border:1px solid #eee;border-radius:12px;padding:12px">
        <h3 style="margin:0 0 8px;font-size:16px">Expenses</h3>
        <div v-if="!expenses.length" style="color:#777">No expenses yet.</div>
        <ul v-else style="list-style:none;padding:0;margin:0;display:grid;gap:8px">
          <li v-for="e in expenses" :key="e.id" style="border:1px solid #f0f0f0;border-radius:10px;padding:10px;display:flex;justify-content:space-between;align-items:center">
            <div>
              <div style="font-weight:600">{{ e.description }}</div>
              <div style="font-size:12px;color:#777">
                Paid by {{ e.payer?.name ?? '—' }}
                <span v-if="e.spent_at"> · {{ e.spent_at }}</span>
              </div>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
              <div style="font-variant-numeric: tabular-nums">¥{{ yen(e.amount_cents) }}</div>
              <button @click="removeExpense(e.id)" title="Delete expense" style="border:none;background:transparent;color:#b00020;cursor:pointer">✕</button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Balances -->
      <div style="border:1px solid #eee;border-radius:12px;padding:12px">
        <div style="display:flex;gap:8px;justify-content:space-between;align-items:center;margin-bottom:8px">
          <h3 style="margin:0;font-size:16px">Balances</h3>
          <div style="display:flex;gap:8px">
            <button @click="fetchBalances" :disabled="loadingBalances"
                    style="padding:6px 10px;border:1px solid #333;border-radius:8px;background:#111;color:#fff">
              {{ loadingBalances ? 'Calculating…' : 'Recalculate' }}
            </button>
            <button v-if="balances" @click="exportBalances"
                    style="padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:white">
              Export balances JSON
            </button>
            <button :disabled="!expenses.length" @click="exportExpenses"
                    style="padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:white">
              Export expenses JSON
            </button>
          </div>
        </div>

        <div v-if="!balances" style="color:#777">Click “Recalculate” to see who owes whom.</div>

        <template v-else>
          <div style="margin-bottom:8px;font-size:14px">
            Total: ¥{{ yen(balances.summary.total_cents) }} · Members: {{ balances.summary.members }}
          </div>

          <div style="display:grid;gap:6px">
            <div v-for="p in balances.per_member" :key="p.member_id"
                 style="display:flex;justify-content:space-between;border:1px dashed #eee;border-radius:8px;padding:8px">
              <span>{{ p.name }}</span>
              <span :style="{ color: Number(p.balance_cents) >= 0 ? '#0a7' : '#b00020', fontVariantNumeric: 'tabular-nums' }">
                {{ Number(p.balance_cents) >= 0 ? 'gets' : 'owes' }}
                ¥{{ yen(Math.abs(Number(p.balance_cents))) }}
              </span>
            </div>
          </div>

          <h4 style="margin:12px 0 6px;font-size:14px">Suggested settlements</h4>
          <div v-if="!balances.settlements.length" style="color:#777">All even.</div>
          <ul v-else style="margin:0;padding-left:16px">
            <li v-for="s in balances.settlements" :key="s.from + s.to + s.amount_cents">
              {{ s.from }} → {{ s.to }}: ¥{{ yen(s.amount_cents) }}
            </li>

          </ul>
        </template>
      </div>
    </div>
  </section>
</template>
