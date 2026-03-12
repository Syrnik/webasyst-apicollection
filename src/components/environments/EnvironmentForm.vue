<template>
  <Teleport to="body">
    <div class="dialog dialog-opened">
      <div class="dialog-background" @click="emit('close')"></div>
      <div 
        class="dialog-body" 
        style="top:50%;left:50%;transform:translate(-50%,-50%);max-height:90vh;overflow-y:auto;width:600px"
      >
        <a 
          href="#" 
          class="dialog-close" 
          @click.prevent="emit('close')" 
          aria-label="Close"
        >
          &times;
        </a>
        
        <header class="dialog-header">
          <h1>{{ form.id ? 'Редактировать окружение' : 'Добавить окружение' }}</h1>
        </header>
        
        <div class="dialog-content">
          <div v-if="formErr" class="alert danger" style="margin-bottom:12px">
            {{ formErr }}
          </div>
          
          <div class="fields">
            <!-- Название -->
            <div class="field">
              <div class="name">Название *</div>
              <div class="value">
                <input 
                  v-model="form.name" 
                  type="text" 
                  class="bold" 
                  placeholder="Production" 
                  maxlength="255"
                >
                <em v-if="errors.name" class="state-error-hint">
                  {{ errors.name }}
                </em>
              </div>
            </div>
            
            <!-- Base URL -->
            <div class="field">
              <div class="name">Base URL *</div>
              <div class="value">
                <input 
                  v-model="form.base_url" 
                  type="url" 
                  placeholder="https://api.example.com" 
                  class="full-width"
                >
                <em class="hint" style="font-size:11px">
                  Базовый URL API для этого окружения
                </em>
                <em v-if="errors.base_url" class="state-error-hint">
                  {{ errors.base_url }}
                </em>
              </div>
            </div>
            
            <!-- Доступ -->
            <div class="field">
              <div class="name">Доступ</div>
              <div class="value">
                <div class="wa-select">
                  <select v-model="form.is_shared">
                    <option :value="0">Личное (только я)</option>
                    <option :value="1">Общее (все пользователи)</option>
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Аутентификация -->
            <div class="field">
              <div class="name">Аутентификация</div>
              <div class="value">
                <div class="wa-select">
                  <select v-model="form.auth_type">
                    <option value="none">Без аутентификации</option>
                    <option value="bearer">Bearer Token</option>
                    <option value="basic">Basic Auth</option>
                    <option value="apikey">API Key</option>
                  </select>
                </div>
                <em class="hint" style="font-size:11px">
                  Переопределяет аутентификацию коллекции
                </em>
              </div>
            </div>
            
            <!-- Поля аутентификации -->
            <template v-if="form.auth_type === 'bearer'">
              <div class="field">
                <div class="name">Token</div>
                <div class="value">
                  <input v-model="form.auth_data.token" type="text" placeholder="eyJ...">
                </div>
              </div>
            </template>
            
            <template v-if="form.auth_type === 'basic'">
              <div class="field">
                <div class="name">Username</div>
                <div class="value">
                  <input v-model="form.auth_data.username" type="text">
                </div>
              </div>
              <div class="field">
                <div class="name">Password</div>
                <div class="value">
                  <input v-model="form.auth_data.password" type="password">
                </div>
              </div>
            </template>
            
            <template v-if="form.auth_type === 'apikey'">
              <div class="field">
                <div class="name">Header Name</div>
                <div class="value">
                  <input v-model="form.auth_data.header" type="text" placeholder="X-API-Key">
                </div>
              </div>
              <div class="field">
                <div class="name">Key Value</div>
                <div class="value">
                  <input v-model="form.auth_data.key" type="text">
                </div>
              </div>
            </template>
            
            <!-- Произвольные заголо��ки -->
            <div class="field">
              <div class="name">Произвольные заголовки</div>
              <div class="value">
                <div style="margin-bottom:8px">
                  <em class="hint" style="font-size:11px">
                    Дополнительные заголовки для этого окружения
                  </em>
                </div>
                
                <div 
                  v-if="form.custom_headers.length === 0" 
                  style="padding:8px;background:var(--background-color);border-radius:var(--border-radius);text-align:center;color:var(--text-color-secondary)"
                >
                  <em style="font-size:12px">Нет заголовков</em>
                </div>
                
                <div v-else style="margin-bottom:8px">
                  <div 
                    v-for="(header, idx) in form.custom_headers" 
                    :key="idx" 
                    style="display:flex;gap:6px;margin-bottom:6px;align-items:flex-start"
                  >
                    <input 
                      v-model="header.name" 
                      type="text" 
                      placeholder="Имя заголовка" 
                      style="flex:1;font-size:12px"
                    >
                    <input 
                      v-model="header.value" 
                      type="text" 
                      placeholder="Значение" 
                      style="flex:1;font-size:12px"
                    >
                    <button 
                      type="button" 
                      class="button light-gray small" 
                      @click="removeCustomHeader(idx)" 
                      style="flex:none"
                    >
                      ✕
                    </button>
                  </div>
                </div>
                
                <button 
                  type="button" 
                  class="button light-gray small" 
                  @click="addCustomHeader"
                >
                  + Добавить заголовок
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <footer class="dialog-footer">
          <button class="button light-gray" @click="emit('close')">
            Отмена
          </button>
          <button 
            class="button blue" 
            :disabled="saving" 
            @click="submit"
          >
            <i v-if="saving" class="wa-icon wa-icon-spinner wa-animation-spin"></i>
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useEnvironments } from '@/composables/useEnvironments';
import { validateEnvironment, hasErrors } from '@/utils/validation';
import type { Environment, AuthData, CustomHeader } from '@/types';

