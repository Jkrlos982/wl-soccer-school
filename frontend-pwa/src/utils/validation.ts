import * as yup from 'yup';

// Common validation patterns
export const validationPatterns = {
  // Account number pattern (uppercase letters, numbers, hyphens)
  accountNumber: /^[A-Z0-9-]*$/,
  // Code pattern (uppercase letters, numbers, underscores, hyphens)
  code: /^[A-Z0-9_-]*$/,
  // Date pattern (YYYY-MM-DD)
  date: /^\d{4}-\d{2}-\d{2}$/,
  // Decimal number with up to 2 decimal places
  decimal: /^\d+(\.\d{1,2})?$/,
};

// Common validation messages
export const validationMessages = {
  required: (field: string) => `${field} is required`,
  minLength: (field: string, min: number) => `${field} must be at least ${min} characters`,
  maxLength: (field: string, max: number) => `${field} must not exceed ${max} characters`,
  positive: (field: string) => `${field} must be positive`,
  nonNegative: (field: string) => `${field} must be non-negative`,
  invalidFormat: (field: string) => `${field} has invalid format`,
  invalidOption: (field: string) => `Invalid ${field}`,
  tooLarge: (field: string) => `${field} is too large`,
  tooSmall: (field: string) => `${field} is too small`,
  dateInFuture: 'Date cannot be in the future',
  dateInPast: 'Date cannot be in the past',
  balanceRequired: 'Debits and credits must be balanced',
};

// Spanish validation messages
export const validationMessagesEs = {
  required: (field: string) => `${field} es requerido`,
  minLength: (field: string, min: number) => `${field} debe tener al menos ${min} caracteres`,
  maxLength: (field: string, max: number) => `${field} no puede exceder ${max} caracteres`,
  positive: (field: string) => `${field} debe ser positivo`,
  nonNegative: (field: string) => `${field} debe ser no negativo`,
  invalidFormat: (field: string) => `${field} tiene formato inválido`,
  invalidOption: (field: string) => `${field} inválido`,
  tooLarge: (field: string) => `${field} es demasiado grande`,
  tooSmall: (field: string) => `${field} es demasiado pequeño`,
  dateInFuture: 'La fecha no puede ser futura',
  dateInPast: 'La fecha no puede ser pasada',
  balanceRequired: 'Los débitos y créditos deben estar balanceados',
};

// Common field validators
export const validators = {
  // Required string with min/max length
  requiredString: (min = 1, max = 255) => 
    yup.string()
      .required(validationMessages.required('Field'))
      .min(min, validationMessages.minLength('Field', min))
      .max(max, validationMessages.maxLength('Field', max)),

  // Optional string with max length
  optionalString: (max = 500) => 
    yup.string()
      .optional()
      .max(max, validationMessages.maxLength('Field', max)),

  // Required positive number
  requiredPositiveNumber: (max = 999999999.99) => 
    yup.number()
      .required(validationMessages.required('Amount'))
      .positive(validationMessages.positive('Amount'))
      .max(max, validationMessages.tooLarge('Amount')),

  // Optional number with range
  optionalNumber: (min = -999999999.99, max = 999999999.99) => 
    yup.number()
      .optional()
      .min(min, validationMessages.tooSmall('Value'))
      .max(max, validationMessages.tooLarge('Value')),

  // Required date
  requiredDate: () => 
    yup.date()
      .required(validationMessages.required('Date')),

  // Date not in future
  pastDate: () => 
    yup.date()
      .required(validationMessages.required('Date'))
      .max(new Date(), validationMessages.dateInFuture),

  // Account number format
  accountNumber: () => 
    yup.string()
      .optional()
      .matches(validationPatterns.accountNumber, validationMessages.invalidFormat('Account number'))
      .max(50, validationMessages.maxLength('Account number', 50)),

  // Code format
  code: () => 
    yup.string()
      .optional()
      .matches(validationPatterns.code, validationMessages.invalidFormat('Code'))
      .max(20, validationMessages.maxLength('Code', 20)),
};

// Form validation helpers
export const formHelpers = {
  // Get error message from formik errors
  getErrorMessage: (errors: any, touched: any, field: string): string | undefined => {
    const fieldError = getNestedValue(errors, field);
    const fieldTouched = getNestedValue(touched, field);
    return fieldTouched && fieldError ? fieldError : undefined;
  },

  // Check if field has error
  hasError: (errors: any, touched: any, field: string): boolean => {
    const fieldError = getNestedValue(errors, field);
    const fieldTouched = getNestedValue(touched, field);
    return Boolean(fieldTouched && fieldError);
  },

  // Format currency for display
  formatCurrency: (amount: number, currency = 'COP'): string => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(amount);
  },

  // Parse currency input
  parseCurrency: (value: string): number => {
    // Remove currency symbols and spaces, replace comma with dot
    const cleaned = value.replace(/[^\d,.-]/g, '').replace(',', '.');
    return parseFloat(cleaned) || 0;
  },

  // Validate balanced accounts
  validateBalancedAccounts: (accounts: Array<{ type: 'debit' | 'credit'; amount: number }>): boolean => {
    if (!accounts || accounts.length === 0) return false;
    
    const totalDebits = accounts
      .filter(acc => acc.type === 'debit')
      .reduce((sum, acc) => sum + (acc.amount || 0), 0);
    
    const totalCredits = accounts
      .filter(acc => acc.type === 'credit')
      .reduce((sum, acc) => sum + (acc.amount || 0), 0);
    
    return Math.abs(totalDebits - totalCredits) < 0.01;
  },
};

// Helper function to get nested object values
function getNestedValue(obj: any, path: string): any {
  return path.split('.').reduce((current, key) => {
    return current && current[key] !== undefined ? current[key] : undefined;
  }, obj);
}

// Export validation schema builders for common patterns
export const schemaBuilders = {
  // Build transaction account validation
  transactionAccount: () => yup.object({
    account_id: yup.string().required(validationMessages.required('Account')),
    type: yup.string()
      .required(validationMessages.required('Account type'))
      .oneOf(['debit', 'credit'], validationMessages.invalidOption('account type')),
    amount: validators.requiredPositiveNumber(),
  }),

  // Build pagination validation
  pagination: () => yup.object({
    page: yup.number()
      .optional()
      .min(1, 'Page must be at least 1'),
    per_page: yup.number()
      .optional()
      .min(1, 'Per page must be at least 1')
      .max(100, 'Per page must not exceed 100'),
  }),

  // Build date range validation
  dateRange: () => yup.object({
    date_from: yup.string()
      .optional()
      .matches(validationPatterns.date, validationMessages.invalidFormat('Start date')),
    date_to: yup.string()
      .optional()
      .matches(validationPatterns.date, validationMessages.invalidFormat('End date'))
      .test(
        'date-range-validation',
        'End date must be after start date',
        function (value) {
          const { date_from } = this.parent;
          if (date_from && value) {
            return new Date(value) >= new Date(date_from);
          }
          return true;
        }
      ),
  }),
};