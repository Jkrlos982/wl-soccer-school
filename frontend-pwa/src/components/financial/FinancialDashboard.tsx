import React, { useEffect } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Paper,
  Stack,
  Chip,
  IconButton,
  Tooltip,
  LinearProgress
} from '@mui/material';
import {
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  AccountBalance as AccountBalanceIcon,
  Receipt as ReceiptIcon,
  PendingActions as PendingActionsIcon,
  Refresh as RefreshIcon
} from '@mui/icons-material';
import { useDispatch, useSelector } from 'react-redux';
import { format } from 'date-fns';
import { RootState, AppDispatch } from '../../store';
import {
  fetchDashboardData,
  fetchStatistics,
  fetchTransactions
} from '../../store/financialSlice';
import { Transaction } from '../../types/financial';

interface MetricCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: React.ReactNode;
  color: 'primary' | 'success' | 'error' | 'warning' | 'info';
  trend?: {
    value: number;
    isPositive: boolean;
  };
}

const MetricCard: React.FC<MetricCardProps> = ({
  title,
  value,
  subtitle,
  icon,
  color,
  trend
}) => {
  return (
    <Card sx={{ height: '100%' }}>
      <CardContent>
        <Stack direction="row" alignItems="center" justifyContent="space-between">
          <Box>
            <Typography color="text.secondary" gutterBottom variant="body2">
              {title}
            </Typography>
            <Typography variant="h4" component="div" color={`${color}.main`}>
              {typeof value === 'number' ? `$${value.toFixed(2)}` : value}
            </Typography>
            {subtitle && (
              <Typography variant="body2" color="text.secondary">
                {subtitle}
              </Typography>
            )}
            {trend && (
              <Stack direction="row" alignItems="center" spacing={1} sx={{ mt: 1 }}>
                {trend.isPositive ? (
                  <TrendingUpIcon color="success" fontSize="small" />
                ) : (
                  <TrendingDownIcon color="error" fontSize="small" />
                )}
                <Typography
                  variant="body2"
                  color={trend.isPositive ? 'success.main' : 'error.main'}
                >
                  {trend.isPositive ? '+' : ''}{trend.value.toFixed(1)}%
                </Typography>
              </Stack>
            )}
          </Box>
          <Box
            sx={{
              p: 2,
              borderRadius: 2,
              bgcolor: `${color}.light`,
              color: `${color}.main`
            }}
          >
            {icon}
          </Box>
        </Stack>
      </CardContent>
    </Card>
  );
};

interface RecentTransactionItemProps {
  transaction: Transaction;
}

const RecentTransactionItem: React.FC<RecentTransactionItemProps> = ({ transaction }) => {
  const getStatusColor = (status: Transaction['status']) => {
    switch (status) {
      case 'completed':
        return 'success';
      case 'pending':
        return 'warning';
      case 'cancelled':
        return 'error';
      default:
        return 'default';
    }
  };

  return (
    <Paper sx={{ p: 2, mb: 1 }}>
      <Stack direction="row" alignItems="center" justifyContent="space-between">
        <Box sx={{ flex: 1 }}>
          <Typography variant="body2" fontWeight="medium">
            {transaction.description}
          </Typography>
          <Typography variant="caption" color="text.secondary">
            {format(new Date(transaction.transaction_date), 'MMM dd, yyyy')}
          </Typography>
        </Box>
        <Stack direction="row" alignItems="center" spacing={2}>
          <Typography
            variant="body2"
            fontWeight="medium"
            color={transaction.amount >= 0 ? 'success.main' : 'error.main'}
          >
            {transaction.amount >= 0 ? '+' : ''}${Math.abs(transaction.amount).toFixed(2)}
          </Typography>
          <Chip
            label={transaction.status}
            color={getStatusColor(transaction.status)}
            size="small"
          />
        </Stack>
      </Stack>
    </Paper>
  );
};

