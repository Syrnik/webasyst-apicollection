<template>
  <Teleport to="body">
    <div class="dialog dialog-opened">
      <div class="dialog-background" @click="emit('close')"></div>
      <div 
        class="dialog-body" 
        style="top:50%;left:50%;transform:translate(-50%,-50%);max-height:90vh;overflow-y:auto;width:700px"
      >
        <a 
          href="#" 
          class="dialog-close" 
          @click.prevent="emit('close')" 
          aria-label="Close"
        >
          &times;
        </a>
        
        <header class="dialog-header">
          <h1>Управление окружениями</h1>
        </header>
        
        <div class="dialog-content">
          <div style="margin-bottom:16px">
            <button class="button blue" @click="emit('add')">
              + Добавить окружение
            </button>
          </div>
          
          <div v-if="loading" style="padding:20px;text-align:center">
            <span class="spinner wa-animation-spin"></span>
          </div>
          
          <div v-else-if="error" class="alert danger">
            {{ error }}
          </div>
          
          <div v-else-if="environments.length === 0" style="padding:40px;text-align:center">
            <p class="hint">Окружений нет</p>
            <p class="hint" style="font-size:12px;margin-top:8px">
              Создайте окружения для быстрого переключения между Production, Staging и другими стендами
            </p>
          </div>
          
          <table v-else class="zebra" style="width:100%">
            <thead>
              <tr>
                <th style="width:200px">Название</th>
                <th>Base URL</th>
                <th style="width:100px">Доступ</th>
                <th style="width:120px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="env in sortedEnvironments" :key="env.id">
                <td>
                  <strong>{{ env.name }}</strong>
                </td>
                <td style="font-family:monospace;font-size:11px">
                  {{ truncate(env.base_url, 50) }}
                </td>
                <td>
                  <span class="hint" style="font-size:11px">
                    {{ env.is_shared ? '🌐 Общее' : '🔒 Личное' }}
                  </span>
                </td>
                <td style="text-align:right">
                  <button 
                    type="button" 
                    class="button light-gray small" 
                    @click="handleEdit(env)"
                    style="margin-right:4px"
                  >
                    ✏️
                  </button>
                  <button 
                    type="button" 
                    class="button light-gray small" 
                    @click="handleDelete(env)"
                  >
                    🗑️
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <footer class="dialog-footer">
          <button class="button light-gray" @click="emit('close')">
            Закрыть
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useEnvironments } from '@/composables/useEnvironments';
import { truncate } from '@/utils/formatters';
import type { Environment } from '@/types';

interface Emits {
  (e: 'close'): void;
  (e: 'add'): void;
  (e: 'edit', env: Environment): void;
  (e: 'changed'): void;
}

const emit = defineEmits<Emits>();

const { environments, loading, error, load, remove } = useEnvironments();

onMounted(() => {
  load();
});

const sortedEnvironments = computed(() => {
  return [...environments.value].sort((a, b) => {
    // Сначала общие, потом личные
    if (a.is_shared !== b.is_shared) {
      return b.is_shared - a.is_shared;
    }
    // Затем по sort
    if (a.sort !== b.sort) {
      return a.sort - b.sort;
    }
    // Затем по имени
    return a.name.localeCompare(b.name);
  });
});

function handleEdit(env: Environment): void {
  emit('edit', env);
}

async function handleDelete(env: Environment): Promise<void> {
  if (!confirm(`Удалить окружение «${env.name}»?`)) {
    return;
  }
  
  try {
    await remove(env.id);
    await load();
    emit('changed');
  } catch (e) {
    alert('Ошибка удаления: ' + (e instanceof Error ? e.message : 'Неизвестная ошибка'));
  }
}
</script>
