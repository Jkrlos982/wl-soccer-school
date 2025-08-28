// Financial module types based on backend API

// Transaction types
export interface Transaction {
  id: string;
  school_id: string;
  financial_concept_id: string;
  reference_number: string;
  description: string;
  amount: number;
  transaction_date: string;
  status: 'pending' | 'approved' | 'rejected' | 'cancelled' | 'completed';
  payment_method: 'cash' | 'bank_transfer' | 'card' | 'mobile_money' | 'cheque' | 'other';
  metadata?: Record<string, any>;
  created_by: string;
  approved_by?: string;
  approved_at?: string;
  approval_notes?: string;
  created_at: string;
  updated_at: string;
  
  // Relations
  financial_concept?: FinancialConcept;
  accounts?: TransactionAccount[];
  created_by_user?: {
    id: string;
    name: string;
    email: string;
  };
  approved_by_user?: {
    id: string;
    name: string;
    email: string;
  };
}

export interface TransactionAccount {
  id: string;
  transaction_id: string;
  account_id: string;
  type: 'debit' | 'credit';
  amount: number;
  created_at: string;
  updated_at: string;
  
  // Relations
  account?: Account;
}

// Financial Concept types
export interface FinancialConcept {
  id: string;
  school_id: string;
  name: string;
  description?: string;
  type: 'income' | 'expense';
  category: string;
  code?: string;
  is_active: boolean;
  created_by: string;
  created_at: string;
  updated_at: string;
}

// Account types
export interface Account {
  id: string;
  school_id: string;
  name: string;
  account_number?: string;
  type: 'asset' | 'liability' | 'equity' | 'income' | 'expense';
  balance: number;
  description?: string;
  is_active: boolean;
  created_by: string;
  created_at: string;
  updated_at: string;
}

// Create/Update types
export interface CreateTransactionData {
  financial_concept_id: string;
  description: string;
  amount: number;
  transaction_date: string;
  payment_method: Transaction['payment_method'];
  metadata?: Record<string, any>;
  accounts: {
    account_id: string;
    type: 'debit' | 'credit';
    amount: number;
  }[];
}

export interface UpdateTransactionData {
  financial_concept_id?: string;
  description?: string;
  amount?: number;
  transaction_date?: string;
  payment_method?: Transaction['payment_method'];
  metadata?: Record<string, any>;
}

export interface CreateFinancialConceptData {
  name: string;
  description?: string;
  type: 'income' | 'expense';
  category: string;
  code?: string;
}

export interface UpdateFinancialConceptData {
  name?: string;
  description?: string;
  type?: 'income' | 'expense';
  category?: string;
  code?: string;
  is_active?: boolean;
}

export interface CreateAccountData {
  name: string;
  account_number?: string;
  type: Account['type'];
  description?: string;
  balance?: number;
}

export interface UpdateAccountData {
  name?: string;
  account_number?: string;
  type?: Account['type'];
  description?: string;
  is_active?: boolean;
}

// Filter types
export interface TransactionFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: Transaction['status'];
  type?: 'income' | 'expense';
  concept_id?: string;
  account_id?: string;
  payment_method?: Transaction['payment_method'];
  amount_min?: number;
  amount_max?: number;
  date_from?: string;
  date_to?: string;
  created_by?: string;
  approved_by?: string;
  sort_by?: 'created_at' | 'transaction_date' | 'amount' | 'reference_number';
  sort_order?: 'asc' | 'desc';
}

export interface ConceptFilters {
  page?: number;
  per_page?: number;
  search?: string;
  type?: 'income' | 'expense';
  category?: string;
  is_active?: boolean;
}

export interface AccountFilters {
  page?: number;
  per_page?: number;
  search?: string;
  type?: Account['type'];
  is_active?: boolean;
}

// Statistics and Dashboard types
export interface TransactionStatistics {
  total_transactions: number;
  total_amount: number;
  pending_transactions: number;
  pending_amount: number;
  approved_transactions: number;
  approved_amount: number;
  income_total: number;
  expense_total: number;
  net_amount: number;
  by_status: {
    [key in Transaction['status']]: {
      count: number;
      amount: number;
    };
  };
  by_payment_method: {
    [key in Transaction['payment_method']]: {
      count: number;
      amount: number;
    };
  };
  by_concept: {
    concept_id: string;
    concept_name: string;
    count: number;
    amount: number;
  }[];
  monthly_trends: {
    month: string;
    income: number;
    expense: number;
    net: number;
    transactions_count: number;
  }[];
}

export interface FinancialDashboardData {
  statistics: TransactionStatistics;
  recent_transactions: Transaction[];
  pending_approvals: Transaction[];
  account_balances: {
    account_id: string;
    account_name: string;
    balance: number;
    type: Account['type'];
  }[];
  alerts: {
    type: 'warning' | 'error' | 'info';
    message: string;
    count?: number;
  }[];
}

// Form validation types
export interface TransactionFormData {
  financial_concept_id: string;
  description: string;
  amount: string; // String for form handling
  transaction_date: string;
  payment_method: Transaction['payment_method'];
  accounts: {
    account_id: string;
    type: 'debit' | 'credit';
    amount: string; // String for form handling
  }[];
  metadata?: Record<string, any>;
}

export interface ConceptFormData {
  name: string;
  description: string;
  type: 'income' | 'expense';
  category: string;
  code: string;
}

export interface AccountFormData {
  name: string;
  account_number: string;
  type: Account['type'];
  description: string;
  balance: string; // String for form handling
}

