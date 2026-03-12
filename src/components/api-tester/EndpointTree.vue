<template>
  <ul class="menu">
    <li v-for="tag in tagTree" :key="tag.name">
      <a 
        href="#" 
        @click.prevent="toggleTag(tag.name)"
        class="semibold uppercase"
      >
        <span>{{ tag.name }}</span>
        <span class="count" style="flex:none">{{ tag.endpoints.length }}</span>
      </a>
      
      <ul v-if="expandedTags.has(tag.name)" class="menu">
        <li
          v-for="(endpoint, idx) in tag.endpoints"
          :key="`${endpoint.method}-${endpoint.path}-${idx}`"
          :class="{ selected: isActive(endpoint) }"
        >
          <a href="#" @click.prevent="emit('select', endpoint)">
            <span style="flex:none;margin-right:8px">
              <span :class="methodBadgeClass(endpoint.method)">
                {{ endpoint.method }}
              </span>
            </span>
            <span>
              {{ endpoint.path }}
              <span v-if="endpoint.op.summary" class="hint smaller" style="display:block">
                {{ truncate(endpoint.op.summary, 50) }}
              </span>
            </span>
          </a>
        </li>
      </ul>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { methodBadgeClass, truncate } from '@/utils/formatters';
import type { EndpointTag, EndpointItem } from '@/types';

interface Props {
  tagTree: EndpointTag[];
  activeEndpoint?: EndpointItem | null;
}

interface Emits {
  (e: 'select', endpoint: EndpointItem): void;
}

const props = withDefaults(defineProps<Props>(), {
  activeEndpoint: null
});

const emit = defineEmits<Emits>();

const expandedTags = ref<Set<string>>(new Set());

// Раскрываем все теги при монтировании
onMounted(() => {
  props.tagTree.forEach(tag => {
    expandedTags.value.add(tag.name);
  });
});

function toggleTag(tagName: string): void {
  if (expandedTags.value.has(tagName)) {
    expandedTags.value.delete(tagName);
  } else {
    expandedTags.value.add(tagName);
  }
}

function isActive(endpoint: EndpointItem): boolean {
  if (!props.activeEndpoint) return false;
  return (
    endpoint.method === props.activeEndpoint.method &&
    endpoint.path === props.activeEndpoint.path
  );
}
</script>
