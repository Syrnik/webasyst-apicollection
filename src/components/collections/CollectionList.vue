<template>
  <div>
    <div v-if="loading" class="block loading" style="padding:20px;text-align:center">
      <span class="spinner wa-animation-spin"></span>
    </div>
    
    <div v-else-if="error" class="alert danger" style="margin:8px">
      {{ error }}
    </div>
    
    <div v-else-if="!collections.length" style="padding:16px;text-align:center">
      <p class="hint" style="margin-bottom:8px">Коллекций нет</p>
      <button class="button blue small" @click="emit('add')">
        + Добавить
      </button>
    </div>
    
    <ul v-else class="menu ellipsis">
      <li
        v-for="col in collections"
        :key="col.id"
        :class="{ selected: col.id === activeId }"
      >
        <a href="#" @click.prevent="emit('select', col)">
          <span class="count apic-col-actions" @click.stop>
            <span 
              class="apic-col-action" 
              title="Редактировать" 
              @click="emit('edit', col)"
            >
              ✏️
            </span>
            <span 
              class="apic-col-action apic-col-action--delete" 
              title="Удалить" 
              @click="handleDelete(col)"
            >
              🗑️
            </span>
          </span>
          <span>
            {{ col.title }}
            <span class="hint">
              {{ col.is_shared ? '🌐 Общая' : '🔒 Личная' }}
            </span>
          </span>
        </a>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useCollections } from '@/composables/useCollections';
import type { Collection } from '@/types';

interface Props {
  activeId?: number | null;
}

interface Emits {
  (e: 'select', collection: Collection): void;
  (e: 'add'): void;
  (e: 'edit', collection: Collection): void;
  (e: 'delete', collection: Collection): void;
}

const props = withDefaults(defineProps<Props>(), {
  activeId: null
});

const emit = defineEmits<Emits>();

const { collections, loading, error, load } = useCollections();

onMounted(() => {
  load();
});

function handleDelete(col: Collection): void {
  if (confirm(`Удалить коллекцию «${col.title}»?`)) {
    emit('delete', col);
  }
}

// Expose для вызова из родителя
defineExpose({ load });
</script>
