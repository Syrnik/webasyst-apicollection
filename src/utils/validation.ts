import type { Collection, Environment, CustomHeader } from '@/types';

/**
 * Валидация форм
 */

export interface ValidationErrors {
  [key: string]: string;
}

/**
 * Валидирует URL
 */
export function validateUrl(url: string): boolean {
  if (!url) return false;
  return /^https?:\/\//i.test(url);
}

/**
 * Валидирует коллекцию
 */
export function validateCollection(
  data: Partial<Collection>
): ValidationErrors {
  const errors: ValidationErrors = {};
  
  if (!data.title?.trim()) {
    errors.title = 'Обязательное поле';
  }
  
  if (data.spec_source === 'url') {
    if (!data.spec_url?.trim()) {
      errors.spec_url = 'Обязательное поле';
    } else if (!validateUrl(data.spec_url)) {
      errors.spec_url = 'Должен начинаться с http:// или https://';
    }
  } else if (data.spec_source === 'file') {
    if (!data.spec_file) {
      errors.spec_file = 'Файл не загружен';
    }
  }
  
  return errors;
}

/**
 * Валидирует окружение
 */
export function validateEnvironment(
  data: Partial<Environment>
): ValidationErrors {
  const errors: ValidationErrors = {};
  
  if (!data.name?.trim()) {
    errors.name = 'Обязательное поле';
  }
  
  if (data.base_url && !validateUrl(data.base_url)) {
    errors.base_url = 'Должен начинаться с http:// или https://';
  }
  
  return errors;
}

/**
 * Валидирует произвольные заголовки
 */
export function validateCustomHeaders(
  headers: CustomHeader[]
): ValidationErrors {
  const errors: ValidationErrors = {};
  
  headers.forEach((header, index) => {
    if (!header.name.trim()) {
      errors[`header_${index}_name`] = 'Имя заголовка обязательно';
    }
    if (!header.value.trim()) {
      errors[`header_${index}_value`] = 'Значение заголовка обязательно';
    }
  });
  
  return errors;
}

/**
 * Проверяет, есть ли ошибки валидац��и
 */
export function hasErrors(errors: ValidationErrors): boolean {
  return Object.keys(errors).length > 0;
}
