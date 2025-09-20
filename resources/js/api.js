import axios from 'axios'

const api = axios.create({
  baseURL: '/api', // same origin → no CORS
})

export const Groups = {
  list:   () => api.get('/groups').then(r => r.data),
  create: (payload) => api.post('/groups', payload).then(r => r.data),
  show:   (id) => api.get(`/groups/${id}`).then(r => r.data),
  destroy:(id) => api.delete(`/groups/${id}`),

  // balances are computed on the backend service
  balances: (id) => api.get(`/groups/${id}/balances`).then(r => r.data),
}

export const Members = {
  create: (groupId, payload) =>
    api.post(`/groups/${groupId}/members`, payload).then(r => r.data),
  destroy: (memberId) => api.delete(`/members/${memberId}`),
}

export const Expenses = {
  list:   (groupId) => api.get(`/groups/${groupId}/expenses`).then(r => r.data),
  create: (groupId, payload) =>
    api.post(`/groups/${groupId}/expenses`, payload).then(r => r.data),
  destroy:(expenseId) => api.delete(`/expenses/${expenseId}`),
}
