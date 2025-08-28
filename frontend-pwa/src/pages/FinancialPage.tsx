import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import FinancialLayout from '../components/financial/FinancialLayout';
import FinancialDashboard from '../components/financial/FinancialDashboard';
import TransactionList from '../components/financial/TransactionList';
import TransactionForm from '../components/financial/TransactionForm';
import ConceptManager from '../components/financial/ConceptManager';
import AccountManager from '../components/financial/AccountManager';
import Reports from '../components/financial/reports/Reports';

const FinancialPage: React.FC = () => {
  return (
    <Routes>
      <Route path="/*" element={<FinancialLayout />}>
        {/* Dashboard - Default route */}
        <Route index element={<Navigate to="dashboard" replace />} />
        <Route path="dashboard" element={<FinancialDashboard />} />
        
        {/* Transactions */}
        <Route path="transactions" element={<TransactionList />} />
        <Route path="transactions/new" element={<TransactionForm />} />
        <Route path="transactions/:id/edit" element={<TransactionForm />} />
        
        {/* Financial Concepts */}
        <Route path="concepts" element={<ConceptManager />} />
        
        {/* Accounts */}
        <Route path="accounts" element={<AccountManager />} />
        
        {/* Financial Reports */}
        <Route path="reports" element={<Reports />} />
      </Route>
    </Routes>
  );
};

export default FinancialPage;