import { createRouter, createWebHistory } from 'vue-router'

const GroupsIndex = () => import('./views/GroupsIndex.vue')
const GroupShow   = () => import('./views/GroupShow.vue')

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'groups.index', component: GroupsIndex },
    { path: '/groups/:id', name: 'groups.show', component: GroupShow, props: true },
  ],
})
