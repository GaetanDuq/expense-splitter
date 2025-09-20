<template>
  <div class="min-h-screen">
    <header style="padding:16px;border-bottom:1px solid #eee;display:flex;gap:12px;align-items:center">
      <router-link to="/" style="text-decoration:none;color:inherit">
        <h1 style="font-size:18px;margin:0">Expense Splitter</h1>
      </router-link>
      <span style="font-size:12px;color:#777">Laravel API + Vue SPA</span>
    </header>

    <main style="padding:16px;max-width:900px;margin:0 auto">
      <router-view />
    </main>
  </div>
</template>

<script setup>
// purely presentational shell
</script>
