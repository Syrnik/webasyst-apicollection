<template>
  <div class="apic-history">
    <div class="hint small uppercase" style="margin-bottom:12px">История запросов</div>
    
    <div v-if="loading" style="padding:20px;text-align:center">
      <span class="spinner wa-animation-spin"></span>
    </div>
    
    <div v-else-if="error" class="alert danger">
      {{ error }}
    </div>
    
    <div v-else-if="items.length === 0" style="padding:20px;text-align:center">
      <p class="hint">История пуста</p>
    </div>
    
    <div v-else style="max-height:300px;overflow-y:auto">
      <table class="zebra" style="width:100%;font-size:12px">
        <thead>
          <tr>
            <th style="width:80px">Метод</th>
            <th>Путь</th>
            <th style="width:80px">Статус</th>
            <th style="width:150px">Время</th>
            <th style="width:80px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <span :class="methodBadgeClass(item.method)" style="font-size:10px;padding:2px 4px">
                {{ item.method }}
              </span>
            </td>
            <td style="font-family:monospace;font-size:11px">{{ truncate(item.path, 50) }}</td>
            <td>
              <span :class="statusClass(item.response_status)" class="apic-status-badge">
                {{ item.response_status || 'ERR' }}
              </span>
            </td>
            <td class="hint">{{ timeAgo(item.executed_at) }}</td>
            <td>
              <button 
                type="button" 
                class="button light-gray small" 
                @click="emit('replay', item)"
                title="Повторить запрос"
              >
                🔄
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRequestHistory } from '@/composables/useRequestHistory';
import { methodBadgeClass, statusClass, timeAgo, truncate } from '@/utils/formatters';
import type { RequestHistoryItem } from '@/types';

interface Props {
  collectionId: number;
}

interface Emits {
  (e: 'replay', item: RequestHistoryItem): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const collectionIdRef = computed(() => props.collectionId);
const { items, loading, error } = useRequestHistory(collectionIdRef);
</script>
