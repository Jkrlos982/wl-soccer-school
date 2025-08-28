import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import { AccountsReceivableService } from '../services/accountsReceivableService';
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
  CollectionReport,
  AccountsReceivableState
} from '../types/financial';
import { PaginatedResponse } from '../types';

// Initial state
const initialState: AccountsReceivableState = {
  accountsReceivable: {
    data: [],
    total: 0,
    current_page: 1,
    last_page: 1,
    per_page: 15,
  },
  payments: [],
  paymentPlans: [],
  collectionReport: null,
  filters: {},
  selectedAR: null,
  isLoading: {
    accountsReceivable: false,
    payments: false,
    paymentPlans: false,
    report: false,
    creating: false,
    updating: false,
    deleting: false,
    paying: false,
  },
  error: {
    accountsReceivable: null,
    payments: null,
    paymentPlans: null,
    report: null,
    form: null,
  },
};

// Async thunks for accounts receivable
export const fetchAccountsReceivable = createAsyncThunk(
  'accountsReceivable/fetchAccountsReceivable',
  async (filters?: ARFilters) => {
    const response = await AccountsReceivableService.getAccountsReceivable(filters);
    return response;
  }
);

export const fetchAccountReceivable = createAsyncThunk(
  'accountsReceivable/fetchAccountReceivable',
  async (arId: string) => {
    const response = await AccountsReceivableService.getAccountReceivable(arId);
    return response;
  }
);

export const createAccountReceivable = createAsyncThunk(
  'accountsReceivable/createAccountReceivable',
  async (data: CreateAccountReceivableData) => {
    const response = await AccountsReceivableService.createAccountReceivable(data);
    return response;
  }
);

export const updateAccountReceivable = createAsyncThunk(
  'accountsReceivable/updateAccountReceivable',
  async ({ arId, data }: { arId: string; data: UpdateAccountReceivableData }) => {
    const response = await AccountsReceivableService.updateAccountReceivable(arId, data);
    return response;
  }
);

export const deleteAccountReceivable = createAsyncThunk(
  'accountsReceivable/deleteAccountReceivable',
  async (arId: string) => {
    await AccountsReceivableService.deleteAccountReceivable(arId);
    return arId;
  }
);

// Async thunks for payments
export const fetchPayments = createAsyncThunk(
  'accountsReceivable/fetchPayments',
  async (filters?: PaymentFilters) => {
    const response = await AccountsReceivableService.getPayments(filters);
    return response;
  }
);

export const fetchPayment = createAsyncThunk(
  'accountsReceivable/fetchPayment',
  async (paymentId: string) => {
    const response = await AccountsReceivableService.getPayment(paymentId);
    return response;
  }
);

export const registerPayment = createAsyncThunk(
  'accountsReceivable/registerPayment',
  async ({ arId, paymentData }: { arId: string; paymentData: PaymentData }) => {
    const response = await AccountsReceivableService.registerPayment(arId, paymentData);
    return response;
  }
);

export const updatePayment = createAsyncThunk(
  'accountsReceivable/updatePayment',
  async ({ paymentId, data }: { paymentId: string; data: Partial<PaymentData> }) => {
    const response = await AccountsReceivableService.updatePayment(paymentId, data);
    return response;
  }
);

export const deletePayment = createAsyncThunk(
  'accountsReceivable/deletePayment',
  async (paymentId: string) => {
    await AccountsReceivableService.deletePayment(paymentId);
    return paymentId;
  }
);

// Async thunks for payment plans
export const fetchPaymentPlans = createAsyncThunk(
  'accountsReceivable/fetchPaymentPlans',
  async (arId?: string) => {
    const response = await AccountsReceivableService.getPaymentPlans(arId);
    return response;
  }
);

export const fetchPaymentPlan = createAsyncThunk(
  'accountsReceivable/fetchPaymentPlan',
  async (planId: string) => {
    const response = await AccountsReceivableService.getPaymentPlan(planId);
    return response;
  }
);

export const createPaymentPlan = createAsyncThunk(
  'accountsReceivable/createPaymentPlan',
  async ({ arId, data }: { arId: string; data: PaymentPlanData }) => {
    const response = await AccountsReceivableService.createPaymentPlan(arId, data);
    return response;
  }
);

export const updatePaymentPlan = createAsyncThunk(
  'accountsReceivable/updatePaymentPlan',
  async ({ planId, data }: { planId: string; data: Partial<PaymentPlanData> }) => {
    const response = await AccountsReceivableService.updatePaymentPlan(planId, data);
    return response;
  }
);

