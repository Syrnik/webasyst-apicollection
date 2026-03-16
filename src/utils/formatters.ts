/**
 * Форматирование и отображение данных
 */

/**
 * Форматирует JSON-строку с отступами
 */
export function formatJson(str: string): string {
  try {
    const parsed = JSON.parse(str);
    return JSON.stringify(parsed, null, 2);
  } catch {
    return str;
  }
}

/**
 * Возвращает CSS-класс для HTTP-статуса
 */
export function statusClass(code: number | string): string {
  const numCode = parseInt(String(code), 10);
  
  if (!numCode || isNaN(numCode)) return 'apic-status-0';
  if (numCode < 300) return 'apic-status-2xx';
  if (numCode < 400) return 'apic-status-3xx';
  if (numCode < 500) return 'apic-status-4xx';
  return 'apic-status-5xx';
}

/**
 * Возвращает CSS-классы для бейджа HTTP-метода
 */
export function methodBadgeClass(method: string): string {
  const colors: Record<string, string> = {
    GET: 'green',
    POST: 'blue',
    PUT: 'orange',
    DELETE: '',
    PATCH: 'yellow',
    HEAD: 'purple',
    OPTIONS: 'gray'
  };
  
  const upperMethod = (method || 'GET').toUpperCase();
  const color = colors[upperMethod] ?? 'gray';
  
  return 'badge squared' + (color ? ' ' + color : '');
}

/**
 * Извлекает base URL из полного URL спецификации
 */
export function extractBaseUrl(specUrl: string): string {
  try {
    const url = new URL(specUrl);
    return url.origin;
  } catch {
    return specUrl;
  }
}

/**
 * Извлекает base URL из спецификации (из поля servers)
 */
export function extractBaseUrlFromSpec(spec: any): string {
  if (!spec) return '';
  
  // OpenAPI 3.x: используем первый сервер из массива servers
  if (spec.servers && Array.isArray(spec.servers) && spec.servers.length > 0) {
    const firstServer = spec.servers[0];
    if (typeof firstServer === 'object' && firstServer.url) {
      return firstServer.url;
    }
  }
  
  // Swagger 2.0: используем host + basePath
  if (spec.host) {
    const scheme = spec.schemes?.[0] || 'https';
    const basePath = spec.basePath || '';
    return `${scheme}://${spec.host}${basePath}`;
  }
  
  return '';
}

/**
 * Форматирует дату/время для отображения (относительное время)
 */
export function timeAgo(dateString: string): string {
  if (!dateString) return '';
  
  const date = new Date(dateString.replace(' ', 'T'));
  const diffSeconds = Math.floor((Date.now() - date.getTime()) / 1000);
  
  if (diffSeconds < 60) return `${diffSeconds}с назад`;
  if (diffSeconds < 3600) return `${Math.floor(diffSeconds / 60)}мин назад`;
  if (diffSeconds < 86400) return `${Math.floor(diffSeconds / 3600)}ч назад`;
  
  return date.toLocaleString('ru-RU');
}

/**
 * Обрезает строку до указанной длины с добавлением "..."
 */
export function truncate(str: string, maxLength: number): string {
  if (str.length <= maxLength) return str;
  return str.substring(0, maxLength - 3) + '...';
}

/**
 * Копирует текст в буфер обмена
 */
export async function copyToClipboard(text: string): Promise<boolean> {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch {
    return false;
  }
}
