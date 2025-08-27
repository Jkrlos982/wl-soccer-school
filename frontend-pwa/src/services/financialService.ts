import ApiService from './api';
import { ApiResponse, PaginatedResponse } from '../types';

// Financial types
export interface Fee {
  id: string;
  name: string;
  description?: string;
  amount: number;
  currency: string;
  type: 'tuition' | 'transport' | 'meal' | 'activity' | 'library' | 'exam' | 'other';
  frequency: 'one_time' | 'monthly' | 'quarterly' | 'semester' | 'annual';
  due_date: string;
  late_fee_amount?: number;
  late_fee_days?: number;
  applicable_grades: string[];
  status: 'active' | 'inactive';
  created_at: string;
  updated_at: string;
}

export interface Payment {
  id: string;
  student_id: string;
  student_name: string;
  fee_id: string;
  fee_name: string;
  amount: number;
  currency: string;
  payment_method: 'cash' | 'bank_transfer' | 'card' | 'mobile_money' | 'cheque';
  transaction_id?: string;
  reference_number: string;
  status: 'pending' | 'completed' | 'failed' | 'refunded' | 'cancelled';
  paid_at?: string;
  due_date: string;
  late_fee?: number;
  discount?: number;
  notes?: string;
  receipt_url?: string;
  created_at: string;
  updated_at: string;
}

export interface Invoice {
  id: string;
  invoice_number: string;
  student_id: string;
  student_name: string;
  items: InvoiceItem[];
  subtotal: number;
  discount: number;
  tax: number;
  total: number;
  currency: string;
  due_date: string;
  status: 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled';
  issued_at: string;
  paid_at?: string;
  notes?: string;
  created_at: string;
  updated_at: string;
}

export interface InvoiceItem {
  id: string;
  fee_id: string;
  fee_name: string;
  description?: string;
  quantity: number;
  unit_price: number;
  total: number;
}

export interface FinancialReport {
  period: {
    start_date: string;
    end_date: string;
  };
  summary: {
    total_revenue: number;
    total_payments: number;
    pending_payments: number;
    overdue_payments: number;
    refunds: number;
  };
  by_fee_type: {
    [key: string]: {
      collected: number;
      pending: number;
      overdue: number;
    };
  };
  by_payment_method: {
    [key: string]: number;
  };
  monthly_trends: {
    month: string;
    revenue: number;
    payments_count: number;
  }[];
}

export interface CreateFeeData {
  name: string;
  description?: string;
  amount: number;
  type: Fee['type'];
  frequency: Fee['frequency'];
  due_date: string;
  late_fee_amount?: number;
  late_fee_days?: number;
  applicable_grades: string[];
}

export interface CreatePaymentData {
  student_id: string;
  fee_id: string;
  amount: number;
  payment_method: Payment['payment_method'];
  transaction_id?: string;
  notes?: string;
}

export interface PaymentFilters {
  page?: number;
  per_page?: number;
  student_id?: string;
  fee_id?: string;
  status?: Payment['status'];
  payment_method?: Payment['payment_method'];
  date_from?: string;
  date_to?: string;
  search?: string;
}

export class FinancialService {
  // Fee Management
  static async getFees(params?: {
    page?: number;
    per_page?: number;
    type?: string;
    status?: string;
    search?: string;
  }): Promise<PaginatedResponse<Fee>> {
    const response = await ApiService.get<PaginatedResponse<Fee>>('/fees', {
      params,
    });
    return response.data;
  }

  static async getFee(feeId: string): Promise<Fee> {
    const response = await ApiService.get<Fee>(`/fees/${feeId}`);
    return response.data;
  }

  static async createFee(data: CreateFeeData): Promise<Fee> {
    const response = await ApiService.post<Fee>('/fees', data);
    return response.data;
  }

  static async updateFee(feeId: string, data: Partial<CreateFeeData>): Promise<Fee> {
    const response = await ApiService.put<Fee>(`/fees/${feeId}`, data);
    return response.data;
  }