export const cancelPaymentPlan = createAsyncThunk(
  'accountsReceivable/cancelPaymentPlan',
  async (planId: string) => {
    await AccountsReceivableService.cancelPaymentPlan(planId);
    return planId;
  }
);

// Async thunks for installment payments
export const fetchInstallmentPayments = createAsyncThunk(
  'accountsReceivable/fetchInstallmentPayments',
  async (planId: string) => {
    const response = await AccountsReceivableService.getInstallmentPayments(planId);
    return response;
  }
);

export const payInstallment = createAsyncThunk(
  'accountsReceivable/payInstallment',
  async ({
    planId,
    installmentId,
    paymentData,
  }: {
    planId: string;
    installmentId: string;
    paymentData: PaymentData;
  }) => {
    const response = await AccountsReceivableService.payInstallment(
      planId,
      installmentId,
      paymentData
    );
    return response;
  }
);

// Async thunks for student-specific data
export const fetchStudentAccountsReceivable = createAsyncThunk(
  'accountsReceivable/fetchStudentAccountsReceivable',
  async ({ studentId, filters }: { studentId: string; filters?: Omit<ARFilters, 'student_id'> }) => {
    const response = await AccountsReceivableService.getStudentAccountsReceivable(
      studentId,
      filters
    );
    return response;
  }
);

export const fetchStudentBalance = createAsyncThunk(
  'accountsReceivable/fetchStudentBalance',
  async (studentId: string) => {
    const response = await AccountsReceivableService.getStudentBalance(studentId);
    return response;
  }
);

export const fetchStudentPaymentHistory = createAsyncThunk(
  'accountsReceivable/fetchStudentPaymentHistory',
  async ({ studentId, filters }: { studentId: string; filters?: PaymentFilters }) => {
    const response = await AccountsReceivableService.getStudentPaymentHistory(
      studentId,
      filters
    );
    return response;
  }
);

// Async thunks for reports and analytics
export const fetchCollectionReport = createAsyncThunk(
  'accountsReceivable/fetchCollectionReport',
  async (filters: {
    start_date?: string;
    end_date?: string;
    student_id?: string;
    concept_id?: string;
    status?: AccountReceivable['status'];
  }) => {
    const response = await AccountsReceivableService.getCollectionReport(filters);
    return response;
  }
);

export const fetchOverdueAccounts = createAsyncThunk(
  'accountsReceivable/fetchOverdueAccounts',
  async () => {
    const response = await AccountsReceivableService.getOverdueAccounts();
    return response;
  }
);

export const fetchCollectionSummary = createAsyncThunk(
  'accountsReceivable/fetchCollectionSummary',
  async () => {
    const response = await AccountsReceivableService.getCollectionSummary();
    return response;
  }
);

// Async thunks for bulk operations
export const bulkCreateAccountsReceivable = createAsyncThunk(
  'accountsReceivable/bulkCreateAccountsReceivable',
  async (data: {
    accounts: CreateAccountReceivableData[];
    apply_to_students?: string[];
  }) => {
    const response = await AccountsReceivableService.bulkCreateAccountsReceivable(data);
    return response;
  }
);

export const bulkRecordPayments = createAsyncThunk(
  'accountsReceivable/bulkRecordPayments',
  async (payments: {
    account_receivable_id: string;
    payment_data: PaymentData;
  }[]) => {
    const response = await AccountsReceivableService.bulkRecordPayments(payments);
    return response;
  }
);

export const bulkUpdateStatus = createAsyncThunk(
  'accountsReceivable/bulkUpdateStatus',
  async ({ arIds, status }: { arIds: string[]; status: AccountReceivable['status'] }) => {
    const response = await AccountsReceivableService.bulkUpdateStatus(arIds, status);
    return response;
  }
);

// Async thunks for notifications
export const sendPaymentReminder = createAsyncThunk(
  'accountsReceivable/sendPaymentReminder',
  async ({ arId, message }: { arId: string; message?: string }) => {
    const response = await AccountsReceivableService.sendPaymentReminder(arId, message);
    return response;
  }
);

export const sendBulkReminders = createAsyncThunk(
  'accountsReceivable/sendBulkReminders',
  async ({ arIds, message }: { arIds: string[]; message?: string }) => {
    const response = await AccountsReceivableService.sendBulkReminders(arIds, message);
    return response;
  }
);

