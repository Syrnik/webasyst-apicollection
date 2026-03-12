<template>
  <div class="fields" style="margin-bottom:20px">
    <!-- Base URL -->
    <div class="field">
      <div class="name">Base URL</div>
      <div class="value">
        <input 
          :value="baseUrl" 
          @input="emit('update:base-url', ($event.target as HTMLInputElement).value)"
          type="url" 
          placeholder="https://api.example.com"
          class="full-width"
        >
        <em class="hint smaller">
          Переопределяет хост из спецификации
        </em>
      </div>
    </div>
    
    <!-- Path Parameters -->
    <template v-if="pathParameters.length > 0">
      <div class="hint small uppercase" style="margin:16px 0 8px">Path Parameters</div>
      <div v-for="param in pathParameters" :key="param.name" class="field">
        <div class="name">
          {{ param.name }}
          <span v-if="param.required" style="color:var(--text-color-error)">*</span>
        </div>
        <div class="value">
          <input
            :value="pathParams[param.name]"
            @input="emit('update:path-params', { ...pathParams, [param.name]: ($event.target as HTMLInputElement).value })"
            type="text"
            :placeholder="param.example || param.default || ''"
          >
          <em v-if="param.schema?.description" class="hint smaller">
            {{ param.schema.description }}
          </em>
        </div>
      </div>
    </template>
    
    <!-- Query Parameters -->
    <template v-if="queryParameters.length > 0">
      <div class="hint small uppercase" style="margin:16px 0 8px">Query Parameters</div>
      <div v-for="param in queryParameters" :key="param.name" class="field">
        <div class="name">
          {{ param.name }}
          <span v-if="param.required" style="color:var(--text-color-error)">*</span>
        </div>
        <div class="value">
          <input
            :value="queryParams[param.name]"
            @input="emit('update:query-params', { ...queryParams, [param.name]: ($event.target as HTMLInputElement).value })"
            type="text"
            :placeholder="param.example || param.default || ''"
          >
          <em v-if="param.schema?.description" class="hint smaller">
            {{ param.schema.description }}
          </em>
        </div>
      </div>
    </template>
    
    <!-- Headers -->
    <template v-if="headerParameters.length > 0">
      <div class="hint small uppercase" style="margin:16px 0 8px">Headers</div>
      <div v-for="param in headerParameters" :key="param.name" class="field">
        <div class="name">
          {{ param.name }}
          <span v-if="param.required" style="color:var(--text-color-error)">*</span>
        </div>
        <div class="value">
          <input
            :value="headerParams[param.name]"
            @input="emit('update:header-params', { ...headerParams, [param.name]: ($event.target as HTMLInputElement).value })"
            type="text"
            :placeholder="param.example || param.default || ''"
          >
        </div>
      </div>
    </template>
    
    <!-- Request Body -->
    <template v-if="hasRequestBody">
      <div style="margin:16px 0 8px;display:flex;justify-content:space-between;align-items:center">
        <div class="hint small uppercase">Request Body</div>
        <button 
          type="button" 
          class="button light-gray small" 
          @click="showBodyDrawer = true"
        >
          📋 Посмотреть схему
        </button>
      </div>
      <div class="field">
        <div class="value">
          <textarea
            :value="bodyContent"
            @input="emit('update:body-content', ($event.target as HTMLTextAreaElement).value)"
            rows="10"
            placeholder='{"key": "value"}'
            style="font-family:monospace"
            class="full-width small"
          ></textarea>
        </div>
      </div>
    </template>
    
    <!-- Кнопка выполнения -->
    <div style="margin-top:20px">
      <button 
        class="button blue" 
        :disabled="executing"
        @click="emit('execute')"
      >
        <i v-if="executing" class="wa-icon wa-icon-spinner wa-animation-spin"></i>
        {{ executing ? 'Выполнение...' : 'Выполнить запрос' }}
      </button>
    </div>
  </div>
  
  <!-- Drawer для просмотра схемы body -->
  <RequestBodyDrawer
    v-if="showBodyDrawer"
    :endpoint="endpoint"
    @close="showBodyDrawer = false"
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import RequestBodyDrawer from './RequestBodyDrawer.vue';
import type { Collection, Environment, EndpointItem } from '@/types';

interface Props {
  endpoint: EndpointItem;
  collection: Collection;
  baseUrl: string;
  activeEnvironment?: Environment | null;
  // Параметры из composable ApiTester
  pathParams: Record<string, string>;
  queryParams: Record<string, string>;
  headerParams: Record<string, string>;
  bodyContent: string;
  executing: boolean;
}

interface Emits {
  (e: 'execute'): void;
  (e: 'update:base-url', value: string): void;
  (e: 'update:path-params', value: Record<string, string>): void;
  (e: 'update:query-params', value: Record<string, string>): void;
  (e: 'update:header-params', value: Record<string, string>): void;
  (e: 'update:body-content', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  activeEnvironment: null
});

const emit = defineEmits<Emits>();

const showBodyDrawer = ref(false);

// Группировка параметров по типу
const pathParameters = computed(() => {
  return (props.endpoint.op.parameters || []).filter(p => p.in === 'path');
});

const queryParameters = computed(() => {
  return (props.endpoint.op.parameters || []).filter(p => p.in === 'query');
});

const headerParameters = computed(() => {
  return (props.endpoint.op.parameters || []).filter(p => p.in === 'header');
});

const hasRequestBody = computed(() => {
  // Показываем body для POST/PUT/PATCH даже если нет requestBody в спецификации
  const methodsWithBody = ['POST', 'PUT', 'PATCH'];
  return methodsWithBody.includes(props.endpoint.method.toUpperCase()) || !!props.endpoint.op.requestBody;
});
</script>
