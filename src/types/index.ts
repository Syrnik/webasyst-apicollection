/**
 * TypeScript типы для apicollection
 */

// Коллекция
export interface Collection {
  id: number;
  contact_id: number;
  is_shared: 0 | 1;
  title: string;
  spec_source: 'url' | 'file';
  spec_url: string | null;
  spec_file: string | null;
  auth_type: 'none' | 'bearer' | 'basic' | 'apikey';
  auth_data: string; // JSON
  custom_headers: string; // JSON
  created: string;
  updated: string;
}

// Окружение
export interface Environment {
  id: number;
  contact_id: number;
  is_shared: 0 | 1;
  name: string;
  base_url: string;
  auth_type: 'none' | 'bearer' | 'basic' | 'apikey';
  auth_data: string; // JSON
  custom_headers: string; // JSON
  sort: number;
  created: string;
  updated: string;
}

// История запросов
export interface RequestHistoryItem {
  id: number;
  collection_id: number;
  contact_id: number;
  method: string;
  path: string;
  request_data: string; // JSON
  response_status: number;
  response_body: string;
  executed_at: string;
}

// Данные аутентификации
export interface AuthData {
  token?: string;
  username?: string;
  password?: string;
  header?: string;
  key?: string;
}

// Произвольный заголовок
export interface CustomHeader {
  name: string;
  value: string;
}

// Swagger/OpenAPI типы
export interface SwaggerSpec {
  swagger?: string;
  openapi?: string;
  info?: {
    title?: string;
    version?: string;
  };
  paths: Record<string, Record<string, SwaggerOperation>>;
  definitions?: Record<string, SwaggerSchema>;
  components?: {
    schemas?: Record<string, SwaggerSchema>;
  };
}

export interface SwaggerOperation {
  tags?: string[];
  summary?: string;
  description?: string;
  operationId?: string;
  parameters?: SwaggerParameter[];
  requestBody?: SwaggerRequestBody;
  responses?: Record<string, SwaggerResponse>;
}

export interface SwaggerParameter {
  name: string;
  in: 'path' | 'query' | 'header' | 'body';
  required?: boolean;
  schema?: SwaggerSchema;
  example?: any;
  default?: any;
}

export interface SwaggerRequestBody {
  description?: string;
  required?: boolean;
  content?: Record<string, {
    schema?: SwaggerSchema;
    example?: any;
  }>;
}

export interface SwaggerResponse {
  description?: string;
  content?: Record<string, {
    schema?: SwaggerSchema;
  }>;
}

export interface SwaggerSchema {
  type?: string;
  format?: string;
  title?: string;
  description?: string;
  properties?: Record<string, SwaggerSchema>;
  items?: SwaggerSchema;
  required?: string[];
  example?: any;
  $ref?: string;
}

// Дерево endpoints
export interface EndpointTag {
  name: string;
  endpoints: EndpointItem[];
}

export interface EndpointItem {
  method: string;
  path: string;
  op: SwaggerOperation;
}

// API Response
export interface ApiResponse<T = any> {
  status: 'ok' | 'fail';
  data?: T;
  errors?: {
    message: string;
  };
}

// Глобальные типы
declare global {
  interface Window {
    $: any;
    jQuery: any;
  }
}

// jQuery из Webasyst
declare const $: any;
