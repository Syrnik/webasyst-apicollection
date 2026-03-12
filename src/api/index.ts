import type { ApiResponse } from '@/types';

declare const $: any; // jQuery из Webasyst

export function apiUrl(action: string): string {
  return `?module=backend&action=${action}`;
}

export function apiFetch<T = any>(
  action: string,
  opts: {
    method?: 'GET' | 'POST';
    data?: Record<string, any>;
  } = {}
): Promise<T> {
  const method = opts.method || 'GET';
  
  return new Promise((resolve, reject) => {
    $.ajax({
      url: apiUrl(action),
      type: method,
      data: opts.data || undefined,
      cache: true, // Важно для Webasyst!
      dataType: 'json',
      success(json: ApiResponse<T>) {
        if (json.status !== 'ok') {
          reject(new Error(json.errors?.message || 'Ошибка запроса'));
        } else {
          resolve(json.data as T);
        }
      },
      error(xhr: any) {
        let msg = `Ошибка сервера: ${xhr.status}`;
        try {
          const response = JSON.parse(xhr.responseText) as ApiResponse;
          msg = response.errors?.message || msg;
        } catch (e) {
          // ignore
        }
        reject(new Error(msg));
      }
    });
  });
}