  static async deleteFee(feeId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/fees/${feeId}`);
  }

  // Payment Management
  static async getPayments(filters?: PaymentFilters): Promise<PaginatedResponse<Payment>> {
    const response = await ApiService.get<PaginatedResponse<Payment>>('/payments', {
      params: filters,
    });
    return response.data;
  }

  static async getPayment(paymentId: string): Promise<Payment> {
    const response = await ApiService.get<Payment>(`/payments/${paymentId}`);
    return response.data;
  }

  static async createPayment(data: CreatePaymentData): Promise<Payment> {
    const response = await ApiService.post<Payment>('/payments', data);
    return response.data;
  }

  static async updatePayment(
    paymentId: string,
    data: Partial<CreatePaymentData>
  ): Promise<Payment> {
    const response = await ApiService.put<Payment>(`/payments/${paymentId}`, data);
    return response.data;
  }

  static async cancelPayment(paymentId: string): Promise<ApiResponse> {
    return await ApiService.patch(`/payments/${paymentId}/cancel`);
  }

  static async refundPayment(
    paymentId: string,
    amount?: number,
    reason?: string
  ): Promise<ApiResponse> {
    return await ApiService.post(`/payments/${paymentId}/refund`, {
      amount,
      reason,
    });
  }

  // Student Financial Records
  static async getStudentPayments(
    studentId: string,
    params?: {
      page?: number;
      per_page?: number;
      status?: string;
      date_from?: string;
      date_to?: string;
    }
  ): Promise<PaginatedResponse<Payment>> {
    const response = await ApiService.get<PaginatedResponse<Payment>>(
      `/students/${studentId}/payments`,
      { params }
    );
    return response.data;
  }

  static async getStudentBalance(studentId: string): Promise<{
    total_fees: number;
    total_paid: number;
    balance: number;
    overdue_amount: number;
    currency: string;
  }> {
    const response = await ApiService.get(`/students/${studentId}/balance`);
    return response.data;
  }

  static async getStudentInvoices(
    studentId: string,
    params?: {
      page?: number;
      per_page?: number;
      status?: string;
    }
  ): Promise<PaginatedResponse<Invoice>> {
    const response = await ApiService.get<PaginatedResponse<Invoice>>(
      `/students/${studentId}/invoices`,
      { params }
    );
    return response.data;
  }

  // Invoice Management
  static async getInvoices(params?: {
    page?: number;
    per_page?: number;
    status?: string;
    student_id?: string;
    date_from?: string;
    date_to?: string;
  }): Promise<PaginatedResponse<Invoice>> {
    const response = await ApiService.get<PaginatedResponse<Invoice>>('/invoices', {
      params,
    });
    return response.data;
  }

  static async getInvoice(invoiceId: string): Promise<Invoice> {
    const response = await ApiService.get<Invoice>(`/invoices/${invoiceId}`);
    return response.data;
  }

  static async createInvoice(data: {
    student_id: string;
    items: Omit<InvoiceItem, 'id' | 'total'>[];
    due_date: string;
    discount?: number;
    tax?: number;
    notes?: string;
  }): Promise<Invoice> {
    const response = await ApiService.post<Invoice>('/invoices', data);
    return response.data;
  }

  static async sendInvoice(invoiceId: string): Promise<ApiResponse> {
    return await ApiService.post(`/invoices/${invoiceId}/send`);
  }

  static async markInvoiceAsPaid(
    invoiceId: string,
    paymentData: {
      payment_method: Payment['payment_method'];
      transaction_id?: string;
      paid_at?: string;
    }
  ): Promise<ApiResponse> {
    return await ApiService.post(`/invoices/${invoiceId}/mark-paid`, paymentData);
  }

  // Reports and Analytics
  static async getFinancialReport(params: {
    start_date: string;
    end_date: string;
    group_by?: 'day' | 'week' | 'month';
  }): Promise<FinancialReport> {
    const response = await ApiService.get<FinancialReport>('/reports/financial', {
      params,
    });
    return response.data;
  }

  static async getOverduePayments(): Promise<Payment[]> {
    const response = await ApiService.get<Payment[]>('/payments/overdue');
    return response.data;
  }

  static async getPaymentSummary(): Promise<{
    today: { count: number; amount: number };
    this_week: { count: number; amount: number };
    this_month: { count: number; amount: number };
    pending: { count: number; amount: number };
    overdue: { count: number; amount: number };
  }> {
    const response = await ApiService.get('/payments/summary');
    return response.data;
  }

  // Bulk Operations
  static async bulkCreateFees(data: {
    fees: CreateFeeData[];
    apply_to_existing_students?: boolean;
  }): Promise<ApiResponse> {
    return await ApiService.post('/fees/bulk-create', data);
  }

  static async bulkRecordPayments(payments: CreatePaymentData[]): Promise<ApiResponse> {
    return await ApiService.post('/payments/bulk-record', { payments });
  }

  // Export and Import
  static async exportPayments(filters?: PaymentFilters): Promise<Blob> {
    const response = await ApiService.get('/payments/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  }

  static async exportFinancialReport(params: {
    start_date: string;
    end_date: string;
    format: 'pdf' | 'excel';
  }): Promise<Blob> {
    const response = await ApiService.get('/reports/financial/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  }

  // Receipt Management
  static async generateReceipt(paymentId: string): Promise<{ receipt_url: string }> {
    const response = await ApiService.post(`/payments/${paymentId}/receipt`);
    return response.data;
  }

  static async downloadReceipt(paymentId: string): Promise<Blob> {
    const response = await ApiService.get(`/payments/${paymentId}/receipt/download`, {
      responseType: 'blob',
    });
    return response.data;
  }
}

export default FinancialService;