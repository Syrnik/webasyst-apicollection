import type { 
  SwaggerSpec, 
  SwaggerSchema, 
  EndpointTag, 
  EndpointItem 
} from '@/types';

/**
 * Работа со Swagger/OpenAPI спецификациями
 */

// Глобальная переменная для текущей спецификации (для resolveRef)
let currentSpec: SwaggerSpec | null = null;

/**
 * Устанавливает текущую спецификацию
 */
export function setCurrentSpec(spec: SwaggerSpec | null): void {
  currentSpec = spec;
}

/**
 * Получает текущую спецификацию
 */
export function getCurrentSpec(): SwaggerSpec | null {
  return currentSpec;
}

/**
 * Разрешает $ref ссылку на схему
 */
export function resolveRef(ref: string): SwaggerSchema | null {
  if (!ref || !currentSpec) return null;
  
  // Формат: "#/definitions/ModelName" (Swagger 2.0) 
  // или "#/components/schemas/ModelName" (OpenAPI 3.x)
  const parts = ref.split('/');
  const modelName = parts[parts.length - 1];
  
  if (!modelName) return null;
  
  // Swagger 2.0
  if (currentSpec.definitions?.[modelName]) {
    return currentSpec.definitions[modelName];
  }
  
  // OpenAPI 3.x
  if (currentSpec.components?.schemas?.[modelName]) {
    return currentSpec.components.schemas[modelName];
  }
  
  return null;
}

/**
 * Строит дерево endpoints из спецификации
 */
export function buildTree(spec: SwaggerSpec): EndpointTag[] {
  const tags: Record<string, EndpointItem[]> = {};
  const paths = spec.paths || {};
  
  const validMethods = ['get', 'post', 'put', 'delete', 'patch', 'head', 'options'];
  
  for (const [path, methods] of Object.entries(paths)) {
    for (const [method, op] of Object.entries(methods)) {
      if (!validMethods.includes(method.toLowerCase())) {
        continue;
      }
      
      const opTags = (op.tags && op.tags.length > 0) ? op.tags : ['default'];
      
      for (const tag of opTags) {
        if (!tags[tag]) {
          tags[tag] = [];
        }
        
        tags[tag].push({
          method: method.toUpperCase(),
          path,
          op
        });
      }
    }
  }
  
  return Object.entries(tags).map(([name, endpoints]) => ({
    name,
    endpoints
  }));
}

/**
 * Генерирует пример JSON из схемы
 */
export function generateExampleFromSchema(
  schema: SwaggerSchema, 
  depth: number = 0
): any {
  // Защита от бесконечной рекурсии
  if (depth > 5) return null;
  
  if (!schema) return null;
  
  // Если есть $ref, разрешаем его
  if (schema.$ref) {
    const resolved = resolveRef(schema.$ref);
    if (resolved) {
      return generateExampleFromSchema(resolved, depth + 1);
    }
    return null;
  }
  
  // Если есть готовый пример
  if (schema.example !== undefined) {
    return schema.example;
  }
  
  // Если это массив
  if (schema.type === 'array') {
    if (schema.items) {
      const itemExample = generateExampleFromSchema(schema.items, depth + 1);
      return itemExample !== null ? [itemExample] : [];
    }
    return [];
  }
  
  // Если это объект
  if (schema.type === 'object' || (!schema.type && schema.properties)) {
    const obj: Record<string, any> = {};
    
    if (schema.properties) {
      for (const [propName, propSchema] of Object.entries(schema.properties)) {
        const propExample = generateExampleFromSchema(propSchema, depth + 1);
        
        if (propExample !== undefined && propExample !== null) {
          obj[propName] = propExample;
        } else {
          // Дефолтные значения по типу
          if (propSchema.type === 'string') {
            obj[propName] = '';
          } else if (propSchema.type === 'number' || propSchema.type === 'integer') {
            obj[propName] = 0;
          } else if (propSchema.type === 'boolean') {
            obj[propName] = false;
          } else if (propSchema.type === 'array') {
            obj[propName] = [];
          } else if (propSchema.type === 'object') {
            obj[propName] = {};
          }
        }
      }
    }
    
    return obj;
  }
  
  // Примитивные типы
  if (schema.type === 'string') return '';
  if (schema.type === 'number' || schema.type === 'integer') return 0;
  if (schema.type === 'boolean') return false;
  
  return null;
}

