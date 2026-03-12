<template>
  <Teleport to="body">
    <div class="drawer drawer-opened drawer-right" @click.self="emit('close')">
      <div class="drawer-background"></div>
      <div class="drawer-body" style="width:600px;right:0">
        <a href="#" class="drawer-close" @click.prevent="emit('close')"><i class="fas fa-times"></i></a>
        <div class="drawer-block">
          <header class="drawer-header">
            <h2>Схема тела запроса</h2>
            
            <!-- Вкладки в header -->
            <ul v-if="requestBody" class="tabs custom-mt-12">
              <li :class="{ selected: activeTab === 'schema' }">
                <a href="#" @click.prevent="activeTab = 'schema'">Модель</a>
              </li>
              <li :class="{ selected: activeTab === 'example' }">
                <a href="#" @click.prevent="activeTab = 'example'">Пример</a>
              </li>
            </ul>
          </header>
          
          <div class="drawer-content">
          <!-- Отладка -->
          <div v-if="!requestBody" class="apic-drawer-empty text-center custom-py-40 custom-px-20">
            <p class="hint">
              Схема Request Body не найдена в спецификации.<br>
              Вы можете ввести JSON вручную в поле Request Body.
            </p>
            <details class="custom-mt-20 text-left">
              <summary style="cursor:pointer;color:var(--text-color-secondary)">Отладка</summary>
              <pre class="custom-mt-12 custom-p-12" style="font-size:11px;background:var(--background-color);border-radius:4px;overflow:auto">{{ JSON.stringify(endpoint.op, null, 2) }}</pre>
            </details>
          </div>
          
          <div v-else class="custom-pt-20">
            <!-- Описание -->
            <div v-if="requestBody.description" class="apic-drawer-description">
              {{ requestBody.description }}
            </div>
            
            <!-- Вкладка: Модель -->
            <div v-if="activeTab === 'schema'" class="apic-drawer-section">
              <div v-if="schema" class="apic-schema-view" v-html="schemaHtml"></div>
              <div v-else class="hint">Схема не определена</div>
            </div>
            
            <!-- Вкладка: Пример -->
            <div v-if="activeTab === 'example'" class="apic-drawer-section">
              <div class="flexbox space-between middle custom-mb-8">
                <span class="hint small uppercase" style="flex:1">JSON Example</span>
                <button 
                  type="button" 
                  class="button light-gray smallest" 
                  style="flex-shrink:0"
                  @click="copyExample"
                >
                  📋 Копировать
                </button>
              </div>
              <pre class="apic-drawer-example">{{ exampleJson }}</pre>
            </div>
          </div>
          </div>
          
          <footer class="drawer-footer">
            <button type="button" class="button light-gray" @click="emit('close')">
              Закрыть
            </button>
          </footer>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { buildSchemaView, generateExampleFromSchema } from '@/utils/swagger';
import { copyToClipboard } from '@/utils/formatters';
import type { EndpointItem, SwaggerSchema } from '@/types';

interface Props {
  endpoint: EndpointItem;
}

interface Emits {
  (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const activeTab = ref<'schema' | 'example'>('schema');

const requestBody = computed(() => {
  // OpenAPI 3.x: requestBody
  if (props.endpoint.op.requestBody) {
    console.log('Found OpenAPI 3.x requestBody:', props.endpoint.op.requestBody);
    return props.endpoint.op.requestBody;
  }
  
  // Swagger 2.0: parameters с in: "body"
  const bodyParam = props.endpoint.op.parameters?.find(p => p.in === 'body');
  if (bodyParam) {
    console.log('Found Swagger 2.0 body parameter:', bodyParam);
    // Преобразуем в формат OpenAPI 3.x для единообразия
    return {
      description: bodyParam.description,
      required: bodyParam.required,
      content: {
        'application/json': {
          schema: bodyParam.schema
        }
      }
    };
  }
  
  console.log('No requestBody found');
  return null;
});

const schema = computed<SwaggerSchema | null>(() => {
  if (!requestBody.value?.content) {
    console.log('No requestBody.content');
    return null;
  }
  
  // Ищем application/json
  const jsonContent = requestBody.value.content['application/json'];
  if (jsonContent?.schema) {
    console.log('Found schema in application/json:', jsonContent.schema);
    return jsonContent.schema;
  }
  
  // Или первый доступный content type
  const firstContent = Object.values(requestBody.value.content)[0];
  console.log('First content:', firstContent);
  return firstContent?.schema || null;
});

const schemaHtml = computed(() => {
  if (!schema.value) return '';
  return buildSchemaView(schema.value);
});

const exampleJson = computed(() => {
  if (!schema.value) return '{}';
  
  // Сначала пробуем взять пример из спецификации
  if (requestBody.value?.content) {
    const jsonContent = requestBody.value.content['application/json'];
    if (jsonContent?.example) {
      return JSON.stringify(jsonContent.example, null, 2);
    }
  }
  
  // Генерируем из схемы
  const generated = generateExampleFromSchema(schema.value);
  return JSON.stringify(generated, null, 2);
});

async function copyExample(): Promise<void> {
  const success = await copyToClipboard(exampleJson.value);
  if (success) {
    alert('Скопировано в буфер обмена');
  }
}
</script>

<style scoped>
/* Переопределяем display для Vue-компонента (штатный drawer управляется через jQuery) */
.drawer {
  display: block !important;
}
</style>
