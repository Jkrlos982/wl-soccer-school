import * as yup from 'yup';
import { Transaction } from '../types/financial';

// Transaction validation schema
export const transactionSchema = yup.object({
  financial_concept_id: yup
    .string()
    .required('Financial concept is required'),
  
  description: yup
    .string()
    .required('Description is required')
    .min(3, 'Description must be at least 3 characters')
    .max(500, 'Description must not exceed 500 characters'),
  
  amount: yup
    .number()
    .required('Amount is required')
    .positive('Amount must be positive')
    .max(999999999.99, 'Amount is too large'),
  
  transaction_date: yup
    .string()
    .required('Transaction date is required')
    .matches(
      /^\d{4}-\d{2}-\d{2}$/,
      'Transaction date must be in YYYY-MM-DD format'
    ),
  
  payment_method: yup
    .string()
    .required('Payment method is required')
    .oneOf(
      ['cash', 'bank_transfer', 'card', 'mobile_money', 'cheque', 'other'],
      'Invalid payment method'
    ) as yup.Schema<Transaction['payment_method']>,
  
  accounts: yup
    .array()
    .of(
      yup.object({
        account_id: yup
          .string()
          .required('Account is required'),
        
        type: yup
          .string()
          .required('Account type is required')
          .oneOf(['debit', 'credit'], 'Account type must be debit or credit'),
        
        amount: yup
          .number()
          .required('Account amount is required')
          .positive('Account amount must be positive')
      })
    )
    .min(2, 'At least 2 accounts are required (debit and credit)')
    .required('Accounts are required')
    .test(
      'balanced-accounts',
      'Total debits must equal total credits',
      function (accounts) {
        if (!accounts || accounts.length === 0) return false;
        
        const totalDebits = accounts
          .filter(acc => acc.type === 'debit')
          .reduce((sum, acc) => sum + (acc.amount || 0), 0);
        
        const totalCredits = accounts
          .filter(acc => acc.type === 'credit')
          .reduce((sum, acc) => sum + (acc.amount || 0), 0);
        
        return Math.abs(totalDebits - totalCredits) < 0.01; // Allow for small rounding differences
      }
    ),
  
  metadata: yup
    .object()
    .optional()
    .nullable()
});

// Financial concept validation schema
export const conceptSchema = yup.object({
  name: yup
    .string()
    .required('Name is required')
    .min(2, 'Name must be at least 2 characters')
    .max(100, 'Name must not exceed 100 characters'),
  
  description: yup
    .string()
    .optional()
    .max(500, 'Description must not exceed 500 characters'),
  
  type: yup
    .string()
    .required('Type is required')
    .oneOf(['income', 'expense'], 'Type must be income or expense'),
  
  category: yup
    .string()
    .required('Category is required')
    .min(2, 'Category must be at least 2 characters')
    .max(50, 'Category must not exceed 50 characters'),
  
  code: yup
    .string()
    .optional()
    .matches(
      /^[A-Z0-9_-]*$/,
      'Code can only contain uppercase letters, numbers, underscores, and hyphens'
    )
    .max(20, 'Code must not exceed 20 characters')
});

// Account validation schema
export const accountSchema = yup.object({
  name: yup
    .string()
    .required('Name is required')
    .min(2, 'Name must be at least 2 characters')
    .max(100, 'Name must not exceed 100 characters'),
  
  account_number: yup
    .string()
    .optional()
    .matches(
      /^[A-Z0-9-]*$/,
      'Account number can only contain uppercase letters, numbers, and hyphens'
    )
    .max(50, 'Account number must not exceed 50 characters'),
  
  type: yup
    .string()
    .required('Type is required')
    .oneOf(
      ['asset', 'liability', 'equity', 'income', 'expense'],
      'Invalid account type'
    ),
  
  description: yup
    .string()
    .optional()
    .max(500, 'Description must not exceed 500 characters'),
  
  balance: yup
    .number()
    .optional()
    .min(-999999999.99, 'Balance is too low')
    .max(999999999.99, 'Balance is too high')
});

// Transaction filter validation schema
export const transactionFilterSchema = yup.object({
  search: yup
    .string()
    .optional()
    .max(100, 'Search term must not exceed 100 characters'),
  
  status: yup
    .string()
    .optional()
    .oneOf(
      ['pending', 'approved', 'rejected', 'cancelled', 'completed'],
      'Invalid status'
    ),
  
  type: yup
    .string()
    .optional()
    .oneOf(['income', 'expense'], 'Type must be income or expense'),
  
  concept_id: yup
    .string()
    .optional(),
  
  account_id: yup
    .string()
    .optional(),
  
  payment_method: yup
    .string()
    .optional()
    .oneOf(
      ['cash', 'bank_transfer', 'card', 'mobile_money', 'cheque', 'other'],
      'Invalid payment method'
    ),
  
  amount_min: yup
    .number()
    .optional()
    .min(0, 'Minimum amount must be non-negative'),
  
  amount_max: yup
    .number()
    .optional()
    .min(0, 'Maximum amount must be non-negative')
    .test(
      'min-max-validation',
      'Maximum amount must be greater than minimum amount',
      function (value) {
        const { amount_min } = this.parent;
        if (amount_min && value) {
          return value >= amount_min;
        }
        return true;
      }
    ),
  
  date_from: yup
    .string()
    .optional()
    .matches(
      /^\d{4}-\d{2}-\d{2}$/,
      'Date from must be in YYYY-MM-DD format'
    ),
  
  date_to: yup
    .string()
    .optional()
    .matches(
      /^\d{4}-\d{2}-\d{2}$/,
      'Date to must be in YYYY-MM-DD format'
    )
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
  
  created_by: yup
    .string()
    .optional(),
  
  approved_by: yup
    .string()
    .optional(),
  
  sort_by: yup
    .string()
    .optional()
    .oneOf(
      ['created_at', 'transaction_date', 'amount', 'reference_number'],
      'Invalid sort field'
    ),
  
  sort_order: yup
    .string()
    .optional()
    .oneOf(['asc', 'desc'], 'Sort order must be asc or desc'),
  
  page: yup
    .number()
    .optional()
    .min(1, 'Page must be at least 1'),
  
  per_page: yup
    .number()
    .optional()
    .min(1, 'Per page must be at least 1')
    .max(100, 'Per page must not exceed 100')
});

// Transaction approval schema
export const transactionApprovalSchema = yup.object({
  approval_notes: yup
    .string()
    .optional()
    .max(1000, 'Approval notes must not exceed 1000 characters')
});

// Bulk transaction operations schema
export const bulkTransactionSchema = yup.object({
  transaction_ids: yup
    .array()
    .of(yup.string().required())
    .min(1, 'At least one transaction must be selected')
    .max(50, 'Cannot process more than 50 transactions at once')
    .required('Transaction IDs are required'),
  
  action: yup
    .string()
    .required('Action is required')
    .oneOf(
      ['approve', 'reject', 'cancel', 'delete'],
      'Invalid bulk action'
    ),
  
  notes: yup
    .string()
    .optional()
    .max(1000, 'Notes must not exceed 1000 characters')
});

// Export type definitions for TypeScript
export type TransactionFormData = yup.InferType<typeof transactionSchema>;
export type ConceptFormData = yup.InferType<typeof conceptSchema>;
export type AccountFormData = yup.InferType<typeof accountSchema>;
export type TransactionFilterData = yup.InferType<typeof transactionFilterSchema>;
export type TransactionApprovalData = yup.InferType<typeof transactionApprovalSchema>;
export type BulkTransactionData = yup.InferType<typeof bulkTransactionSchema>;