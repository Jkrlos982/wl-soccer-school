import ApiService from './api';
import { ApiResponse, PaginatedResponse } from '../types';
import {
  AccountReceivable,
  Payment,
  PaymentPlan,
  InstallmentPayment,
  CreateAccountReceivableData,
  UpdateAccountReceivableData,
  PaymentData,
  PaymentPlanData,
  ARFilters,
  PaymentFilters,
  CollectionReport
} from '../types/financial';

export class AccountsReceivableService {
  // Accounts Receivable Management
  static async getAccountsReceivable(filters?: ARFilters): Promise<PaginatedResponse<AccountReceivable>> {
    const response = await ApiService.get<PaginatedResponse<AccountReceivable>>('/accounts-receivable', {
      params: filters,
    });
    return response.data;
  }

  static async getAccountReceivable(arId: string): Promise<AccountReceivable> {
    const response = await ApiService.get<AccountReceivable>(`/accounts-receivable/${arId}`);
    return response.data;
  }

  static async createAccountReceivable(data: CreateAccountReceivableData): Promise<AccountReceivable> {
    const response = await ApiService.post<AccountReceivable>('/accounts-receivable', data);
    return response.data;
  }

  static async updateAccountReceivable(
    arId: string,
    data: UpdateAccountReceivableData
  ): Promise<AccountReceivable> {
    const response = await ApiService.put<AccountReceivable>(`/accounts-receivable/${arId}`, data);
    return response.data;
  }