/**
 * Строит HTML-представление схемы для отображения
 */
export function buildSchemaView(schema: SwaggerSchema, depth: number = 0): string {
  if (!schema) return '';
  
  let result = '';
  
  // Если есть $ref, разрешаем его
  if (schema.$ref) {
    const resolved = resolveRef(schema.$ref);
    if (resolved) {
      return buildSchemaView(resolved, depth);
    } else {
      const refName = schema.$ref.split('/').pop();
      return `<div class="apic-schema-ref-box"><strong>📦 ${refName}</strong></div>`;
    }
  }
  
  // Если ��то объект
  if ((schema.type === 'object' || !schema.type) && schema.properties) {
    result += '<div class="apic-schema-object">';
    
    if (schema.title) {
      result += `<div class="apic-schema-object-title">${schema.title}</div>`;
    }
    if (schema.description) {
      result += `<div class="apic-schema-object-desc">${schema.description}</div>`;
    }
    
    result += '<div class="apic-schema-properties">';
    
    for (const [propName, propSchema] of Object.entries(schema.properties)) {
      const propType = propSchema.type || (propSchema.$ref ? 'object' : 'unknown');
      const propDesc = propSchema.description || '';
      
      result += '<div class="apic-schema-property">';
      result += `<div class="apic-schema-property-name">
        <span class="apic-schema-key">"${propName}"</span>
        <span class="apic-schema-type">${propType}</span>
      </div>`;
      
      if (propDesc) {
        result += `<div class="apic-schema-property-desc">${propDesc}</div>`;
      }
      
      // Если это $ref
      if (propSchema.$ref) {
        const refName = propSchema.$ref.split('/').pop();
        result += `<div class="apic-schema-ref-inline">→ ${refName}</div>`;
        const resolved = resolveRef(propSchema.$ref);
        if (resolved) {
          result += buildSchemaView(resolved, depth + 1);
        }
      }
      // Если это вложенный объект
      else if (propSchema.type === 'object' && propSchema.properties) {
        result += buildSchemaView(propSchema, depth + 1);
      }
      // Если это массив
      else if (propSchema.type === 'array' && propSchema.items) {
        result += '<div class="apic-schema-array">';
        
        if (propSchema.items.$ref) {
          const refName = propSchema.items.$ref.split('/').pop();
          result += `<div class="apic-schema-ref-inline">→ ${refName}[]</div>`;
          const resolved = resolveRef(propSchema.items.$ref);
          if (resolved) {
            result += buildSchemaView(resolved, depth + 1);
          }
        } else if (propSchema.items.type === 'object' && propSchema.items.properties) {
          result += buildSchemaView(propSchema.items, depth + 1);
        } else {
          result += `<span class="apic-schema-type">${propSchema.items.type || 'object'}[]</span>`;
        }
        
        result += '</div>';
      }
      
      result += '</div>';
    }
    
    result += '</div>';
    result += '</div>';
  }
  // Если это массив
  else if (schema.type === 'array') {
    result += '<div class="apic-schema-array">';
    
    if (schema.items?.type === 'object' && schema.items?.properties) {
      result += buildSchemaView(schema.items, depth + 1);
    } else if (schema.items?.$ref) {
      const refName = schema.items.$ref.split('/').pop();
      result += `<div class="apic-schema-ref-inline">→ ${refName}[]</div>`;
      const resolved = resolveRef(schema.items.$ref);
      if (resolved) {
        result += buildSchemaView(resolved, depth + 1);
      }
    } else {
      result += `<span class="apic-schema-type">${schema.items?.type || 'object'}[]</span>`;
    }
    
    result += '</div>';
  }
  // Примитивный тип
  else {
    result += `<span class="apic-schema-type">${schema.type || 'unknown'}</span>`;
  }
  
  return result;
}
