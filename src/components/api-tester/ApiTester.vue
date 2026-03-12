<template>
  <div class="flexbox" style="height:calc(100vh - 120px)">
    <!-- Сайдбар с деревом endpoints -->
    <div class="sidebar blank width-20rem flexbox">
      <div class="sidebar-header" style="padding:12px;border-bottom:1px solid var(--border-color)">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Поиск endpoints..." 
          class="full-width"
          style="font-size:12px"
        >
      </div>
      
      <div class="sidebar-body">
        <div v-if="loading" style="padding:20px;text-align:center">
          <span class="spinner wa-animation-spin"></span>
        </div>
        
        <div v-else-if="error" class="alert danger" style="margin:8px">
          {{ error }}
        </div>
        
        <EndpointTree
          v-else
          :tag-tree="filteredTree"
          :active-endpoint="activeEndpoint"
          @select="selectEndpoint"
        />
      </div>
    </div>
    
    <!-- Основная область -->
    <div class="content" style="overflow-y:auto">
      <div v-if="!activeEndpoint" style="padding:40px;text-align:center">
        <p class="hint">Выберите endpoint из списка слева</p>
      </div>
      
      <div v-else style="padding:20px">
        <!-- Заголовок endpoint -->
        <div style="margin-bottom:20px">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
            <span :class="methodBadgeClass(activeEndpoint.method)">
              {{ activeEndpoint.method }}
            </span>
            <code style="font-size:14px;font-weight:600">{{ activeEndpoint.path }}</code>
          </div>
          <p v-if="activeEndpoint.op.summary" class="hint" style="margin:0">
            {{ activeEndpoint.op.summary }}
          </p>
        </div>
        
        <!-- Форма запроса -->
        <RequestForm
          :endpoint="activeEndpoint"
          :collection="collection"
          :base-url="baseUrl"
          :active-environment="activeEnvironment"
          :path-params="pathParams"
          :query-params="queryParams"
          :header-params="headerParams"
          :body-content="bodyContent"
          :executing="executing"
          @execute="handleExecute"
          @update:base-url="baseUrl = $event"
          @update:path-params="Object.assign(pathParams, $event)"
          @update:query-params="Object.assign(queryParams, $event)"
          @update:header-params="Object.assign(headerParams, $event)"
          @update:body-content="bodyContent = $event"
        />
        
        <!-- Ответ -->
        <ResponseViewer
          v-if="response"
          :response="response"
          :executing="executing"
        />
        
        <!-- История -->
        <RequestHistory
          :collection-id="collection.id"
          @replay="replayRequest"
          :key="collection.id"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import EndpointTree from './EndpointTree.vue';
import RequestForm from './RequestForm.vue';
import ResponseViewer from './ResponseViewer.vue';
import RequestHistory from './RequestHistory.vue';
import { useSwaggerSpec } from '@/composables/useSwaggerSpec';
import { useApiRequest } from '@/composables/useApiRequest';
import { useRequestHistory } from '@/composables/useRequestHistory';
import { useEndpointCache } from '@/composables/useEndpointCache';
import { methodBadgeClass } from '@/utils/formatters';
import { extractBaseUrl } from '@/utils/formatters';
import type { Collection, Environment, EndpointItem } from '@/types';

interface Props {
  collection: Collection;
  activeEnvironment?: Environment | null;
}

const props = withDefaults(defineProps<Props>(), {
  activeEnvironment: null
});

// Загрузка спецификации
const collectionRef = computed(() => props.collection);
const { spec, tagTree, loading, error } = useSwaggerSpec(collectionRef);

// API запросы
const {
  executing,
  response,
  execute,
  pathParams,
  queryParams,
  headerParams,
  bodyContent,
  setParamsFromSpec
} = useApiRequest();

// История запросов
const collectionIdRef = computed(() => props.collection.id);
const { load: reloadHistory, addItem: addHistoryItem } = useRequestHistory(collectionIdRef);

// Кеш параметров по endpoint'ам
const endpointCache = useEndpointCache();

