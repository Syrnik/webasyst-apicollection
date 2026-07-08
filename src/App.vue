<template>
  <div class="flexbox" style="height:100vh">
    <!-- Сайдбар с коллекциями -->
    <div class="sidebar flexbox width-18rem height-auto">
      <div class="sidebar-header">
        <div 
          class="flexbox middle" 
          style="padding:14px 16px 10px;border-bottom:1px solid var(--border-color);justify-content:space-between"
        >
          <span class="semibold">Коллекции</span>
          <button class="button blue small" @click="openAddForm">
            + Добавить
          </button>
        </div>
      </div>
      
      <div class="sidebar-body">
        <CollectionList
          ref="listRef"
          :active-id="selectedCollection?.id"
          @select="selectCollection"
          @add="openAddForm"
          @edit="openEditForm"
          @delete="handleDelete"
        />
      </div>
    </div>
    
    <!-- Основная область -->
    <div class="content">
      <!-- Тулбар -->
      <div 
        class="apic-toolbar" 
        style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border-color)"
      >
        <template v-if="selectedCollection">
          <strong>{{ selectedCollection.title }}</strong>
          <span class="hint text-ellipsis smaller">
            {{ selectedCollection.spec_url || selectedCollection.spec_file }}
          </span>
          <span style="margin-left:auto">
            <EnvironmentSelector
              ref="envSelectorRef"
              :collection-id="selectedCollection.id"
              :active-env="activeEnvironment"
              @select="onEnvSelect"
              @manage="showEnvManager = true"
            />
          </span>
        </template>
        <span v-else class="hint">
          Выберите коллекцию
        </span>
      </div>
      
      <!-- Пустое состояние -->
      <div v-if="!selectedCollection" class="apic-empty" style="min-height:400px">
        <div class="apic-empty__icon">📡</div>
        <div class="hint">
          Добавьте коллекцию и выберите её для начала работы
        </div>
        <button class="button blue" @click="openAddForm">
          + Добавить коллекцию
        </button>
      </div>
      
      <!-- API Tester -->
      <ApiTester 
        v-else
        :collection="selectedCollection" 
        :active-environment="activeEnvironment"
      />
    </div>
    
    <!-- Модальные окна -->
    <CollectionForm
      v-if="showForm"
      :initial="editingCollection"
      @save="handleSaved"
      @close="showForm = false"
    />
    
    <EnvironmentManager
      v-if="showEnvManager"
      @close="showEnvManager = false"
      @add="openEnvAddForm"
      @edit="openEnvEditForm"
      @changed="handleEnvManagerChanged"
    />
    
    <EnvironmentForm
      v-if="showEnvForm"
      :initial="editingEnvironment"
      @save="handleEnvSaved"
      @close="showEnvForm = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import CollectionList from '@/components/collections/CollectionList.vue';
import CollectionForm from '@/components/collections/CollectionForm.vue';
import ApiTester from '@/components/api-tester/ApiTester.vue';
import EnvironmentSelector from '@/components/environments/EnvironmentSelector.vue';
import EnvironmentManager from '@/components/environments/EnvironmentManager.vue';
import EnvironmentForm from '@/components/environments/EnvironmentForm.vue';
import { useCollections } from '@/composables/useCollections';
import { useEnvironments } from '@/composables/useEnvironments';
import type { Collection, Environment } from '@/types';

// Коллекции
const selectedCollection = ref<Collection | null>(null);
const showForm = ref(false);
const editingCollection = ref<Collection | null>(null);
const listRef = ref<InstanceType<typeof CollectionList> | null>(null);

const { remove: removeCollection, getById: getCollectionById } = useCollections();

// Окружения
const activeEnvironment = ref<Environment | null>(null);
const showEnvManager = ref(false);
const showEnvForm = ref(false);
const editingEnvironment = ref<Environment | null>(null);
const envSelectorRef = ref<InstanceType<typeof EnvironmentSelector> | null>(null);

const { getById: getEnvironmentById, getSelected } = useEnvironments();

// Выбор коллекции
function selectCollection(col: Collection): void {
  selectedCollection.value = col;
}

// Открыть форму добавления
function openAddForm(): void {
  editingCollection.value = null;
  showForm.value = true;
}

// Открыть форму редактирования
async function openEditForm(col: Collection): Promise<void> {
  try {
    editingCollection.value = await getCollectionById(col.id);
  } catch {
    editingCollection.value = { ...col };
  }
  showForm.value = true;
}

// Удалить коллекцию
async function handleDelete(col: Collection): Promise<void> {
  try {
    await removeCollection(col.id);
    
    if (selectedCollection.value?.id === col.id) {
      selectedCollection.value = null;
    }
    
    listRef.value?.load();
  } catch (e) {
    alert('Ошибка удаления: ' + (e instanceof Error ? e.message : 'Неизвестная ошибка'));
  }
}

// Сохранение коллекции
function handleSaved(): void {
  showForm.value = false;
  editingCollection.value = null;
  listRef.value?.load();
}

// Выбор окружения
function onEnvSelect(env: Environment | null): void {
  activeEnvironment.value = env;
}

// Открыть форму добавления окружения
function openEnvAddForm(): void {
  showEnvManager.value = false;
  editingEnvironment.value = null;
  showEnvForm.value = true;
}

// Открыть форму редактирования окружения
async function openEnvEditForm(env: Environment): Promise<void> {
  showEnvManager.value = false;

  try {
    editingEnvironment.value = await getEnvironmentById(env.id);
  } catch {
    editingEnvironment.value = { ...env };
  }
  
  showEnvForm.value = true;
}

// Сохранение окружения
function handleEnvSaved(): void {
  showEnvForm.value = false;
  editingEnvironment.value = null;
  showEnvManager.value = true;
  envSelectorRef.value?.load();
}

// Изменения в менеджере окружений
function handleEnvManagerChanged(): void {
  envSelectorRef.value?.load();
  
  // Проверяем, существует ли ещё активное окружение
  if (activeEnvironment.value) {
    getEnvironmentById(activeEnvironment.value.id)
      .catch(() => {
        activeEnvironment.value = null;
      });
  }
}

// Загрузка выбранного окружения при смене коллекции
watch(selectedCollection, async (col) => {
  if (col) {
    try {
      const res = await getSelected(col.id);
      activeEnvironment.value = res.environment || null;
    } catch {
      activeEnvironment.value = null;
    }
  } else {
    activeEnvironment.value = null;
  }
});
</script>
