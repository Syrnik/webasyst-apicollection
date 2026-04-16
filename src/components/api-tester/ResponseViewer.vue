<template>
  <div class="apic-response-panel" style="margin-top:20px">
    <div class="apic-response-panel__header">
      <span class="semibold">Код ответа:</span>
      <span class="badge squared" :class="statusClass(response.response_status)">
        {{ response.response_status || 'ERROR' }}
      </span>
      <span v-if="statusDescription(response.response_status)" class="hint smaller">
        {{ statusDescription(response.response_status) }}
      </span>
      <span style="margin-left:auto" class="hint smaller">
        {{ formatResponseSize(response.response_body) }}
      </span>
    </div>
    
    <div class="apic-response-panel__body">
      <!-- Заголовки ответа -->
      <div v-if="response.response_headers && Object.keys(response.response_headers).length > 0" class="custom-mb-16">
        <div class="semibold" style="margin-bottom:10px;font-size:14px;color:var(--text-color)">HEADERS</div>
        <dl class="apic-headers-list">
          <template v-for="(value, key) in response.response_headers" :key="key">
            <dt>{{ key }}:</dt>
            <dd>{{ value }}</dd>
          </template>
        </dl>
      </div>
      
      <!-- Тело ответа -->
      <div>
        <div class="apic-response-body-label">BODY</div>
        <div class="apic-response-body-wrap">
          <a
            href="javascript:void(0)"
            class="apic-copy-btn"
            style="color:var(--apic-code-color)"
            title="Копировать"
            @click="copyResponse"
          >
            <i class="far fa-copy"></i>
          </a>
          <pre class="apic-response-body">{{ formattedBody }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { statusClass, statusDescription, formatJson, copyToClipboard } from '@/utils/formatters';

interface Props {
  response: {
    response_status: number;
    response_body: string;
    response_headers: Record<string, string>;
  };
  executing: boolean;
}

const props = defineProps<Props>();

const formattedBody = computed(() => {
  try {
    return formatJson(props.response.response_body);
  } catch {
    return props.response.response_body;
  }
});

function formatResponseSize(body: string): string {
  const bytes = new Blob([body]).size;
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

async function copyResponse(): Promise<void> {
  const success = await copyToClipboard(formattedBody.value);
  if (success) {
    alert('Скопировано в буфер обмена');
  }
}
</script>