// Accounts Receivable types
export interface AccountReceivable {
  id: string;
  school_id: string;
  student_id: string;
  invoice_id?: string;
  concept_id: string;
  description: string;
  amount: number;
  balance: number;
  due_date: string;
  status: 'pending' | 'partial' | 'paid' | 'overdue' | 'cancelled';
  created_at: string;
  updated_at: string;
  
  // Relations
  student?: {
    id: string;
    name: string;
    email: string;
    identification: string;
  };
  concept?: FinancialConcept;
  payments?: Payment[];
  payment_plan?: PaymentPlan;
}

export interface Payment {
  id: string;
  account_receivable_id: string;
  amount: number;
  payment_date: string;
  payment_method: 'cash' | 'bank_transfer' | 'card' | 'mobile_money' | 'cheque' | 'other';
  reference_number?: string;
  notes?: string;
  voucher_url?: string;
  created_by: string;
  created_at: string;
  updated_at: string;
  
  // Relations
  account_receivable?: AccountReceivable;
  created_by_user?: {
    id: string;
    name: string;
    email: string;
  };
}

export interface PaymentPlan {
  id: string;
  account_receivable_id: string;
  total_amount: number;
  installments: number;
  installment_amount: number;
  start_date: string;
  status: 'active' | 'completed' | 'cancelled';
  notes?: string;
  created_by: string;
  created_at: string;
  updated_at: string;
  
  // Relations
  account_receivable?: AccountReceivable;
  installment_payments?: InstallmentPayment[];
}

export interface InstallmentPayment {
  id: string;
  payment_plan_id: string;
  installment_number: number;
  amount: number;
  due_date: string;
  paid_date?: string;
  status: 'pending' | 'paid' | 'overdue';
  payment_id?: string;
  created_at: string;
  updated_at: string;
  
  // Relations
  payment?: Payment;
}

// Create/Update types for Accounts Receivable
export interface CreateAccountReceivableData {
  student_id: string;
  concept_id: string;
  description: string;
  amount: number;
  due_date: string;
}

export interface UpdateAccountReceivableData {
  description?: string;
  amount?: number;
  due_date?: string;
  status?: AccountReceivable['status'];
}

export interface PaymentData {
  amount: number;
  payment_date: string;
  payment_method: Payment['payment_method'];
  reference_number?: string;
  notes?: string;
}

export interface PaymentPlanData {
  total_amount: number;
  installments: number;
  start_date: string;
  notes?: string;
}

// Filter types for Accounts Receivable
export interface ARFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: AccountReceivable['status'];
  student_id?: string;
  concept_id?: string;
  amount_min?: number;
  amount_max?: number;
  due_date_from?: string;
  due_date_to?: string;
  overdue_only?: boolean;
  sort_by?: 'created_at' | 'due_date' | 'amount' | 'balance';
  sort_order?: 'asc' | 'desc';
}

export interface PaymentFilters {
  page?: number;
  per_page?: number;
  account_receivable_id?: string;
  payment_method?: Payment['payment_method'];
  date_from?: string;
  date_to?: string;
  amount_min?: number;
  amount_max?: number;
}

// Statistics and Reports for Accounts Receivable
export interface CollectionReport {
  total_receivables: number;
  total_amount: number;
  total_balance: number;
  collected_amount: number;
  collection_rate: number;
  overdue_amount: number;
  overdue_count: number;
  by_status: {
    [key in AccountReceivable['status']]: {
      count: number;
      amount: number;
      balance: number;
    };
  };
  by_concept: {
    concept_id: string;
    concept_name: string;
    count: number;
    amount: number;
    balance: number;
  }[];
  monthly_collection: {
    month: string;
    collected: number;
    pending: number;
    overdue: number;
  }[];
  top_debtors: {
    student_id: string;
    student_name: string;
    total_debt: number;
    overdue_debt: number;
  }[];
}

// Form data types
export interface AccountReceivableFormData {
  student_id: string;
  concept_id: string;
  description: string;
  amount: string;
  due_date: string;
}

export interface PaymentFormData {
  amount: string;
  payment_date: string;
  payment_method: Payment['payment_method'];
  reference_number: string;
  notes: string;
}

export interface PaymentPlanFormData {
  total_amount: string;
  installments: string;
  start_date: string;
  notes: string;
}

// State types for Accounts Receivable
export interface AccountsReceivableState {
  accountsReceivable: {
    data: AccountReceivable[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
  };
  payments: Payment[];
  paymentPlans: PaymentPlan[];
  collectionReport: CollectionReport | null;
  filters: ARFilters;
  selectedAR: AccountReceivable | null;
  isLoading: {
    accountsReceivable: boolean;
    payments: boolean;
    paymentPlans: boolean;
    report: boolean;
    creating: boolean;
    updating: boolean;
    deleting: boolean;
    paying: boolean;
  };
  error: {
    accountsReceivable: string | null;
    payments: string | null;
    paymentPlans: string | null;
    report: string | null;
    form: string | null;
  };
}

// Redux state types
export interface FinancialState {
  transactions: {
    data: Transaction[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
  };
  concepts: FinancialConcept[];
  accounts: Account[];
  statistics: TransactionStatistics | null;
  dashboardData: FinancialDashboardData | null;
  filters: TransactionFilters;
  selectedTransaction: Transaction | null;
  isLoading: {
    transactions: boolean;
    concepts: boolean;
    accounts: boolean;
    statistics: boolean;
    dashboard: boolean;
    creating: boolean;
    updating: boolean;
    deleting: boolean;
  };
  error: {
    transactions: string | null;
    concepts: string | null;
    accounts: string | null;
    statistics: string | null;
    dashboard: string | null;
    form: string | null;
  };
}