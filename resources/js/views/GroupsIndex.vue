<script setup>
import { ref, onMounted } from 'vue'
import { Groups } from '../api'

const groups = ref([])
const loading = ref(false)
const name = ref('')
const creating = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  try {
    groups.value = await Groups.list()
  } finally {
    loading.value = false
  }
}

async function createGroup() {
  error.value = ''
  if (!name.value.trim()) { error.value = 'Group name is required'; return }
  creating.value = true
  try {
    const g = await Groups.create({ name: name.value.trim() })
    name.value = ''
    groups.value.unshift({ ...g, members_count: 0, expenses_count: 0 })
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to create group'
  } finally {
    creating.value = false
  }
}

onMounted(load)
</script>

<template>
  <section>
    <h2 style="margin:0 0 12px">Groups</h2>

    <form @submit.prevent="createGroup" style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
      <input v-model="name" placeholder="New group name (e.g., Kyoto Trip)" style="padding:8px;border:1px solid #ddd;border-radius:8px;flex:1" />
      <button :disabled="creating" style="padding:8px 12px;border:1px solid #333;border-radius:8px;background:#111;color:#fff;cursor:pointer">
        {{ creating ? 'Creating…' : 'Create' }}
      </button>
    </form>
    <p v-if="error" style="color:#b00020">{{ error }}</p>

    <div v-if="loading">Loading…</div>
    <div v-else-if="!groups.length" style="color:#777">No groups yet. Create one above.</div>

    <ul v-else style="list-style:none;padding:0;margin:0;display:grid;gap:10px">
      <li v-for="g in groups" :key="g.id" style="border:1px solid #eee;border-radius:12px;padding:12px;display:flex;justify-content:space-between;align-items:center">
        <div>
          <router-link :to="`/groups/${g.id}`" style="font-weight:600;text-decoration:none;color:#111">{{ g.name }}</router-link>
          <div style="font-size:12px;color:#777">Members: {{ g.members_count ?? '—' }} · Expenses: {{ g.expenses_count ?? '—' }}</div>
        </div>
        <router-link :to="`/groups/${g.id}`" style="font-size:13px">Open →</router-link>
      </li>
    </ul>
  </section>
</template>