// Состояние
const searchQuery = ref('');
const activeEndpoint = ref<EndpointItem | null>(null);
const baseUrl = ref('');

// Инициализация base URL
if (props.collection.spec_url) {
  baseUrl.value = extractBaseUrl(props.collection.spec_url);
}

// Обновление base URL при изменении окружения
watch(() => props.activeEnvironment, (newEnv) => {
  if (newEnv?.base_url) {
    // Если выбрано окружение с base_url — используем его
    baseUrl.value = newEnv.base_url;
  } else if (props.collection.spec_url) {
    // Иначе возвращаемся к base URL из спецификации
    baseUrl.value = extractBaseUrl(props.collection.spec_url);
  }
}, { immediate: true });

// Фильтрация дерева по поиску
const filteredTree = computed(() => {
  if (!searchQuery.value.trim()) {
    return tagTree.value;
  }
  
  const query = searchQuery.value.toLowerCase();
  return tagTree.value
    .map(tag => ({
      ...tag,
      endpoints: tag.endpoints.filter(ep => 
        ep.path.toLowerCase().includes(query) ||
        ep.method.toLowerCase().includes(query) ||
        ep.op.summary?.toLowerCase().includes(query)
      )
    }))
    .filter(tag => tag.endpoints.length > 0);
});

// Выбор endpoint
function selectEndpoint(endpoint: EndpointItem): void {
  // Сохраняем параметры предыдущего endpoint'а
  if (activeEndpoint.value) {
    endpointCache.saveState(
      activeEndpoint.value.method,
      activeEndpoint.value.path,
      pathParams,
      queryParams,
      headerParams,
      bodyContent
    );
  }

  activeEndpoint.value = endpoint;

  // Очищаем ответ при переключении endpoint
  response.value = null;

  // Устанавливаем параметры из спецификации
  const params = endpoint.op.parameters || [];

  // Выполняем один микротик, чтобы гарантировать обновление DOM
  // перед инициализацией параметров
  Promise.resolve().then(() => {
    // Проверяем, есть ли сохранённое состояние для этого endpoint'а
    const cachedState = endpointCache.getState(endpoint.method, endpoint.path);

    if (cachedState) {
      // Восстанавливаем сохранённые параметры
      Object.assign(pathParams, cachedState.pathParams);
      Object.assign(queryParams, cachedState.queryParams);
      Object.assign(headerParams, cachedState.headerParams);
      bodyContent.value = cachedState.bodyContent;
    } else {
      // Используем значения по умолчанию из спецификации
      setParamsFromSpec(params);
    }
  });
}

// Выполнение запроса
async function handleExecute(): Promise<void> {
  if (!activeEndpoint.value) return;

  // Сохраняем текущее состояние перед выполнением
  endpointCache.saveState(
    activeEndpoint.value.method,
    activeEndpoint.value.path,
    pathParams,
    queryParams,
    headerParams,
    bodyContent.value
  );

  await execute(
    props.collection,
    activeEndpoint.value.method,
    activeEndpoint.value.path,
    baseUrl.value,
    props.activeEnvironment
  );

  // Обновляем историю запросов
  await reloadHistory();
}

// Повтор запроса из истории
function replayRequest(historyItem: any): void {
  try {
    const requestData = JSON.parse(historyItem.request_data);
    
    // Находим endpoint
    const endpoint = tagTree.value
      .flatMap(tag => tag.endpoints)
      .find(ep => ep.path === historyItem.path && ep.method === historyItem.method);
    
    if (endpoint) {
      activeEndpoint.value = endpoint;
      
      // Восстанавливаем параметры
      if (requestData.query_params) {
        Object.assign(queryParams, requestData.query_params);
      }
      if (requestData.headers) {
        Object.assign(headerParams, requestData.headers);
      }
      if (requestData.body) {
        bodyContent.value = requestData.body;
      }
    }
  } catch (e) {
    console.error('Failed to replay request:', e);
  }
}
</script>
