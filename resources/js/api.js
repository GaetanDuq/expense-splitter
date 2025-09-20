import axios from 'axios'
const api = axios.create({ baseURL: '/api' })

export const Groups = {
  list:   () => api.get('/groups').then(r => r.data),
  create: (data) => api.post('/groups', data).then(r => r.data),
  show:   (id) => api.get(`/groups/${id}`).then(r => r.data),
  remove: (id) => api.delete(`/groups/${id}`),
  balances: (id) => api.get(`/groups/${id}/balances`).then(r => r.data),
}

export const Members = {
  create: (groupId, data) => api.post(`/groups/${groupId}/members`, data).then(r => r.data),
  remove: (memberId) => api.delete(`/members/${memberId}`),
}

export const Expenses = {
  list:   (groupId) => api.get(`/groups/${groupId}/expenses`).then(r => r.data),
  create: (groupId, data) => api.post(`/groups/${groupId}/expenses`, data).then(r => r.data),
  remove: (expenseId) => api.delete(`/expenses/${expenseId}`),
}
