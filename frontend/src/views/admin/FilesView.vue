 <template>
  <div class="pages-view">
    <header class="page-header">
      <h1>Page Management</h1>
      <p class="page-description">
        Manage pages across all conferences in the system
      </p>
    </header>

    <div class="page-content">
      <ConferencePageManager title="Conference Pages" @refresh="fetchStats" />
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.pages-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  margin-bottom: 40px;
}

.page-header h1 {
  margin-bottom: 10px;
  color: #2c3e50;
}

.page-description {
  color: #6c757d;
  font-size: 1.1rem;
}

.page-content {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