const FinancialDashboard: React.FC = () => {
  const dispatch = useDispatch<AppDispatch>();
  const {
    dashboardData,
    statistics,
    transactions,
    isLoading,
    error
  } = useSelector((state: RootState) => state.financial);

  useEffect(() => {
    dispatch(fetchDashboardData());
    dispatch(fetchStatistics());
    dispatch(fetchTransactions({ status: 'pending' }));
  }, [dispatch]);

  const handleRefresh = () => {
    dispatch(fetchDashboardData());
    dispatch(fetchStatistics());
    dispatch(fetchTransactions({}));
  };

  // Calculate metrics from dashboard data
  const monthlyIncome = dashboardData?.statistics?.income_total || 0;
  const monthlyExpenses = dashboardData?.statistics?.expense_total || 0;
  const currentBalance = dashboardData?.statistics?.net_amount || 0;
  const pendingTransactions = dashboardData?.statistics?.pending_transactions || 0;

  // Calculate trends (mock data for now)
  const incomeTrend = { value: 12.5, isPositive: true };
  const expensesTrend = { value: -8.2, isPositive: false };
  const balanceTrend = { value: 15.3, isPositive: currentBalance >= 0 };

  // Recent transactions (first 5)
  const recentTransactions = transactions.data ? transactions.data.slice(0, 5) : [];

  if (isLoading) {
    return (
      <Box>
        <Typography variant="h4" gutterBottom>
          Financial Dashboard
        </Typography>
        <LinearProgress sx={{ mt: 2 }} />
      </Box>
    );
  }

  return (
    <Box>
      <Stack
        direction="row"
        alignItems="center"
        justifyContent="space-between"
        sx={{ mb: 3 }}
      >
        <Typography variant="h4">
          Financial Dashboard
        </Typography>
        <Tooltip title="Refresh Data">
          <IconButton onClick={handleRefresh}>
            <RefreshIcon />
          </IconButton>
        </Tooltip>
      </Stack>

      {error && (
        <Paper sx={{ p: 2, mb: 3, bgcolor: 'error.light' }}>
          <Typography color="error">
            Error loading dashboard data: {typeof error === 'string' ? error : 'Unknown error'}
          </Typography>
        </Paper>
      )}

      {/* Key Metrics */}
      <Stack direction="row" spacing={3} sx={{ mb: 4, flexWrap: 'wrap' }}>
        <Box sx={{ flex: '1 1 250px', minWidth: '250px' }}>
          <MetricCard
            title="Monthly Income"
            value={monthlyIncome}
            subtitle="This month"
            icon={<TrendingUpIcon />}
            color="success"
            trend={incomeTrend}
          />
        </Box>
        <Box sx={{ flex: '1 1 250px', minWidth: '250px' }}>
          <MetricCard
            title="Monthly Expenses"
            value={monthlyExpenses}
            subtitle="This month"
            icon={<TrendingDownIcon />}
            color="error"
            trend={expensesTrend}
          />
        </Box>
        <Box sx={{ flex: '1 1 250px', minWidth: '250px' }}>
          <MetricCard
            title="Current Balance"
            value={`$${currentBalance.toLocaleString()}`}
            trend={balanceTrend}
            color="info"
            icon="💰"
          />
        </Box>
        <Box sx={{ flex: '1 1 250px', minWidth: '250px' }}>
          <MetricCard
            title="Pending Transactions"
            value={pendingTransactions}
            subtitle="Awaiting approval"
            icon={<PendingActionsIcon />}
            color="warning"
          />
        </Box>
      </Stack>

      {/* Additional Statistics */}
      {statistics && (
        <Stack direction={{ xs: 'column', md: 'row' }} spacing={3} sx={{ mb: 4 }}>
          <Box sx={{ flex: 1 }}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  Transaction Summary
                </Typography>
                <Stack spacing={2}>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2">Total Transactions:</Typography>
                    <Typography variant="body2" fontWeight="medium">
                      {statistics.total_transactions || 0}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2">Approved:</Typography>
                    <Typography variant="body2" fontWeight="medium" color="success.main">
                      {statistics.approved_transactions || 0}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2">Pending:</Typography>
                    <Typography variant="body2" fontWeight="medium" color="warning.main">
                      {statistics.pending_transactions || 0}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2">Net Amount:</Typography>
                    <Typography variant="body2" fontWeight="medium" color={statistics.net_amount >= 0 ? 'success.main' : 'error.main'}>
                      ${(statistics.net_amount || 0).toLocaleString()}
                    </Typography>
                  </Stack>
                </Stack>
              </CardContent>
            </Card>
          </Box>
          <Box sx={{ flex: 1 }}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  Amount Summary
                </Typography>
                <Stack spacing={2}>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2">Total Income:</Typography>
                    <Typography variant="body2" fontWeight="medium" color="success.main">
                      ${(statistics.income_total || 0).toLocaleString()}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2">Total Expenses:</Typography>
                    <Typography variant="body2" fontWeight="medium" color="error.main">
                      ${(statistics.expense_total || 0).toLocaleString()}
                    </Typography>
                  </Stack>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography variant="body2">Pending Amount:</Typography>
                    <Typography variant="body2" fontWeight="medium" color="warning.main">
                      ${(statistics.pending_amount || 0).toLocaleString()}
                    </Typography>
                  </Stack>
                </Stack>
              </CardContent>
            </Card>
          </Box>
        </Stack>
      )}

      {/* Recent Transactions */}
      <Card>
        <CardContent>
          <Stack
            direction="row"
            alignItems="center"
            justifyContent="space-between"
            sx={{ mb: 2 }}
          >
            <Typography variant="h6">
              Recent Transactions
            </Typography>
            <Chip
              icon={<ReceiptIcon />}
              label={`${recentTransactions.length} transactions`}
              size="small"
            />
          </Stack>
          {recentTransactions.length > 0 ? (
            <Box>
              {recentTransactions.map((transaction) => (
                <RecentTransactionItem
                  key={transaction.id}
                  transaction={transaction}
                />
              ))}
            </Box>
          ) : (
            <Paper sx={{ p: 3, textAlign: 'center', bgcolor: 'grey.50' }}>
              <Typography variant="body2" color="text.secondary">
                No recent transactions found
              </Typography>
            </Paper>
          )}
        </CardContent>
      </Card>
    </Box>
  );
};

export default FinancialDashboard;