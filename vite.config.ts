import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig(({ mode }) => ({
  plugins: [vue()],
  
  // Разрешение путей
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
      // Используем полную версию Vue с компилятором шаблонов
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  },
  
  // Настройки сборки
  build: {
    // Собираем в корень приложения (не в dist/)
    outDir: '.',
    
    // НЕ очищаем директорию (там PHP-код!)
    emptyOutDir: false,
    
    // Rollup опции
    rollupOptions: {
      // Точка входа
      input: {
        main: path.resolve(__dirname, 'src/main.ts')
      },
      
      // Настройки output
      output: {
        // Имя файла JS
        entryFileNames: 'js/apicollection.js',
        
        // Имена asset-файлов (CSS, изображения)
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'css/apicollection.css';
          }
          return 'assets/[name].[ext]';
        },
        
        // IIFE формат для совместимости с Webasyst
        // (не ES module, т.к. нужна интеграция с jQuery)
        format: 'iife',
        
        // Имя глобальной переменной (опционально)
        name: 'ApiCollection',
        
        // Не разбивать на чанки
        manualChunks: undefined
      },
      
      // Внешние зависимости (не включать в бандл)
      external: [
        // jQuery загружается из Webasyst
        'jquery'
      ]
    },
    
    // Минификация: только в production
    minify: mode === 'production' ? 'terser' : false,
    terserOptions: mode === 'production' ? {
      compress: {
        // Удаляем console.log в production
        drop_console: true,
        // Удаляем debugger
        drop_debugger: true,
        // Удаляем неиспользуемый код
        dead_code: true
      },
      format: {
        // Удаляе�� комментарии
        comments: false
      }
    } : undefined,
    
    // Source maps: включены в development
    sourcemap: mode === 'development',
    
    // Целевые браузеры
    target: 'es2024',
    
    // Размер чанка для предупреждений
    chunkSizeWarningLimit: 500
  },
  
  // Определения для замены в коде
  define: {
    // Vue feature flags
    __VUE_OPTIONS_API__: false,  // Отключаем Options API (используем только Composition API)
    // DevTools только в development, запрещены в production
    __VUE_PROD_DEVTOOLS__: mode === 'development',
    __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    // Режим разработки (для условной logики в коде)
    __DEV__: mode === 'development'
  },
  
  // Dev-сервер (для разработки)
  server: {
    port: 3000,
    open: false,
    cors: true,
    
    // Прокси для API-запросов к Webasyst
    proxy: {
      '/wa-apps': {
        target: 'http://wa26.local',
        changeOrigin: true
      },
      '/wa-content': {
        target: 'http://wa26.local',
        changeOrigin: true
      }
    }
  },
  
  // Оптимизация зависимостей
  optimizeDeps: {
    include: ['vue']
  }
}));
