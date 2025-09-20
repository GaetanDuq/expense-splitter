import { createRouter, createWebHistory } from 'vue-router'
import GroupsIndex from './views/GroupsIndex.vue'
import GroupShow from './views/GroupShow.vue'

const routes = [
  { path: '/', name: 'groups.index', component: GroupsIndex },
  { path: '/groups/:id', name: 'groups.show', component: GroupShow, props: true },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
