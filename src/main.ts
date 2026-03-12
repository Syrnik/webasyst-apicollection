import { createApp } from 'vue';
import App from './App.vue';
import './styles/main.css';

/**
 * Ждём загрузки jQuery из Webasyst
 */
function waitForJQuery(): Promise<void> {
  return new Promise((resolve) => {
    if (typeof (window as any).$ !== 'undefined') {
      resolve();
    } else {
      const interval = setInterval(() => {
        if (typeof (window as any).$ !== 'undefined') {
          clearInterval(interval);
          resolve();
        }
      }, 100);
    }
  });
}

/**
 * Инициализация приложения
 */
async function init(): Promise<void> {
  // Ждём jQuery (нужен для CSRF и интеграции с Webasyst)
  await waitForJQuery();
  
  // Создаём и монтируем приложение
  const app = createApp(App);
  app.mount('#apicollection-app');
}

// Запускаем
init().catch((error) => {
  console.error('Failed to initialize app:', error);
  
  const el = document.getElementById('apicollection-app');
  if (el) {
    el.innerHTML = `
      <div class="alert danger" style="margin:20px">
        <strong>Ошибка инициализации приложения</strong>
        <p>${error.message || 'Неизвестная ошибка'}</p>
      </div>
    `;
  }
});
