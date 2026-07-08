<template>
  <div class="apic-env-dropdown" style="position:relative">
    <button 
      class="button light-gray small" 
      @click="toggleDropdown"
      style="display:flex;align-items:center;gap:6px"
    >
      <span>🌍</span>
      <span>{{ activeEnv ? activeEnv.name : 'Без окружения' }}</span>
      <span style="font-size:10px">▼</span>
    </button>
    
    <div 
      v-if="showDropdown" 
      class="dropdown-menu"
      style="position:absolute;top:100%;right:0;margin-top:4px;min-width:200px;background:var(--background-color-blank);border:1px solid var(--border-color);border-radius:var(--border-radius);box-shadow:0 2px 8px rgba(0,0,0,0.1);z-index:100"
    >
      <div style="padding:8px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center">
        <span class="semibold" style="font-size:12px">Окружения</span>
        <button 
          type="button" 
          class="button light-gray small" 
          @click="openManager"
          style="font-size:11px;padding:2px 6px"
        >
          ⚙️
        </button>
      </div>
      
      <div v-if="loading" style="padding:12px;text-align:center">
        <span class="spinner wa-animation-spin"></span>
      </div>
      
      <div v-else-if="error" class="alert danger" style="margin:8px;font-size:11px">
        {{ error }}
      </div>
      
      <div v-else>
        <a 
          href="#" 
          class="dropdown-item"
          :class="{ active: !activeEnv }"
          @click.prevent="selectEnvironment(null)"
          style="display:block;padding:8px 12px;font-size:12px;text-decoration:none;color:var(--text-color)"
        >
          <span>Без окружения</span>
        </a>
        
        <a 
          v-for="env in environments" 
          :key="env.id"
          href="#" 
          class="dropdown-item"
          :class="{ active: activeEnv?.id === env.id }"
          @click.prevent="selectEnvironment(env)"
          style="display:block;padding:8px 12px;font-size:12px;text-decoration:none;color:var(--text-color);border-top:1px solid var(--border-color)"
        >
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span>{{ env.name }}</span>
            <span v-if="env.is_shared" class="hint" style="font-size:10px">🌐</span>
          </div>
          <div class="hint" style="font-size:10px;margin-top:2px">{{ env.base_url }}</div>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useEnvironments } from '@/composables/useEnvironments';
import type { Environment } from '@/types';

interface Props {
  collectionId: number;
  activeEnv?: Environment | null;
}

interface Emits {
  (e: 'select', env: Environment | null): void;
  (e: 'manage'): void;
}

const props = withDefaults(defineProps<Props>(), {
  activeEnv: null
});

const emit = defineEmits<Emits>();

const { environments, loading, error, load, select } = useEnvironments();
const showDropdown = ref(false);

onMounted(() => {
  load();
});

function toggleDropdown(): void {
  showDropdown.value = !showDropdown.value;
}

async function selectEnvironment(env: Environment | null): Promise<void> {
  try {
    await select(props.collectionId, env?.id || null);
    emit('select', env);
    showDropdown.value = false;
  } catch (e) {
    alert('Ошибка выбора окружения: ' + (e instanceof Error ? e.message : 'Неизвестная ошибка'));
  }
}

function openManager(): void {
  showDropdown.value = false;
  emit('manage');
}

// Закрытие dropdown при клике вне его
function handleClickOutside(event: MouseEvent): void {
  const target = event.target as HTMLElement;
  if (!target.closest('.apic-env-dropdown')) {
    showDropdown.value = false;
  }
}

watch(showDropdown, (show) => {
  if (show) {
    document.addEventListener('click', handleClickOutside);
  } else {
    document.removeEventListener('click', handleClickOutside);
  }
});

// Expose для вызова из родителя
defineExpose({ load });
</script>
