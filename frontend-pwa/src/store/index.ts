import { configureStore } from '@reduxjs/toolkit';
import { useDispatch, useSelector, TypedUseSelectorHook } from 'react-redux';
import authReducer from './authSlice';
import financialReducer from './financialSlice';

// Configure store
export const store = configureStore({
  reducer: {
    auth: authReducer,
    financial: financialReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: ['persist/PERSIST', 'persist/REHYDRATE'],
      },
    }),
  devTools: process.env.NODE_ENV !== 'production',
});

// Types
export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;

// Typed hooks
export const useAppDispatch = () => useDispatch<AppDispatch>();
export const useAppSelector: TypedUseSelectorHook<RootState> = useSelector;

// Auth Selectors
export const selectAuth = (state: RootState) => state.auth;
export const selectUser = (state: RootState) => state.auth.user;
export const selectIsAuthenticated = (state: RootState) => state.auth.isAuthenticated;
export const selectAuthLoading = (state: RootState) => state.auth.isLoading;
export const selectAuthError = (state: RootState) => state.auth.error;
export const selectUserRole = (state: RootState) => state.auth.user?.role;
export const selectUserPermissions = (state: RootState) => state.auth.user?.permissions || [];

// Financial Selectors
export const selectFinancial = (state: RootState) => state.financial;
export const selectTransactions = (state: RootState) => state.financial.transactions;
export const selectConcepts = (state: RootState) => state.financial.concepts;
export const selectAccounts = (state: RootState) => state.financial.accounts;
export const selectFinancialStatistics = (state: RootState) => state.financial.statistics;
export const selectDashboardData = (state: RootState) => state.financial.dashboardData;
export const selectFinancialFilters = (state: RootState) => state.financial.filters;
export const selectSelectedTransaction = (state: RootState) => state.financial.selectedTransaction;
export const selectFinancialLoading = (state: RootState) => state.financial.isLoading;
export const selectFinancialErrors = (state: RootState) => state.financial.error;

export default store;