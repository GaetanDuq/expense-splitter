<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Groups, Members, Expenses } from '../api'

const route = useRoute()
const router = useRouter()
const groupId = ref(Number(route.params.id))

// data for the page
const group = ref(null)
const expenses = ref([])
const balances = ref(null)

// simple flags and messages
const loading = ref(false)
const savingMember = ref(false)
const savingExpense = ref(false)
const loadingBalances = ref(false)
const msg = ref('')

// form models
const newMemberName = ref('')
const expense = ref({ payer_id: null, description: '', amount: '', spent_at: '' })

// format cents as Japanese Yen (no decimals)
const jpy = new Intl.NumberFormat('ja-JP')
const yen = (c) => jpy.format(Math.round(Number(c) / 100))

// today string for date input
const today = () => new Date().toISOString().slice(0,10)

// load group + expenses
async function load() {
  loading.value = true
  try {
    group.value = await Groups.show(groupId.value)
    expenses.value = await Expenses.list(groupId.value)
    expense.value.payer_id = group.value?.members?.[0]?.id ?? null
    expense.value.spent_at = today()
  } finally {
    loading.value = false
  }
}

// add a member with just a name
async function addMember() {
  const n = newMemberName.value.trim()
  if (!n) return
  savingMember.value = true
  try {
    const m = await Members.create(groupId.value, { name: n })
    group.value.members.unshift(m)
    if (!expense.value.payer_id) expense.value.payer_id = m.id
    newMemberName.value = ''
    msg.value = 'Member added'
  } finally {
    savingMember.value = false
  }
}

// remove a member (this also removes their expenses by cascade)
async function removeMember(id) {
  if (!confirm('Delete this member?')) return
  await Members.remove(id)
  await load()
  balances.value = null
  msg.value = 'Member deleted'
}

// add an expense in yen (backend stores cents)
async function addExpense() {
  msg.value = ''
  const amt = Number(expense.value.amount)
  if (!expense.value.payer_id || !expense.value.description.trim() || !amt) {
    msg.value = 'Please fill payer, description and amount'
    return
  }
  savingExpense.value = true
  try {
    const saved = await Expenses.create(groupId.value, {
      payer_id: expense.value.payer_id,
      description: expense.value.description.trim(),
      amount: amt,                // I type yen, backend converts to cents
      spent_at: expense.value.spent_at || null,
    })
    expenses.value.unshift(saved)
    // keep payer/date for next one
    expense.value.description = ''
    expense.value.amount = ''
    msg.value = 'Expense added'
  } finally {
    savingExpense.value = false
  }
}

// delete one expense
async function removeExpense(id) {
  if (!confirm('Delete this expense?')) return
  await Expenses.remove(id)
  expenses.value = expenses.value.filter(e => e.id !== id)
  balances.value = null
  msg.value = 'Expense deleted'
}

// ask server to calculate balances
async function calcBalances() {
  loadingBalances.value = true
  try { balances.value = await Groups.balances(groupId.value) }
  finally { loadingBalances.value = false }
}

