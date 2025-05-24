import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      'tinymce': path.resolve(__dirname, 'node_modules/tinymce')
    }
  },
  server: {
    host: '0.0.0.0',
    port: 8080,
    watch: {
      usePolling: true,
      interval: 1000,
    },
    hmr: {
      host: 'localhost',
      clientPort: 8080,
    },
    fs: {
      strict: false,
      allow: ['..']
    },
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false
      }
    }
  },
  optimizeDeps: {
    include: ['vue', 'vue-router', 'pinia', 'axios', '@tinymce/tinymce-vue', 'tinymce'],
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    rollupOptions: {
      output: {
        manualChunks: id => {
          if (id.includes('node_modules/tinymce')) {
            return 'tinymce';
          }
        }
      }
    }
  },
  publicDir: 'public'
});
