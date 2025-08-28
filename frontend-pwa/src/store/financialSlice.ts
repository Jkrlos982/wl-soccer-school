import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import {
  Transaction,
  FinancialConcept,
  Account,
  TransactionFilters,
  TransactionStatistics,
  FinancialDashboardData,
  FinancialState,
  CreateTransactionData,
  UpdateTransactionData,
  CreateFinancialConceptData,
  UpdateFinancialConceptData,
  CreateAccountData,
  UpdateAccountData,
  PaginatedResponse,
} from '../types';
import { TransactionService } from '../services';

// Initial state
const initialState: FinancialState = {
  transactions: {
    data: [],
    total: 0,
    current_page: 1,
    last_page: 1,
    per_page: 10,
  },
  concepts: [],
  accounts: [],
  statistics: null,
  dashboardData: null,
  filters: {
    page: 1,
    per_page: 10,
    sort_by: 'created_at',
    sort_order: 'desc',
  },
  selectedTransaction: null,
  isLoading: {
    transactions: false,
    concepts: false,
    accounts: false,
    statistics: false,
    dashboard: false,
    creating: false,
    updating: false,
    deleting: false,
  },
  error: {
    transactions: null,
    concepts: null,
    accounts: null,
    statistics: null,
    dashboard: null,
    form: null,
  },
};

// Async thunks for transactions
export const fetchTransactions = createAsyncThunk(
  'financial/fetchTransactions',
  async (filters?: TransactionFilters) => {
    const response = await TransactionService.getTransactions(filters);
    return response;
  }
);

export const fetchTransaction = createAsyncThunk(
  'financial/fetchTransaction',
  async (transactionId: string) => {
    const response = await TransactionService.getTransaction(transactionId);
    return response;
  }
);

export const createTransaction = createAsyncThunk(
  'financial/createTransaction',
  async (data: CreateTransactionData) => {
    const response = await TransactionService.createTransaction(data);
    return response;
  }
);

export const updateTransaction = createAsyncThunk(
  'financial/updateTransaction',
  async ({ id, data }: { id: string; data: UpdateTransactionData }) => {
    const response = await TransactionService.updateTransaction(id, data);
    return response;
  }
);

export const deleteTransaction = createAsyncThunk(
  'financial/deleteTransaction',
  async (transactionId: string) => {
    await TransactionService.deleteTransaction(transactionId);
    return transactionId;
  }
);

export const approveTransaction = createAsyncThunk(
  'financial/approveTransaction',
  async ({ id, notes }: { id: string; notes?: string }) => {
    const response = await TransactionService.approveTransaction(id, notes);
    return response;
  }
);

export const rejectTransaction = createAsyncThunk(
  'financial/rejectTransaction',
  async ({ id, notes }: { id: string; notes?: string }) => {
    const response = await TransactionService.rejectTransaction(id, notes);
    return response;
  }
);

export const cancelTransaction = createAsyncThunk(
  'financial/cancelTransaction',
  async ({ id, reason }: { id: string; reason?: string }) => {
    const response = await TransactionService.cancelTransaction(id, reason);
    return response;
  }
);

// Async thunks for concepts
export const fetchConcepts = createAsyncThunk(
  'financial/fetchConcepts',
  async () => {
    const response = await TransactionService.getAllConcepts();
    return response;
  }
);

export const createConcept = createAsyncThunk(
  'financial/createConcept',
  async (data: CreateFinancialConceptData) => {
    const response = await TransactionService.createConcept(data);
    return response;
  }
);

export const updateConcept = createAsyncThunk(
  'financial/updateConcept',
  async ({ id, data }: { id: string; data: UpdateFinancialConceptData }) => {
    const response = await TransactionService.updateConcept(id, data);
    return response;
  }
);

export const deleteConcept = createAsyncThunk(
  'financial/deleteConcept',
  async (conceptId: string) => {
    await TransactionService.deleteConcept(conceptId);
    return conceptId;
  }
);

// Async thunks for accounts
export const fetchAccounts = createAsyncThunk(
  'financial/fetchAccounts',
  async () => {
    const response = await TransactionService.getAllAccounts();
    return response;
  }
);

export const createAccount = createAsyncThunk(
  'financial/createAccount',
  async (data: CreateAccountData) => {
    const response = await TransactionService.createAccount(data);
    return response;
  }
);

export const updateAccount = createAsyncThunk(
  'financial/updateAccount',
  async ({ id, data }: { id: string; data: UpdateAccountData }) => {
    const response = await TransactionService.updateAccount(id, data);
    return response;
  }
);