interface Props {
  initial?: Environment | null;
}

interface Emits {
  (e: 'save', data: Partial<Environment>): void;
  (e: 'close'): void;
}

const props = withDefaults(defineProps<Props>(), {
  initial: null
});

const emit = defineEmits<Emits>();

const { save } = useEnvironments();

// Форма
const form = reactive({
  id: props.initial?.id || null,
  name: props.initial?.name || '',
  base_url: props.initial?.base_url || '',
  is_shared: props.initial?.is_shared || 0,
  auth_type: (props.initial?.auth_type || 'none') as 'none' | 'bearer' | 'basic' | 'apikey',
  auth_data: {
    token: '',
    username: '',
    password: '',
    header: '',
    key: ''
  } as AuthData,
  custom_headers: [] as CustomHeader[],
  sort: props.initial?.sort || 0
});

// Загружаем auth_data и custom_headers если редактируем
if (props.initial?.auth_data) {
  try {
    Object.assign(form.auth_data, JSON.parse(props.initial.auth_data));
  } catch (e) {
    // ignore
  }
}

if (props.initial?.custom_headers) {
  try {
    form.custom_headers = JSON.parse(props.initial.custom_headers) || [];
  } catch (e) {
    // ignore
  }
}

const errors = reactive<Record<string, string>>({});
const saving = ref(false);
const formErr = ref('');

function addCustomHeader(): void {
  form.custom_headers.push({ name: '', value: '' });
}

function removeCustomHeader(index: number): void {
  form.custom_headers.splice(index, 1);
}

async function submit(): Promise<void> {
  // Валидация
  Object.keys(errors).forEach(k => delete errors[k]);
  const validationErrors = validateEnvironment(form);
  
  if (hasErrors(validationErrors)) {
    Object.assign(errors, validationErrors);
    return;
  }
  
  saving.value = true;
  formErr.value = '';
  
  try {
    const data: Partial<Environment> = {
      id: form.id || undefined,
      name: form.name.trim(),
      base_url: form.base_url.trim(),
      is_shared: form.is_shared,
      auth_type: form.auth_type,
      auth_data: JSON.stringify(form.auth_data),
      custom_headers: JSON.stringify(form.custom_headers),
      sort: form.sort
    };
    
    const result = await save(data);
    emit('save', { ...data, id: result.id });
  } catch (e) {
    formErr.value = e instanceof Error ? e.message : 'Ошибка сохранения';
  } finally {
    saving.value = false;
  }
}
</script>
