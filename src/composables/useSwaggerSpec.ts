import { ref, watch, type Ref } from 'vue';
import * as yaml from 'js-yaml';
import { apiFetch } from '@/api';
import { buildTree, setCurrentSpec } from '@/utils/swagger';
import type { SwaggerSpec, EndpointTag, Collection } from '@/types';

/**
 * Парсит спецификацию в зависимости от формата
 */
function parseSpec(content: string, format: 'json' | 'yaml'): SwaggerSpec {
  if (format === 'yaml') {
    return yaml.load(content) as SwaggerSpec;
  }
  return JSON.parse(content) as SwaggerSpec;
}

/**
 * Composable для загрузки и парсинга Swagger/OpenAPI спецификаций
 */
export function useSwaggerSpec(collection: Ref<Collection | null>) {
  const spec = ref<SwaggerSpec | null>(null);
  const tagTree = ref<EndpointTag[]>([]);
  const loading = ref<boolean>(false);
  const error = ref<string>('');
  
  /**
   * Загружает спецификацию
   */
  async function load(): Promise<void> {
    if (!collection.value) {
      spec.value = null;
      tagTree.value = [];
      return;
    }
    
    loading.value = true;
    error.value = '';
    spec.value = null;
    tagTree.value = [];
    
    try {
      let loadedSpec: SwaggerSpec;
      
      // Загрузка из файла
      if (collection.value.spec_source === 'file' && collection.value.spec_file) {
        const result = await apiFetch<{ content: string; format: 'json' | 'yaml' }>('collectionGetSpec', {
          data: { file: collection.value.spec_file }
        });
        loadedSpec = parseSpec(result.content, result.format);
      }
      // Загрузка из URL (напрямую через fetch)
      else if (collection.value.spec_url) {
        const response = await fetch(collection.value.spec_url);
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type') ?? '';
        const text = await response.text();
        const isYaml = /yaml|yml/i.test(contentType)
          || /\.(yaml|yml)(\?|$)/i.test(collection.value.spec_url);
        loadedSpec = parseSpec(text, isYaml ? 'yaml' : 'json');
      } else {
        throw new Error('Спецификация не найдена');
      }
      
      spec.value = loadedSpec;
      setCurrentSpec(loadedSpec);
      tagTree.value = buildTree(loadedSpec);
    } catch (e) {
      error.value = e instanceof Error 
        ? `Не удалось загрузить спецификацию: ${e.message}`
        : 'Не удалось загрузить спецификацию';
    } finally {
      loading.value = false;
    }
  }
  
  // Автоматическая загрузка при смене коллекции
  watch(collection, load, { immediate: true });
  
  return {
    spec,
    tagTree,
    loading,
    error,
    load
  };
}