export const deleteAccount = createAsyncThunk(
  'financial/deleteAccount',
  async (accountId: string) => {
    await TransactionService.deleteAccount(accountId);
    return accountId;
  }
);

// Async thunks for statistics and dashboard
export const fetchStatistics = createAsyncThunk(
  'financial/fetchStatistics',
  async (params?: { date_from?: string; date_to?: string }) => {
    const response = await TransactionService.getTransactionStatistics(params);
    return response;
  }
);

export const fetchDashboardData = createAsyncThunk(
  'financial/fetchDashboardData',
  async () => {
    const response = await TransactionService.getDashboardData();
    return response;
  }
);

// Financial slice
const financialSlice = createSlice({
  name: 'financial',
  initialState,
  reducers: {
    // Filter actions
    setFilters: (state, action: PayloadAction<Partial<TransactionFilters>>) => {
      state.filters = { ...state.filters, ...action.payload };
    },
    resetFilters: (state) => {
      state.filters = {
        page: 1,
        per_page: 10,
        sort_by: 'created_at',
        sort_order: 'desc',
      };
    },
    
    // Selection actions
    setSelectedTransaction: (state, action: PayloadAction<Transaction | null>) => {
      state.selectedTransaction = action.payload;
    },
    
    // Error actions
    clearError: (state, action: PayloadAction<keyof FinancialState['error']>) => {
      state.error[action.payload] = null;
    },
    clearAllErrors: (state) => {
      Object.keys(state.error).forEach((key) => {
        state.error[key as keyof FinancialState['error']] = null;
      });
    },
    
    // Reset actions
    resetTransactions: (state) => {
      state.transactions = {
        data: [],
        total: 0,
        current_page: 1,
        last_page: 1,
        per_page: 10,
      };
    },
    resetState: () => initialState,
  },
  extraReducers: (builder) => {
    // Fetch transactions
    builder
      .addCase(fetchTransactions.pending, (state) => {
        state.isLoading.transactions = true;
        state.error.transactions = null;
      })
      .addCase(fetchTransactions.fulfilled, (state, action) => {
        state.isLoading.transactions = false;
        state.transactions = action.payload;
      })
      .addCase(fetchTransactions.rejected, (state, action) => {
        state.isLoading.transactions = false;
        state.error.transactions = action.error.message || 'Failed to fetch transactions';
      })
      
      // Fetch single transaction
      .addCase(fetchTransaction.pending, (state) => {
        state.isLoading.transactions = true;
      })
      .addCase(fetchTransaction.fulfilled, (state, action) => {
        state.isLoading.transactions = false;
        state.selectedTransaction = action.payload;
      })
      .addCase(fetchTransaction.rejected, (state, action) => {
        state.isLoading.transactions = false;
        state.error.transactions = action.error.message || 'Failed to fetch transaction';
      })
      
      // Create transaction
      .addCase(createTransaction.pending, (state) => {
        state.isLoading.creating = true;
        state.error.form = null;
      })
      .addCase(createTransaction.fulfilled, (state, action) => {
        state.isLoading.creating = false;
        state.transactions.data.unshift(action.payload);
        state.transactions.total += 1;
      })
      .addCase(createTransaction.rejected, (state, action) => {
        state.isLoading.creating = false;
        state.error.form = action.error.message || 'Failed to create transaction';
      })
      
      // Update transaction
      .addCase(updateTransaction.pending, (state) => {
        state.isLoading.updating = true;
        state.error.form = null;
      })
      .addCase(updateTransaction.fulfilled, (state, action) => {
        state.isLoading.updating = false;
        const index = state.transactions.data.findIndex(t => t.id === action.payload.id);
        if (index !== -1) {
          state.transactions.data[index] = action.payload;
        }
        if (state.selectedTransaction?.id === action.payload.id) {
          state.selectedTransaction = action.payload;
        }
      })
      .addCase(updateTransaction.rejected, (state, action) => {
        state.isLoading.updating = false;
        state.error.form = action.error.message || 'Failed to update transaction';
      })
      
      // Delete transaction
      .addCase(deleteTransaction.pending, (state) => {
        state.isLoading.deleting = true;
      })
      .addCase(deleteTransaction.fulfilled, (state, action) => {
        state.isLoading.deleting = false;
        state.transactions.data = state.transactions.data.filter(t => t.id !== action.payload);
        state.transactions.total -= 1;
        if (state.selectedTransaction?.id === action.payload) {
          state.selectedTransaction = null;
        }
      })
      .addCase(deleteTransaction.rejected, (state, action) => {
        state.isLoading.deleting = false;
        state.error.transactions = action.error.message || 'Failed to delete transaction';
      })
      
      // Approve transaction
      .addCase(approveTransaction.fulfilled, (state, action) => {
        const index = state.transactions.data.findIndex(t => t.id === action.payload.id);
        if (index !== -1) {
          state.transactions.data[index] = action.payload;
        }
        if (state.selectedTransaction?.id === action.payload.id) {
          state.selectedTransaction = action.payload;
        }
      })
      
      // Reject transaction
      .addCase(rejectTransaction.fulfilled, (state, action) => {
        const index = state.transactions.data.findIndex(t => t.id === action.payload.id);
        if (index !== -1) {
          state.transactions.data[index] = action.payload;
        }
        if (state.selectedTransaction?.id === action.payload.id) {
          state.selectedTransaction = action.payload;
        }
      })
      
      // Cancel transaction
      .addCase(cancelTransaction.fulfilled, (state, action) => {
        const index = state.transactions.data.findIndex(t => t.id === action.payload.id);
        if (index !== -1) {
          state.transactions.data[index] = action.payload;
        }
        if (state.selectedTransaction?.id === action.payload.id) {
          state.selectedTransaction = action.payload;
        }
      })
      
      // Fetch concepts
      .addCase(fetchConcepts.pending, (state) => {
        state.isLoading.concepts = true;
        state.error.concepts = null;
      })
      .addCase(fetchConcepts.fulfilled, (state, action) => {
        state.isLoading.concepts = false;
        state.concepts = action.payload;
      })
      .addCase(fetchConcepts.rejected, (state, action) => {
        state.isLoading.concepts = false;
        state.error.concepts = action.error.message || 'Failed to fetch concepts';
      })
      
      // Create concept
      .addCase(createConcept.fulfilled, (state, action) => {
        state.concepts.push(action.payload);
      })
      
      // Update concept
      .addCase(updateConcept.fulfilled, (state, action) => {
        const index = state.concepts.findIndex(c => c.id === action.payload.id);
        if (index !== -1) {
          state.concepts[index] = action.payload;
        }
      })
      
      // Delete concept
      .addCase(deleteConcept.fulfilled, (state, action) => {
        state.concepts = state.concepts.filter(c => c.id !== action.payload);
      })
      
      // Fetch accounts
      .addCase(fetchAccounts.pending, (state) => {
        state.isLoading.accounts = true;
        state.error.accounts = null;
      })
      .addCase(fetchAccounts.fulfilled, (state, action) => {
        state.isLoading.accounts = false;
        state.accounts = action.payload;
      })
      .addCase(fetchAccounts.rejected, (state, action) => {
        state.isLoading.accounts = false;
        state.error.accounts = action.error.message || 'Failed to fetch accounts';
      })
      
      // Create account
      .addCase(createAccount.fulfilled, (state, action) => {
        state.accounts.push(action.payload);
      })
      
      // Update account
      .addCase(updateAccount.fulfilled, (state, action) => {
        const index = state.accounts.findIndex(a => a.id === action.payload.id);
        if (index !== -1) {
          state.accounts[index] = action.payload;
        }
      })
      
      // Delete account
      .addCase(deleteAccount.fulfilled, (state, action) => {
        state.accounts = state.accounts.filter(a => a.id !== action.payload);
      })
      
      // Fetch statistics
      .addCase(fetchStatistics.pending, (state) => {
        state.isLoading.statistics = true;
        state.error.statistics = null;
      })
      .addCase(fetchStatistics.fulfilled, (state, action) => {
        state.isLoading.statistics = false;
        state.statistics = action.payload;
      })
      .addCase(fetchStatistics.rejected, (state, action) => {
        state.isLoading.statistics = false;
        state.error.statistics = action.error.message || 'Failed to fetch statistics';
      })
      
      // Fetch dashboard data
      .addCase(fetchDashboardData.pending, (state) => {
        state.isLoading.dashboard = true;
        state.error.dashboard = null;
      })
      .addCase(fetchDashboardData.fulfilled, (state, action) => {
        state.isLoading.dashboard = false;
        state.dashboardData = action.payload;
      })
      .addCase(fetchDashboardData.rejected, (state, action) => {
        state.isLoading.dashboard = false;
        state.error.dashboard = action.error.message || 'Failed to fetch dashboard data';
      });
  },
});

// Export actions
export const {
  setFilters,
  resetFilters,
  setSelectedTransaction,
  clearError,
  clearAllErrors,
  resetTransactions,
  resetState,
} = financialSlice.actions;

// Export reducer
export default financialSlice.reducer;