  static async deleteAccountReceivable(arId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/accounts-receivable/${arId}`);
  }

  // Payment Management
  static async getPayments(filters?: PaymentFilters): Promise<PaginatedResponse<Payment>> {
    const response = await ApiService.get<PaginatedResponse<Payment>>('/accounts-receivable/payments', {
      params: filters,
    });
    return response.data;
  }

  static async getPayment(paymentId: string): Promise<Payment> {
    const response = await ApiService.get<Payment>(`/accounts-receivable/payments/${paymentId}`);
    return response.data;
  }

  static async registerPayment(arId: string, paymentData: PaymentData): Promise<Payment> {
    const response = await ApiService.post<Payment>(`/accounts-receivable/${arId}/payments`, paymentData);
    return response.data;
  }

  static async updatePayment(
    paymentId: string,
    data: Partial<PaymentData>
  ): Promise<Payment> {
    const response = await ApiService.put<Payment>(`/accounts-receivable/payments/${paymentId}`, data);
    return response.data;
  }

  static async deletePayment(paymentId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/accounts-receivable/payments/${paymentId}`);
  }

  // Payment Plan Management
  static async getPaymentPlans(arId?: string): Promise<PaymentPlan[]> {
    const endpoint = arId ? `/accounts-receivable/${arId}/payment-plans` : '/accounts-receivable/payment-plans';
    const response = await ApiService.get<PaymentPlan[]>(endpoint);
    return response.data;
  }

  static async getPaymentPlan(planId: string): Promise<PaymentPlan> {
    const response = await ApiService.get<PaymentPlan>(`/accounts-receivable/payment-plans/${planId}`);
    return response.data;
  }

  static async createPaymentPlan(arId: string, data: PaymentPlanData): Promise<PaymentPlan> {
    const response = await ApiService.post<PaymentPlan>(`/accounts-receivable/${arId}/payment-plans`, data);
    return response.data;
  }

  static async updatePaymentPlan(
    planId: string,
    data: Partial<PaymentPlanData>
  ): Promise<PaymentPlan> {
    const response = await ApiService.put<PaymentPlan>(`/accounts-receivable/payment-plans/${planId}`, data);
    return response.data;
  }

  static async cancelPaymentPlan(planId: string): Promise<ApiResponse> {
    return await ApiService.patch(`/accounts-receivable/payment-plans/${planId}/cancel`);
  }

  // Installment Payments
  static async getInstallmentPayments(planId: string): Promise<InstallmentPayment[]> {
    const response = await ApiService.get<InstallmentPayment[]>(`/accounts-receivable/payment-plans/${planId}/installments`);
    return response.data;
  }

  static async payInstallment(
    planId: string,
    installmentId: string,
    paymentData: PaymentData
  ): Promise<Payment> {
    const response = await ApiService.post<Payment>(
      `/accounts-receivable/payment-plans/${planId}/installments/${installmentId}/pay`,
      paymentData
    );
    return response.data;
  }

  // Student-specific methods
  static async getStudentAccountsReceivable(
    studentId: string,
    filters?: Omit<ARFilters, 'student_id'>
  ): Promise<PaginatedResponse<AccountReceivable>> {
    const response = await ApiService.get<PaginatedResponse<AccountReceivable>>(
      `/students/${studentId}/accounts-receivable`,
      { params: filters }
    );
    return response.data;
  }

  static async getStudentBalance(studentId: string): Promise<{
    total_receivables: number;
    total_balance: number;
    overdue_amount: number;
    paid_amount: number;
    currency: string;
  }> {
    const response = await ApiService.get(`/students/${studentId}/accounts-receivable/balance`);
    return response.data;
  }

  static async getStudentPaymentHistory(
    studentId: string,
    filters?: PaymentFilters
  ): Promise<PaginatedResponse<Payment>> {
    const response = await ApiService.get<PaginatedResponse<Payment>>(
      `/students/${studentId}/accounts-receivable/payments`,
      { params: filters }
    );
    return response.data;
  }

  // Reports and Analytics
  static async getCollectionReport(filters: {
    start_date?: string;
    end_date?: string;
    student_id?: string;
    concept_id?: string;
    status?: AccountReceivable['status'];
  }): Promise<CollectionReport> {
    const response = await ApiService.get<CollectionReport>('/accounts-receivable/reports/collection', {
      params: filters,
    });
    return response.data;
  }

  static async getOverdueAccounts(): Promise<AccountReceivable[]> {
    const response = await ApiService.get<AccountReceivable[]>('/accounts-receivable/overdue');
    return response.data;
  }

  static async getCollectionSummary(): Promise<{
    today: { count: number; amount: number };
    this_week: { count: number; amount: number };
    this_month: { count: number; amount: number };
    pending: { count: number; amount: number };
    overdue: { count: number; amount: number };
    total_balance: number;
  }> {
    const response = await ApiService.get('/accounts-receivable/summary');
    return response.data;
  }

  // Voucher and Receipt Management
  static async generateVoucher(paymentId: string): Promise<Blob> {
    const response = await ApiService.get(`/accounts-receivable/payments/${paymentId}/voucher`, {
      responseType: 'blob',
    });
    return response.data;
  }

  static async downloadVoucher(paymentId: string): Promise<Blob> {
    const response = await ApiService.get(`/accounts-receivable/payments/${paymentId}/voucher/download`, {
      responseType: 'blob',
    });
    return response.data;
  }

  static async generateStatement(
    studentId: string,
    params: {
      start_date?: string;
      end_date?: string;
      format?: 'pdf' | 'excel';
    }
  ): Promise<Blob> {
    const response = await ApiService.get(`/students/${studentId}/accounts-receivable/statement`, {
      params,
      responseType: 'blob',
    });
    return response.data;
  }

  // Bulk Operations
  static async bulkCreateAccountsReceivable(data: {
    accounts: CreateAccountReceivableData[];
    apply_to_students?: string[];
  }): Promise<ApiResponse> {
    return await ApiService.post('/accounts-receivable/bulk-create', data);
  }

  static async bulkRecordPayments(payments: {
    account_receivable_id: string;
    payment_data: PaymentData;
  }[]): Promise<ApiResponse> {
    return await ApiService.post('/accounts-receivable/payments/bulk-record', { payments });
  }

  static async bulkUpdateStatus(
    arIds: string[],
    status: AccountReceivable['status']
  ): Promise<ApiResponse> {
    return await ApiService.patch('/accounts-receivable/bulk-update-status', {
      account_receivable_ids: arIds,
      status,
    });
  }

  // Export Methods
  static async exportAccountsReceivable(filters?: ARFilters): Promise<Blob> {
    const response = await ApiService.get('/accounts-receivable/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  }

  static async exportPayments(filters?: PaymentFilters): Promise<Blob> {
    const response = await ApiService.get('/accounts-receivable/payments/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  }

  static async exportCollectionReport(params: {
    start_date: string;
    end_date: string;
    format: 'pdf' | 'excel' | 'csv';
    student_id?: string;
    concept_id?: string;
  }): Promise<Blob> {
    const response = await ApiService.get('/accounts-receivable/reports/collection/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  }

  // Notification and Communication
  static async sendPaymentReminder(arId: string, message?: string): Promise<ApiResponse> {
    return await ApiService.post(`/accounts-receivable/${arId}/send-reminder`, {
      message,
    });
  }

  static async sendBulkReminders(
    arIds: string[],
    message?: string
  ): Promise<ApiResponse> {
    return await ApiService.post('/accounts-receivable/send-bulk-reminders', {
      account_receivable_ids: arIds,
      message,
    });
  }

  // Chart Data for Dashboard
  static async getCollectionChartData(params: {
    type: 'collection_trend' | 'status_distribution' | 'overdue_analysis' | 'payment_methods';
    period: 'monthly' | 'quarterly' | 'yearly';
    start_date?: string;
    end_date?: string;
    student_id?: string;
  }): Promise<{ success: boolean; data: any[] }> {
    const response = await ApiService.get('/accounts-receivable/reports/chart-data', {
      params,
    });
    return response.data;
  }

  static async getCollectionTrendData(params: {
    period: 'monthly' | 'quarterly' | 'yearly';
    start_date?: string;
    end_date?: string;
  }): Promise<{ name: string; collected: number; pending: number; overdue: number }[]> {
    const response = await this.getCollectionChartData({
      ...params,
      type: 'collection_trend',
    });
    return response.data;
  }

  static async getStatusDistributionData(): Promise<{ name: string; value: number; color: string }[]> {
    const response = await this.getCollectionChartData({
      type: 'status_distribution',
      period: 'monthly',
    });
    return response.data;
  }

  static async getOverdueAnalysisData(): Promise<{
    ranges: { range: string; count: number; amount: number }[];
    trends: { month: string; overdue_count: number; overdue_amount: number }[];
  }> {
    const response = await this.getCollectionChartData({
      type: 'overdue_analysis',
      period: 'monthly',
    });
    return response.data as unknown as {
      ranges: { range: string; count: number; amount: number }[];
      trends: { month: string; overdue_count: number; overdue_amount: number }[];
    };
  }

  static async getPaymentMethodsData(): Promise<{ name: string; value: number }[]> {
    const response = await this.getCollectionChartData({
      type: 'payment_methods',
      period: 'monthly',
    });
    return response.data;
  }
}

export default AccountsReceivableService;