import ApiService from './api';
import {
  ApiResponse,
  PaginatedResponse,
  Transaction,
  FinancialConcept,
  Account,
  CreateTransactionData,
  UpdateTransactionData,
  CreateFinancialConceptData,
  UpdateFinancialConceptData,
  CreateAccountData,
  UpdateAccountData,
  TransactionFilters,
  ConceptFilters,
  AccountFilters,
  TransactionStatistics,
  FinancialDashboardData,
} from '../types';

/**
 * Transaction-based Financial Service
 * Handles the new transaction system with concepts and accounts
 */
export class TransactionService {
  // Transaction Management
  static async getTransactions(filters?: TransactionFilters): Promise<PaginatedResponse<Transaction>> {
    const response = await ApiService.get<PaginatedResponse<Transaction>>('/transactions', {
      params: filters,
    });
    return response.data;
  }

  static async getTransaction(transactionId: string): Promise<Transaction> {
    const response = await ApiService.get<Transaction>(`/transactions/${transactionId}`);
    return response.data;
  }

  static async createTransaction(data: CreateTransactionData): Promise<Transaction> {
    const response = await ApiService.post<Transaction>('/transactions', data);
    return response.data;
  }

  static async updateTransaction(
    transactionId: string,
    data: UpdateTransactionData
  ): Promise<Transaction> {
    const response = await ApiService.put<Transaction>(`/transactions/${transactionId}`, data);
    return response.data;
  }

