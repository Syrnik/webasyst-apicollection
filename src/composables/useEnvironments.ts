import { ref } from 'vue';
import { apiFetch } from '@/api';
import type { Environment } from '@/types';

/**
 * Composable для работы с окружениями
 */
export function useEnvironments() {
  const environments = ref<Environment[]>([]);
  const loading = ref<boolean>(false);
  const error = ref<string>('');
  
  /**
   * Загружает список окружений
   */
  async function load(): Promise<void> {
    loading.value = true;
    error.value = '';
    
    try {
      environments.value = await apiFetch<Environment[]>('environments');
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Ошибка загрузки';
    } finally {
      loading.value = false;
    }
  }
  
  /**
   * Сохраняет окружение (создание или обновление)
   */
  async function save(data: Partial<Environment>): Promise<{ id: number }> {
    return await apiFetch<{ id: number }>('environmentSave', {
      method: 'POST',
      data
    });
  }
  
  /**
   * Удаляет окружение
   */
  async function remove(id: number): Promise<void> {
    await apiFetch('environmentDelete', {
      method: 'POST',
      data: { id }
    });
  }
  
  /**
   * Получает одно окружение по ID (с auth_data)
   */
  async function getById(id: number): Promise<Environment> {
    return await apiFetch<Environment>('environmentGet', {
      data: { id }
    });
  }
  
  /**
   * Выбирает окружение для коллекции
   */
  async function select(
    collectionId: number, 
    environmentId: number | null
  ): Promise<void> {
    await apiFetch('environmentSelect', {
      method: 'POST',
      data: {
        collection_id: collectionId,
        environment_id: environmentId || ''
      }
    });
  }
  
  /**
   * Получает текущее выбранное окружение для коллекции
   */
  async function getSelected(
    collectionId: number
  ): Promise<{ environment: Environment | null }> {
    return await apiFetch<{ environment: Environment | null }>(
      'environmentSelected',
      { data: { collection_id: collectionId } }
    );
  }
  
  return {
    environments,
    loading,
    error,
    load,
    save,
    remove,
    getById,
    select,
    getSelected
  };
}