// export helper (small but looks nice in demos)
function downloadJson(filename, obj) {
  const data = JSON.stringify(obj, null, 2)
  const blob = new Blob([data], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url; a.download = filename; a.click()
  URL.revokeObjectURL(url)
}

function exportBalances() {
  if (!balances.value) return
  downloadJson(`${group.value.name}-balances.json`, balances.value)
}

function exportExpenses() {
  const data = expenses.value.map(e => ({
    id: e.id,
    description: e.description,
    payer: e.payer?.name ?? null,
    amount_yen: Math.round(e.amount_cents / 100),
    spent_at: e.spent_at,
  }))
  downloadJson(`${group.value.name}-expenses.json`, data)
}

watch(() => route.params.id, async (v) => { groupId.value = Number(v); await load() })
onMounted(load)
</script>

<template>
  <section v-if="!group">Loading…</section>

  <section v-else>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <h2 style="margin:0">{{ group.name }}</h2>
      <button @click="router.push('/')" style="border:1px solid #ddd;background:#fff;border-radius:8px;padding:6px 10px">← All groups</button>
    </div>

    <p v-if="msg" style="color:#0a7">{{ msg }}</p>

    <!-- Members -->
    <div style="border:1px solid #eee;border-radius:12px;padding:12px;margin-bottom:12px">
      <h3 style="margin:0 0 8px">Members</h3>
      <ul style="margin:0;padding-left:16px">
        <li v-for="m in group.members" :key="m.id" style="display:flex;gap:8px;align-items:center">
          <span>{{ m.name }}</span>
          <button @click="removeMember(m.id)" title="Delete member"
                  style="border:none;background:transparent;color:#b00020;cursor:pointer">✕</button>
        </li>
      </ul>
      <form @submit.prevent="addMember" style="display:flex;gap:8px;margin-top:10px">
        <input v-model="newMemberName" placeholder="Add member name"
               style="flex:1;padding:8px;border:1px solid #ddd;border-radius:8px" />
        <button :disabled="savingMember" style="padding:8px 12px;border:1px solid #333;border-radius:8px;background:#111;color:#fff">
          {{ savingMember ? 'Adding…' : 'Add' }}
        </button>
      </form>
    </div>

    <!-- New expense -->
    <div style="border:1px solid #eee;border-radius:12px;padding:12px;margin-bottom:12px">
      <h3 style="margin:0 0 8px">Add expense</h3>
      <form @submit.prevent="addExpense"
            style="display:grid;grid-template-columns:1fr 2fr 1fr 1fr;gap:8px;align-items:center">
        <select v-model.number="expense.payer_id" style="padding:8px;border:1px solid #ddd;border-radius:8px">
          <option :value="null" disabled>Payer</option>
          <option v-for="m in group.members" :key="m.id" :value="m.id">{{ m.name }}</option>
        </select>
        <input v-model="expense.description" placeholder="What was it?"
               style="padding:8px;border:1px solid #ddd;border-radius:8px" />
        <input v-model="expense.amount" type="number" step="1" min="0" placeholder="Amount (yen)"
               style="padding:8px;border:1px solid #ddd;border-radius:8px" />
        <input v-model="expense.spent_at" type="date"
               style="padding:8px;border:1px solid #ddd;border-radius:8px" />
        <div style="grid-column:1 / -1">
          <button :disabled="savingExpense" style="padding:8px 12px;border:1px solid #333;border-radius:8px;background:#111;color:#fff">
            {{ savingExpense ? 'Saving…' : 'Save expense' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Expenses list -->
    <div style="border:1px solid #eee;border-radius:12px;padding:12px;margin-bottom:12px">
      <h3 style="margin:0 0 8px">Expenses</h3>
      <div v-if="!expenses.length" style="color:#777">No expenses yet.</div>
      <ul v-else style="list-style:none;padding:0;display:grid;gap:8px">
        <li v-for="e in expenses" :key="e.id"
            style="border:1px solid #f0f0f0;border-radius:10px;padding:10px;display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:600">{{ e.description }}</div>
            <div style="font-size:12px;color:#777">
              Paid by {{ e.payer?.name ?? '—' }} <span v-if="e.spent_at">· {{ e.spent_at }}</span>
            </div>
          </div>
          <div style="display:flex;gap:10px;align-items:center">
            <div style="font-variant-numeric:tabular-nums">¥{{ yen(e.amount_cents) }}</div>
            <button @click="removeExpense(e.id)" title="Delete expense"
                    style="border:none;background:transparent;color:#b00020;cursor:pointer">✕</button>
          </div>
        </li>
      </ul>
    </div>

    <!-- Balances -->
    <div style="border:1px solid #eee;border-radius:12px;padding:12px">
      <div style="display:flex;gap:8px;justify-content:space-between;align-items:center;margin-bottom:8px">
        <h3 style="margin:0">Balances</h3>
        <div style="display:flex;gap:8px">
          <button @click="calcBalances" :disabled="loadingBalances"
                  style="padding:6px 10px;border:1px solid #333;border-radius:8px;background:#111;color:#fff">
            {{ loadingBalances ? 'Calculating…' : 'Recalculate' }}
          </button>
          <button v-if="balances" @click="exportBalances"
                  style="padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#fff">
            Export balances
          </button>
          <button :disabled="!expenses.length" @click="exportExpenses"
                  style="padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#fff">
            Export expenses
          </button>
        </div>
      </div>

      <div v-if="!balances" style="color:#777">Click “Recalculate”.</div>

      <template v-else>
        <div style="margin-bottom:8px">
          Total: ¥{{ yen(balances.summary.total_cents) }} · Members: {{ balances.summary.members }}
        </div>

        <div style="display:grid;gap:6px">
          <div v-for="p in balances.per_member" :key="p.member_id"
               style="display:flex;justify-content:space-between;border:1px dashed #eee;border-radius:8px;padding:8px">
            <span>{{ p.name }}</span>
            <span :style="{ color: Number(p.balance_cents) >= 0 ? '#0a7' : '#b00020', fontVariantNumeric: 'tabular-nums' }">
              {{ Number(p.balance_cents) >= 0 ? 'gets' : 'owes' }} ¥{{ yen(Math.abs(Number(p.balance_cents))) }}
            </span>
          </div>
        </div>

        <h4 style="margin:12px 0 6px">Suggested settlements</h4>
        <div v-if="!balances.settlements.length" style="color:#777">All even.</div>
        <ul v-else style="margin:0;padding-left:16px">
          <li v-for="s in balances.settlements" :key="s.from + s.to + s.amount_cents">
            {{ s.from }} → {{ s.to }}: ¥{{ yen(s.amount_cents) }}
          </li>
        </ul>
      </template>
    </div>
  </section>
</template>