  static async deleteTransaction(transactionId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/transactions/${transactionId}`);
  }

  // Transaction Status Management
  static async approveTransaction(
    transactionId: string,
    notes?: string
  ): Promise<Transaction> {
    const response = await ApiService.patch<Transaction>(
      `/transactions/${transactionId}/approve`,
      { approval_notes: notes }
    );
    return response.data;
  }

  static async rejectTransaction(
    transactionId: string,
    notes?: string
  ): Promise<Transaction> {
    const response = await ApiService.patch<Transaction>(
      `/transactions/${transactionId}/reject`,
      { approval_notes: notes }
    );
    return response.data;
  }

  static async cancelTransaction(
    transactionId: string,
    reason?: string
  ): Promise<Transaction> {
    const response = await ApiService.patch<Transaction>(
      `/transactions/${transactionId}/cancel`,
      { reason }
    );
    return response.data;
  }

  static async completeTransaction(transactionId: string): Promise<Transaction> {
    const response = await ApiService.patch<Transaction>(
      `/transactions/${transactionId}/complete`
    );
    return response.data;
  }

  // Financial Concept Management
  static async getConcepts(filters?: ConceptFilters): Promise<PaginatedResponse<FinancialConcept>> {
    const response = await ApiService.get<PaginatedResponse<FinancialConcept>>('/financial-concepts', {
      params: filters,
    });
    return response.data;
  }

  static async getAllConcepts(): Promise<FinancialConcept[]> {
    const response = await ApiService.get<FinancialConcept[]>('/financial-concepts/all');
    return response.data;
  }

  static async getConcept(conceptId: string): Promise<FinancialConcept> {
    const response = await ApiService.get<FinancialConcept>(`/financial-concepts/${conceptId}`);
    return response.data;
  }

  static async createConcept(data: CreateFinancialConceptData): Promise<FinancialConcept> {
    const response = await ApiService.post<FinancialConcept>('/financial-concepts', data);
    return response.data;
  }

  static async updateConcept(
    conceptId: string,
    data: UpdateFinancialConceptData
  ): Promise<FinancialConcept> {
    const response = await ApiService.put<FinancialConcept>(
      `/financial-concepts/${conceptId}`,
      data
    );
    return response.data;
  }

  static async deleteConcept(conceptId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/financial-concepts/${conceptId}`);
  }

  // Account Management
  static async getAccounts(filters?: AccountFilters): Promise<PaginatedResponse<Account>> {
    const response = await ApiService.get<PaginatedResponse<Account>>('/accounts', {
      params: filters,
    });
    return response.data;
  }

  static async getAllAccounts(): Promise<Account[]> {
    const response = await ApiService.get<Account[]>('/accounts/all');
    return response.data;
  }

  static async getAccount(accountId: string): Promise<Account> {
    const response = await ApiService.get<Account>(`/accounts/${accountId}`);
    return response.data;
  }

  static async createAccount(data: CreateAccountData): Promise<Account> {
    const response = await ApiService.post<Account>('/accounts', data);
    return response.data;
  }

  static async updateAccount(
    accountId: string,
    data: UpdateAccountData
  ): Promise<Account> {
    const response = await ApiService.put<Account>(`/accounts/${accountId}`, data);
    return response.data;
  }

  static async deleteAccount(accountId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/accounts/${accountId}`);
  }

  // Statistics and Reports
  static async getTransactionStatistics(params?: {
    date_from?: string;
    date_to?: string;
    concept_id?: string;
    account_id?: string;
  }): Promise<TransactionStatistics> {
    const response = await ApiService.get<TransactionStatistics>('/transactions/statistics', {
      params,
    });
    return response.data;
  }

  static async getDashboardData(): Promise<FinancialDashboardData> {
    const response = await ApiService.get<FinancialDashboardData>('/dashboard/financial');
    return response.data;
  }

  static async getPendingApprovals(): Promise<Transaction[]> {
    const response = await ApiService.get<Transaction[]>('/transactions/pending-approvals');
    return response.data;
  }

  static async getRecentTransactions(limit: number = 10): Promise<Transaction[]> {
    const response = await ApiService.get<Transaction[]>('/transactions/recent', {
      params: { limit },
    });
    return response.data;
  }

  // Reference Number Generation
  static async generateReferenceNumber(type: 'income' | 'expense'): Promise<{ reference_number: string }> {
    const response = await ApiService.post<{ reference_number: string }>(
      '/transactions/generate-reference',
      { type }
    );
    return response.data;
  }

  // Bulk Operations
  static async bulkApproveTransactions(
    transactionIds: string[],
    notes?: string
  ): Promise<ApiResponse> {
    return await ApiService.post('/transactions/bulk-approve', {
      transaction_ids: transactionIds,
      approval_notes: notes,
    });
  }

  static async bulkRejectTransactions(
    transactionIds: string[],
    notes?: string
  ): Promise<ApiResponse> {
    return await ApiService.post('/transactions/bulk-reject', {
      transaction_ids: transactionIds,
      approval_notes: notes,
    });
  }

  // Export Functions
  static async exportTransactions(filters?: TransactionFilters): Promise<Blob> {
    const response = await ApiService.get('/transactions/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  }

  static async exportStatistics(params?: {
    date_from?: string;
    date_to?: string;
    format?: 'pdf' | 'excel';
  }): Promise<Blob> {
    const response = await ApiService.get('/transactions/statistics/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  }

  // Validation Helpers
  static async validateTransaction(data: CreateTransactionData): Promise<{
    valid: boolean;
    errors: string[];
    warnings: string[];
  }> {
    const response = await ApiService.post('/transactions/validate', data);
    return response.data;
  }

  static async checkAccountBalance(accountId: string): Promise<{
    current_balance: number;
    available_balance: number;
    pending_transactions: number;
  }> {
    const response = await ApiService.get(`/accounts/${accountId}/balance`);
    return response.data;
  }

  // Search and Filters
  static async searchTransactions(query: string, filters?: Partial<TransactionFilters>): Promise<Transaction[]> {
    const response = await ApiService.get<Transaction[]>('/transactions/search', {
      params: { q: query, ...filters },
    });
    return response.data;
  }

  static async getFilterOptions(): Promise<{
    concepts: { id: string; name: string; type: string }[];
    accounts: { id: string; name: string; type: string }[];
    payment_methods: string[];
    statuses: string[];
  }> {
    const response = await ApiService.get('/transactions/filter-options');
    return response.data;
  }
}

export default TransactionService;