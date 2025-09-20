<script setup>
import { ref, onMounted } from 'vue'
import { Groups } from '../api'

const groups = ref([])
const name = ref('')
const loading = ref(false)
const making = ref(false)
const error = ref('')

// get all groups when page opens
async function load() {
  loading.value = true
  try { groups.value = await Groups.list() }
  finally { loading.value = false }
}

// create a group with just a name
async function createGroup() {
  error.value = ''
  const n = name.value.trim()
  if (!n) { error.value = 'Please type a group name'; return }
  making.value = true
  try {
    const g = await Groups.create({ name: n })
    // add at top so I see it right away
    groups.value.unshift({ ...g, members_count: 0, expenses_count: 0 })
    name.value = ''
  } catch (e) {
    error.value = 'Could not create the group'
  } finally {
    making.value = false
  }
}

onMounted(load)
</script>

<template>
  <section>
    <h2>Groups</h2>

    <form @submit.prevent="createGroup" style="display:flex;gap:8px;margin-bottom:12px">
      <input v-model="name" placeholder="New group name (e.g. Kyoto Trip)"
             style="flex:1;padding:8px;border:1px solid #ddd;border-radius:8px" />
      <button :disabled="making" style="padding:8px 12px;border:1px solid #333;border-radius:8px;background:#111;color:#fff">
        {{ making ? 'Saving…' : 'Create' }}
      </button>
    </form>

    <p v-if="error" style="color:#b00020">{{ error }}</p>
    <p v-if="loading">Loading…</p>
    <p v-else-if="!groups.length" style="color:#777">No groups yet.</p>

    <ul v-else style="list-style:none;padding:0;display:grid;gap:10px">
      <li v-for="g in groups" :key="g.id"
          style="border:1px solid #eee;border-radius:12px;padding:12px;display:flex;justify-content:space-between;align-items:center">
        <div>
          <router-link :to="`/groups/${g.id}`" style="text-decoration:none;color:#111;font-weight:600">{{ g.name }}</router-link>
          <div style="color:#777;font-size:12px">
            Members: {{ g.members_count ?? '—' }} · Expenses: {{ g.expenses_count ?? '—' }}
          </div>
        </div>
        <router-link :to="`/groups/${g.id}`">Open →</router-link>
      </li>
    </ul>
  </section>
</template>
