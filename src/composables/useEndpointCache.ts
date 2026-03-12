import { reactive } from 'vue';

/**
 * Сохраняет параметры для каждого endpoint
 */
interface EndpointState {
  pathParams: Record<string, string>;
  queryParams: Record<string, string>;
  headerParams: Record<string, string>;
  bodyContent: string;
}

/**
 * Composable для кеширования параметров endpoint'ов
 * Позволяет переключаться между endpoint'ами и сохранять их состояние
 */
export function useEndpointCache() {
  // Кеш: ключ = "METHOD:PATH", значение = состояние параметров
  const cache = reactive<Record<string, EndpointState>>({});

  /**
   * Генерирует ключ кеша для endpoint'а
   */
  function getCacheKey(method: string, path: string): string {
    return `${method.toUpperCase()}:${path}`;
  }

  /**
   * Сохраняет текущее состояние параметров
   */
  function saveState(
    method: string,
    path: string,
    pathParams: Record<string, string>,
    queryParams: Record<string, string>,
    headerParams: Record<string, string>,
    bodyContent: string
  ): void {
    const key = getCacheKey(method, path);
    cache[key] = {
      pathParams: { ...pathParams },
      queryParams: { ...queryParams },
      headerParams: { ...headerParams },
      bodyContent
    };
  }

  /**
   * Получает сохранённое состояние или null если его нет
   */
  function getState(method: string, path: string): EndpointState | null {
    const key = getCacheKey(method, path);
    return cache[key] || null;
  }

  /**
   * Проверяет, есть ли сохранённое состояние
   */
  function hasState(method: string, path: string): boolean {
    const key = getCacheKey(method, path);
    return !!cache[key];
  }

  /**
   * Очищает кеш полностью
   */
  function clearCache(): void {
    Object.keys(cache).forEach(key => {
      delete cache[key];
    });
  }

  return {
    cache,
    getCacheKey,
    saveState,
    getState,
    hasState,
    clearCache
  };
}
