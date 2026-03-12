import { ref } from 'vue';
import { apiFetch } from '@/api';
import type { Collection } from '@/types';

declare const $: any;

/**
 * Composable для работы с коллекциями
 */
export function useCollections() {
  const collections = ref<Collection[]>([]);
  const loading = ref<boolean>(false);
  const error = ref<string>('');
  
  /**
   * Загружает список коллекций
   */
  async function load(): Promise<void> {
    loading.value = true;
    error.value = '';
    
    try {
      collections.value = await apiFetch<Collection[]>('collections');
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Ошибка загрузки';
    } finally {
      loading.value = false;
    }
  }
  
  /**
   * Сохраняет коллекцию (создание или обновление)
   */
  async function save(data: Partial<Collection>): Promise<{ id: number }> {
    return await apiFetch<{ id: number }>('collectionSave', {
      method: 'POST',
      data
    });
  }
  
  /**
   * Удаляет коллекцию
   */
  async function remove(id: number): Promise<void> {
    await apiFetch('collectionDelete', {
      method: 'POST',
      data: { id }
    });
  }
  
  /**
   * Получает одну коллекцию по ID (с auth_data)
   */
  async function getById(id: number): Promise<Collection> {
    return await apiFetch<Collection>('collectionGet', {
      data: { id }
    });
  }
  
  /**
   * Загружает файл спецификации
   */
  async function uploadFile(file: File): Promise<{ file: string; name: string }> {
    const formData = new FormData();
    formData.append('file', file);
    
    return new Promise((resolve, reject) => {
      $.ajax({
        url: '?module=backend&action=collectionUploadFile',
        type: 'POST',
        data: formData,
        cache: true,
        dataType: 'json',
        processData: false,
        contentType: false,
        success(json: any) {
          if (json.status !== 'ok') {
            reject(new Error(json.errors?.message || 'Ошибка загрузки'));
          } else {
            resolve(json.data);
          }
        },
        error(xhr: any) {
          let msg = `Ошибка сервера: ${xhr.status}`;
          try {
            msg = JSON.parse(xhr.responseText).errors?.message || msg;
          } catch (e) {
            // ignore
          }
          reject(new Error(msg));
        }
      });
    });
  }
  
  return {
    collections,
    loading,
    error,
    load,
    save,
    remove,
    getById,
    uploadFile
  };
}
