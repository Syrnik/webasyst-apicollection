import { ref, reactive } from 'vue';
import { apiFetch } from '@/api';
import type { Collection, Environment } from '@/types';

/**
 * Результат выполнения запроса
 */
interface ApiRequestResponse {
  response_status: number;
  response_body: string;
  response_headers: Record<string, string>;
}

/**
 * Composable для выполнения API-запросов через прокси
 */
export function useApiRequest() {
  const executing = ref<boolean>(false);
  const response = ref<ApiRequestResponse | null>(null);
  const error = ref<string>('');
  
  // Параметры запроса
  const pathParams = reactive<Record<string, string>>({});
  const queryParams = reactive<Record<string, string>>({});
  const headerParams = reactive<Record<string, string>>({});
  const bodyContent = ref<string>('');
  
  /**
   * Строит финальный путь с подстановкой path-параметров
   */
  function buildFinalPath(path: string): string {
    let finalPath = path;
    
    for (const [key, value] of Object.entries(pathParams)) {
      finalPath = finalPath.replace(
        `{${key}}`, 
        encodeURIComponent(value)
      );
    }
    
    return finalPath;
  }
  
  /**
   * Выполняет HTTP-запрос через серверный прокси
   */
  async function execute(
    collection: Collection,
    method: string,
    path: string,
    baseUrl?: string,
    environment?: Environment | null
  ): Promise<void> {
    executing.value = true;
    error.value = '';
    response.value = null;

    try {
      const payload: Record<string, any> = {
        collection_id: collection.id,
        method: method.toUpperCase(),
        path: buildFinalPath(path),
        query_params: { ...queryParams },
        headers: { ...headerParams },
        body: bodyContent.value || undefined,
        base_url: baseUrl || undefined
      };

      console.log('=== API Request Debug ===');
      console.log('queryParams object:', queryParams);
      console.log('queryParams snapshot:', { ...queryParams });
      console.log('payload:', payload);

      // Если выбрано окружение, передаём его ID
      if (environment?.id) {
        payload['environment_id'] = environment.id;
      }

      const res = await apiFetch<ApiRequestResponse>('proxyFetch', {
        method: 'POST',
        data: payload
      });

      response.value = res;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Ошибка выполнения';
    } finally {
      executing.value = false;
    }
  }
  
  /**
   * Очищает параметры запроса
   */
  function clearParams(): void {
    Object.keys(pathParams).forEach(k => delete pathParams[k]);
    Object.keys(queryParams).forEach(k => delete queryParams[k]);
    Object.keys(headerParams).forEach(k => delete headerParams[k]);
    bodyContent.value = '';
  }
  
  /**
   * Устанавливает параметры из спецификации
   */
  function setParamsFromSpec(parameters: any[]): void {
    clearParams();

    const pathEntries: Record<string, string> = {};
    const queryEntries: Record<string, string> = {};
    const headerEntries: Record<string, string> = {};

    for (const param of parameters) {
      const defaultValue = param.example || param.default || '';

      if (param.in === 'path') {
        pathEntries[param.name] = defaultValue;
      } else if (param.in === 'query') {
        queryEntries[param.name] = defaultValue;
      } else if (param.in === 'header') {
        headerEntries[param.name] = defaultValue;
      }
    }

    // Используем Object.assign для корректного обновления reactive объектов
    if (Object.keys(pathEntries).length > 0) {
      Object.assign(pathParams, pathEntries);
    }
    if (Object.keys(queryEntries).length > 0) {
      Object.assign(queryParams, queryEntries);
    }
    if (Object.keys(headerEntries).length > 0) {
      Object.assign(headerParams, headerEntries);
    }
  }
  
  return {
    executing,
    response,
    error,
    pathParams,
    queryParams,
    headerParams,
    bodyContent,
    buildFinalPath,
    execute,
    clearParams,
    setParamsFromSpec
  };
}
