import { ref, watch, type Ref } from 'vue';
import { apiFetch } from '@/api';
import type { RequestHistoryItem } from '@/types';

/**
 * Composable для работы с историей запросов
 */
export function useRequestHistory(collectionId: Ref<number | null>) {
  const items = ref<RequestHistoryItem[]>([]);
  const loading = ref<boolean>(false);
  const error = ref<string>('');
  
  /**
   * Загружает историю запросов
   */
  async function load(limit: number = 50): Promise<void> {
    if (!collectionId.value) {
      items.value = [];
      return;
    }
    
    loading.value = true;
    error.value = '';
    
    try {
      items.value = await apiFetch<RequestHistoryItem[]>('history', {
        data: {
          collection_id: collectionId.value,
          limit
        }
      });
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Ошибка загрузки';
    } finally {
      loading.value = false;
    }
  }
  
  // Автоматическая загрузка при смене коллекции
  watch(collectionId, () => load(), { immediate: true });
  
  /**
   * Добавляет новый элемент в начало истории (без перезагрузки с сервера)
   */
  function addItem(item: RequestHistoryItem): void {
    items.value.unshift(item);
    // Удаляем последний элемент если превышен лимит
    if (items.value.length > 50) {
      items.value.pop();
    }
  }

  return {
    items,
    loading,
    error,
    load,
    addItem
  };
}
