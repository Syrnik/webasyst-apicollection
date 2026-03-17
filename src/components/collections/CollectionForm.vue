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
                    <h1>{{ form.id ? 'Редактировать коллекцию' : 'Добавить коллекцию' }}</h1>
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
                                    v-model="form.title"
                                    type="text"
                                    class="bold"
                                    placeholder="Моя API коллекция"
                                    maxlength="255"
                                >
                                <em v-if="errors.title" class="state-error-hint">
                                    {{ errors.title }}
                                </em>
                            </div>
                        </div>

                        <!-- Источник спецификации (toggle) -->
                        <div class="field">
                            <div class="name">Источник спецификации</div>
                            <div class="value">
                                <div class="toggle" ref="toggleRef">
                  <span
                      data-value="url"
                      :class="{ selected: form.spec_source === 'url' }"
                  >
                    URL
                  </span>
                                    <span
                                        data-value="file"
                                        :class="{ selected: form.spec_source === 'file' }"
                                    >
                    Загрузить файл
                  </span>
                                </div>
                            </div>
                        </div>

                        <!-- URL спецификации -->
                        <template v-if="form.spec_source === 'url'">
                            <div class="field">
                                <div class="name">URL спецификации *</div>
                                <div class="value">
                                    <input
                                        v-model="form.spec_url"
                                        type="url"
                                        placeholder="https://api.example.com/swagger.json"
                                        class="full-width"
                                    >
                                    <em v-if="errors.spec_url" class="state-error-hint">
                                        {{ errors.spec_url }}
                                    </em>
                                </div>
                            </div>
                        </template>

                        <!-- Загрузка файла -->
                        <template v-if="form.spec_source === 'file'">
                            <div class="field">
                                <div class="name">Файл спецификации (.json, .yaml, .yml) *</div>
                                <div class="value">
                                    <div
                                        v-if="form.spec_file"
                                        style="margin-bottom:8px;padding:8px;background:var(--background-color);border-radius:var(--border-radius);display:flex;justify-content:space-between;align-items:center"
                                    >
                    <span style="font-size:12px">
                      <strong>✓</strong> {{ form.spec_file_name || form.spec_file }}
                    </span>
                                        <button
                                            type="button"
                                            class="button light-gray small"
                                            @click="clearFile"
                                            :disabled="uploading"
                                        >
                                            Удалить
                                        </button>
                                    </div>
                                    <div v-else>
                                        <input
                                            ref="fileInputRef"
                                            type="file"
                                            accept=".json,.yaml,.yml"
                                            @change="handleFileUpload"
                                            :disabled="uploading"
                                            style="display:block;margin-bottom:8px"
                                        >
                                        <em v-if="uploading" style="color:var(--accent-color)">
                                            Загрузка...
                                        </em>
                                    </div>
                                    <em v-if="errors.spec_file" class="state-error-hint">
                                        {{ errors.spec_file }}
                                    </em>
                                </div>
                            </div>
                        </template>

                        <!-- Доступ -->
                        <div class="field">
                            <div class="name">Доступ</div>
                            <div class="value">
                                <div class="wa-select">
                                    <select v-model="form.is_shared">
                                        <option :value="0">Личная (только я)</option>
                                        <option :value="1">Общая (все пользователи)</option>
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

                        <!-- Произвольные заголовки -->
                        <div class="field">
                            <div class="name">Произвольные заголовки</div>
                            <div class="value">
                                <div style="margin-bottom:8px">
                                    <em class="hint" style="font-size:11px">
                                        Дополнительные заголовки для всех запросов
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
                        :disabled="saving || uploading"
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
import {ref, reactive, onMounted, nextTick} from 'vue';
import {useCollections} from '@/composables/useCollections';
import {validateCollection, hasErrors} from '@/utils/validation';
import type {Collection, AuthData, CustomHeader} from '@/types';

declare const $: any;

interface Props {
    initial?: Collection | null;
}

interface Emits {
    (e: 'save', data: Partial<Collection>): void;

    (e: 'close'): void;
}

const props = withDefaults(defineProps<Props>(), {
    initial: null
});

const emit = defineEmits<Emits>();

const {save, uploadFile} = useCollections();

// Форма
const form = reactive({
    id: props.initial?.id || null,
    title: props.initial?.title || '',
    spec_source: (props.initial?.spec_source || 'url') as 'url' | 'file',
    spec_url: props.initial?.spec_url || '',
    spec_file: props.initial?.spec_file || '',
    spec_file_name: '',
    is_shared: props.initial?.is_shared || 0,
    auth_type: (props.initial?.auth_type || 'none') as 'none' | 'bearer' | 'basic' | 'apikey',
    auth_data: {
        token: '',
        username: '',
        password: '',
        header: '',
        key: ''
    } as AuthData,
    custom_headers: [] as CustomHeader[]
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
const uploading = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const toggleRef = ref<HTMLElement | null>(null);

// Инициализация toggle
onMounted(() => {
    nextTick(() => {
        if (toggleRef.value && typeof $.fn.waToggle === 'function') {
            $(toggleRef.value).waToggle({
                change: function (_event: any, target: any) {
                    form.spec_source = $(target).data('value');
                }
            });
        }
    });
});

async function handleFileUpload(event: Event): Promise<void> {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    uploading.value = true;
    formErr.value = '';

    try {
        const response = await uploadFile(file);
        form.spec_file = response.file;
        form.spec_file_name = response.name;
        form.spec_source = 'file';
    } catch (e) {
        formErr.value = e instanceof Error ? e.message : 'Ошибка загрузки';
    } finally {
        uploading.value = false;
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    }
}

function clearFile(): void {
    form.spec_file = '';
    form.spec_file_name = '';
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function addCustomHeader(): void {
    form.custom_headers.push({name: '', value: ''});
}

function removeCustomHeader(index: number): void {
    form.custom_headers.splice(index, 1);
}

async function submit(): Promise<void> {
    // Валидация
    Object.keys(errors).forEach(k => delete errors[k]);
    const validationErrors = validateCollection(form);

    if (hasErrors(validationErrors)) {
        Object.assign(errors, validationErrors);
        return;
    }

    saving.value = true;
    formErr.value = '';

    try {
        const data: Partial<Collection> = {
            id: form.id || undefined,
            title: form.title.trim(),
            spec_source: form.spec_source,
            spec_url: form.spec_source === 'url' ? form.spec_url.trim() : '',
            spec_file: form.spec_source === 'file' ? form.spec_file : '',
            is_shared: form.is_shared,
            auth_type: form.auth_type,
            auth_data: JSON.stringify(form.auth_data),
            custom_headers: JSON.stringify(form.custom_headers)
        };

        const result = await save(data);
        emit('save', {...data, id: result.id});
    } catch (e) {
        formErr.value = e instanceof Error ? e.message : 'Ошибка сохранения';
    } finally {
        saving.value = false;
    }
}
</script>
