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