// Accounts Receivable slice
const accountsReceivableSlice = createSlice({
  name: 'accountsReceivable',
  initialState,
  reducers: {
    // Filter actions
    setFilters: (state, action: PayloadAction<Partial<ARFilters>>) => {
      state.filters = { ...state.filters, ...action.payload };
    },
    resetFilters: (state) => {
      state.filters = {};
    },

    // Selection actions
    setSelectedAR: (state, action: PayloadAction<AccountReceivable | null>) => {
      state.selectedAR = action.payload;
    },

    // Error actions
    clearError: (state, action: PayloadAction<keyof AccountsReceivableState['error']>) => {
      state.error[action.payload] = null;
    },
    clearAllErrors: (state) => {
      Object.keys(state.error).forEach((key) => {
        state.error[key as keyof AccountsReceivableState['error']] = null;
      });
    },

    // Reset actions
    resetAccountsReceivable: (state) => {
      state.accountsReceivable = {
        data: [],
        total: 0,
        current_page: 1,
        last_page: 1,
        per_page: 15,
      };
    },
    resetPayments: (state) => {
      state.payments = [];
    },
    resetState: () => initialState,
  },
  extraReducers: (builder) => {
    // Fetch accounts receivable
    builder
      .addCase(fetchAccountsReceivable.pending, (state) => {
        state.isLoading.accountsReceivable = true;
        state.error.accountsReceivable = null;
      })
      .addCase(fetchAccountsReceivable.fulfilled, (state, action) => {
        state.isLoading.accountsReceivable = false;
        state.accountsReceivable = action.payload;
      })
      .addCase(fetchAccountsReceivable.rejected, (state, action) => {
        state.isLoading.accountsReceivable = false;
        state.error.accountsReceivable = action.error.message || 'Failed to fetch accounts receivable';
      })

      // Fetch single account receivable
      .addCase(fetchAccountReceivable.pending, (state) => {
        state.isLoading.accountsReceivable = true;
      })
      .addCase(fetchAccountReceivable.fulfilled, (state, action) => {
        state.isLoading.accountsReceivable = false;
        state.selectedAR = action.payload;
      })
      .addCase(fetchAccountReceivable.rejected, (state, action) => {
        state.isLoading.accountsReceivable = false;
        state.error.accountsReceivable = action.error.message || 'Failed to fetch account receivable';
      })

      // Create account receivable
      .addCase(createAccountReceivable.pending, (state) => {
        state.isLoading.creating = true;
        state.error.form = null;
      })
      .addCase(createAccountReceivable.fulfilled, (state, action) => {
        state.isLoading.creating = false;
        state.accountsReceivable.data.unshift(action.payload);
        state.accountsReceivable.total += 1;
      })
      .addCase(createAccountReceivable.rejected, (state, action) => {
        state.isLoading.creating = false;
        state.error.form = action.error.message || 'Failed to create account receivable';
      })

      // Update account receivable
      .addCase(updateAccountReceivable.pending, (state) => {
        state.isLoading.updating = true;
        state.error.form = null;
      })
      .addCase(updateAccountReceivable.fulfilled, (state, action) => {
        state.isLoading.updating = false;
        const index = state.accountsReceivable.data.findIndex(ar => ar.id === action.payload.id);
        if (index !== -1) {
          state.accountsReceivable.data[index] = action.payload;
        }
        if (state.selectedAR?.id === action.payload.id) {
          state.selectedAR = action.payload;
        }
      })
      .addCase(updateAccountReceivable.rejected, (state, action) => {
        state.isLoading.updating = false;
        state.error.form = action.error.message || 'Failed to update account receivable';
      })

      // Delete account receivable
      .addCase(deleteAccountReceivable.pending, (state) => {
        state.isLoading.deleting = true;
      })
      .addCase(deleteAccountReceivable.fulfilled, (state, action) => {
        state.isLoading.deleting = false;
        state.accountsReceivable.data = state.accountsReceivable.data.filter(
          ar => ar.id !== action.payload
        );
        state.accountsReceivable.total -= 1;
        if (state.selectedAR?.id === action.payload) {
          state.selectedAR = null;
        }
      })
      .addCase(deleteAccountReceivable.rejected, (state, action) => {
        state.isLoading.deleting = false;
        state.error.accountsReceivable = action.error.message || 'Failed to delete account receivable';
      })

      // Fetch payments
      .addCase(fetchPayments.pending, (state) => {
        state.isLoading.payments = true;
        state.error.payments = null;
      })
      .addCase(fetchPayments.fulfilled, (state, action) => {
        state.isLoading.payments = false;
        state.payments = action.payload.data;
      })
      .addCase(fetchPayments.rejected, (state, action) => {
        state.isLoading.payments = false;
        state.error.payments = action.error.message || 'Failed to fetch payments';
      })

      // Fetch single payment
      .addCase(fetchPayment.fulfilled, (state, action) => {
        // Payment fetched successfully
      })

      // Register payment
      .addCase(registerPayment.pending, (state) => {
        state.isLoading.paying = true;
        state.error.form = null;
      })
      .addCase(registerPayment.fulfilled, (state, action) => {
        state.isLoading.paying = false;
        state.payments.push(action.payload);
      })
      .addCase(registerPayment.rejected, (state, action) => {
        state.isLoading.paying = false;
        state.error.form = action.error.message || 'Failed to register payment';
      })

      // Update payment
      .addCase(updatePayment.fulfilled, (state, action) => {
        const index = state.payments.findIndex(p => p.id === action.payload.id);
        if (index !== -1) {
          state.payments[index] = action.payload;
        }
      })

      // Delete payment
      .addCase(deletePayment.fulfilled, (state, action) => {
        state.payments = state.payments.filter(p => p.id !== action.payload);
      })

      // Fetch payment plans
      .addCase(fetchPaymentPlans.pending, (state) => {
        state.isLoading.paymentPlans = true;
        state.error.paymentPlans = null;
      })
      .addCase(fetchPaymentPlans.fulfilled, (state, action) => {
        state.isLoading.paymentPlans = false;
        state.paymentPlans = action.payload;
      })
      .addCase(fetchPaymentPlans.rejected, (state, action) => {
        state.isLoading.paymentPlans = false;
        state.error.paymentPlans = action.error.message || 'Failed to fetch payment plans';
      })

      // Fetch single payment plan
      .addCase(fetchPaymentPlan.fulfilled, (state, action) => {
        // Payment plan fetched successfully
      })

      // Create payment plan
      .addCase(createPaymentPlan.fulfilled, (state, action) => {
        state.paymentPlans.push(action.payload);
      })

      // Update payment plan
      .addCase(updatePaymentPlan.fulfilled, (state, action) => {
        const index = state.paymentPlans.findIndex(pp => pp.id === action.payload.id);
        if (index !== -1) {
          state.paymentPlans[index] = action.payload;
        }
      })

      // Cancel payment plan
      .addCase(cancelPaymentPlan.fulfilled, (state, action) => {
        const index = state.paymentPlans.findIndex(pp => pp.id === action.payload);
        if (index !== -1) {
          state.paymentPlans[index].status = 'cancelled';
        }
      })

      // Fetch installment payments
      .addCase(fetchInstallmentPayments.pending, (state) => {
        state.isLoading.paymentPlans = true;
      })
      .addCase(fetchInstallmentPayments.fulfilled, (state, action) => {
        state.isLoading.paymentPlans = false;
      })
      .addCase(fetchInstallmentPayments.rejected, (state, action) => {
        state.isLoading.paymentPlans = false;
      })

      // Pay installment
      .addCase(payInstallment.fulfilled, (state, action) => {
        state.payments.unshift(action.payload);
      })

      // Fetch collection report
      .addCase(fetchCollectionReport.pending, (state) => {
        state.isLoading.report = true;
        state.error.report = null;
      })
      .addCase(fetchCollectionReport.fulfilled, (state, action) => {
        state.isLoading.report = false;
        state.collectionReport = action.payload;
      })
      .addCase(fetchCollectionReport.rejected, (state, action) => {
        state.isLoading.report = false;
        state.error.report = action.error.message || 'Failed to fetch collection report';
      })

      // Fetch overdue accounts
      .addCase(fetchOverdueAccounts.pending, (state) => {
        state.isLoading.accountsReceivable = true;
      })
      .addCase(fetchOverdueAccounts.fulfilled, (state, action) => {
        state.isLoading.accountsReceivable = false;
      })
      .addCase(fetchOverdueAccounts.rejected, (state, action) => {
        state.isLoading.accountsReceivable = false;
      })

      // Fetch collection summary
      .addCase(fetchCollectionSummary.pending, (state) => {
        state.isLoading.report = true;
      })
      .addCase(fetchCollectionSummary.fulfilled, (state, action) => {
        state.isLoading.report = false;
      })
      .addCase(fetchCollectionSummary.rejected, (state, action) => {
        state.isLoading.report = false;
      });
  },
});

// Export actions
export const {
  setFilters,
  resetFilters,
  setSelectedAR,
  clearError,
  clearAllErrors,
  resetAccountsReceivable,
  resetPayments,
  resetState,
} = accountsReceivableSlice.actions;

// Export reducer
export default accountsReceivableSlice.reducer;