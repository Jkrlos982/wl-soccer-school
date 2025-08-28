import React, { useEffect, useMemo } from 'react';
import {
  Box,
  Card,
  CardContent,
  CardHeader,
  Typography,
  Chip,
  LinearProgress,
  Alert,
  CircularProgress,
  Stack,
  Divider,
} from '@mui/material';
import {
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  AccountBalance as AccountBalanceIcon,
  Payment as PaymentIcon,
  Schedule as ScheduleIcon,
  Warning as WarningIcon,
  CheckCircle as CheckCircleIcon,
  Error as ErrorIcon,
} from '@mui/icons-material';
import { useAppDispatch, useAppSelector } from '../../store';
import { fetchAccountsReceivable } from '../../store/accountsReceivableSlice';
import { selectARList, selectARLoading, selectARErrors } from '../../store';
import { formatCurrency } from '../../utils';
import type { AccountReceivable } from '../../types/financial';

interface MetricCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: React.ReactNode;
  color?: 'primary' | 'secondary' | 'error' | 'warning' | 'info' | 'success';
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
  color = 'primary',
  trend,
}) => {
  return (
    <Card sx={{ height: '100%' }}>
      <CardContent>
        <Stack spacing={2}>
          <Box display="flex" alignItems="center" justifyContent="space-between">
            <Box display="flex" alignItems="center" gap={1}>
              <Box
                sx={{
                  p: 1,
                  borderRadius: 1,
                  bgcolor: `${color}.100`,
                  color: `${color}.main`,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                {icon}
              </Box>
              <Typography variant="body2" color="text.secondary">
                {title}
              </Typography>
            </Box>
            {trend && (
              <Chip
                icon={
                  trend.isPositive ? <TrendingUpIcon /> : <TrendingDownIcon />
                }
                label={`${trend.isPositive ? '+' : ''}${trend.value}%`}
                color={trend.isPositive ? 'success' : 'error'}
                size="small"
                variant="outlined"
              />
            )}
          </Box>
          
          <Box>
            <Typography variant="h4" fontWeight="bold" color={`${color}.main`}>
              {value}
            </Typography>
            {subtitle && (
              <Typography variant="body2" color="text.secondary">
                {subtitle}
              </Typography>
            )}
          </Box>
        </Stack>
      </CardContent>
    </Card>
  );
};

interface StatusDistributionProps {
  data: {
    pending: number;
    partial: number;
    paid: number;
    overdue: number;
  };
}

const StatusDistribution: React.FC<StatusDistributionProps> = ({ data }) => {
  const total = data.pending + data.partial + data.paid + data.overdue;
  
  const getPercentage = (value: number) => {
    return total > 0 ? Math.round((value / total) * 100) : 0;
  };

  const statusConfig = [
    {
      label: 'Pendientes',
      value: data.pending,
      color: 'warning',
      icon: <ScheduleIcon />,
    },
    {
      label: 'Parciales',
      value: data.partial,
      color: 'info',
      icon: <PaymentIcon />,
    },
    {
      label: 'Pagadas',
      value: data.paid,
      color: 'success',
      icon: <CheckCircleIcon />,
    },
    {
      label: 'Vencidas',
      value: data.overdue,
      color: 'error',
      icon: <ErrorIcon />,
    },
  ];

  return (
    <Card>
      <CardHeader title="Distribución por Estado" />
      <CardContent>
        <Stack spacing={3}>
          {statusConfig.map((status) => (
            <Box key={status.label}>
              <Box display="flex" alignItems="center" justifyContent="space-between" mb={1}>
                <Box display="flex" alignItems="center" gap={1}>
                  <Box
                    sx={{
                      color: `${status.color}.main`,
                      display: 'flex',
                      alignItems: 'center',
                    }}
                  >
                    {status.icon}
                  </Box>
                  <Typography variant="body2">{status.label}</Typography>
                </Box>
                <Box display="flex" alignItems="center" gap={1}>
                  <Typography variant="body2" fontWeight="medium">
                    {status.value}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    ({getPercentage(status.value)}%)
                  </Typography>
                </Box>
              </Box>
              <LinearProgress
                variant="determinate"
                value={getPercentage(status.value)}
                color={status.color as any}
                sx={{ height: 8, borderRadius: 4 }}
              />
            </Box>
          ))}
        </Stack>
      </CardContent>
    </Card>
  );
};

interface AgingAnalysisProps {
  data: {
    current: number;
    days30: number;
    days60: number;
    days90: number;
    over90: number;
  };
}

const AgingAnalysis: React.FC<AgingAnalysisProps> = ({ data }) => {
  const total = data.current + data.days30 + data.days60 + data.days90 + data.over90;
  
  const getPercentage = (value: number) => {
    return total > 0 ? Math.round((value / total) * 100) : 0;
  };

  const agingRanges = [
    {
      label: 'Corriente (0-30 días)',
      value: data.current,
      color: 'success',
    },
    {
      label: '31-60 días',
      value: data.days30,
      color: 'info',
    },
    {
      label: '61-90 días',
      value: data.days60,
      color: 'warning',
    },
    {
      label: '91-120 días',
      value: data.days90,
      color: 'error',
    },
    {
      label: 'Más de 120 días',
      value: data.over90,
      color: 'error',
    },
  ];

  return (
    <Card>
      <CardHeader title="Análisis de Antigüedad" />
      <CardContent>
        <Stack spacing={2}>
          {agingRanges.map((range) => (
            <Box key={range.label}>
              <Box display="flex" alignItems="center" justifyContent="space-between" mb={1}>
                <Typography variant="body2">{range.label}</Typography>
                <Box display="flex" alignItems="center" gap={1}>
                  <Typography variant="body2" fontWeight="medium">
                    {formatCurrency(range.value)}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    ({getPercentage(range.value)}%)
                  </Typography>
                </Box>
              </Box>
              <LinearProgress
                variant="determinate"
                value={getPercentage(range.value)}
                color={range.color as any}
                sx={{ height: 6, borderRadius: 3 }}
              />
            </Box>
          ))}
        </Stack>
      </CardContent>
    </Card>
  );
};

const CollectionDashboard: React.FC = () => {
  const dispatch = useAppDispatch();
  const accountsReceivable = useAppSelector(selectARList);
  const isLoading = useAppSelector(selectARLoading);
  const error = useAppSelector(selectARErrors);

  useEffect(() => {
    dispatch(fetchAccountsReceivable({ page: 1 }));
  }, [dispatch]);

  const metrics = useMemo(() => {
    const arData = accountsReceivable?.data || [];
    if (!arData.length) {
      return {
        totalAmount: 0,
        totalCount: 0,
        averageAmount: 0,
        collectionRate: 0,
        statusDistribution: {
          pending: 0,
          partial: 0,
          paid: 0,
          overdue: 0,
        },
        agingAnalysis: {
          current: 0,
          days30: 0,
          days60: 0,
          days90: 0,
          over90: 0,
        },
      };
    }

    const now = new Date();
    const totalAmount = arData.reduce((sum, ar) => sum + ar.balance, 0);
    const totalCount = arData.length;
    const averageAmount = totalCount > 0 ? totalAmount / totalCount : 0;

    // Status distribution
    const statusDistribution = arData.reduce(
      (acc, ar) => {
        const status = ar.status;
        if (status === 'pending') {
          // Check if overdue
          const dueDate = new Date(ar.due_date);
          if (dueDate < now) {
            acc.overdue++;
          } else {
            acc.pending++;
          }
        } else if (status === 'partial') {
          acc.partial++;
        } else if (status === 'paid') {
          acc.paid++;
        }
        return acc;
      },
      { pending: 0, partial: 0, paid: 0, overdue: 0 }
    );

    // Aging analysis
    const agingAnalysis = arData.reduce(
      (acc, ar) => {
        if (ar.status === 'paid') return acc;
        
        const dueDate = new Date(ar.due_date);
        const daysDiff = Math.floor((now.getTime() - dueDate.getTime()) / (1000 * 60 * 60 * 24));
        
        if (daysDiff <= 30) {
          acc.current += ar.balance;
        } else if (daysDiff <= 60) {
          acc.days30 += ar.balance;
        } else if (daysDiff <= 90) {
          acc.days60 += ar.balance;
        } else if (daysDiff <= 120) {
          acc.days90 += ar.balance;
        } else {
          acc.over90 += ar.balance;
        }
        
        return acc;
      },
      { current: 0, days30: 0, days60: 0, days90: 0, over90: 0 }
    );

    // Collection rate (paid accounts / total accounts)
    const collectionRate = totalCount > 0 ? Math.round((statusDistribution.paid / totalCount) * 100) : 0;

    return {
      totalAmount,
      totalCount,
      averageAmount,
      collectionRate,
      statusDistribution,
      agingAnalysis,
    };
  }, [accountsReceivable]);

  if (isLoading.accountsReceivable) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight={400}>
        <CircularProgress />
      </Box>
    );
  }

  if (error?.accountsReceivable) {
    return (
      <Alert severity="error">
        Error al cargar los datos: {error.accountsReceivable}
      </Alert>
    );
  }

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        Dashboard de Cobranza
      </Typography>
      
      <Stack spacing={3}>
        {/* Key Metrics */}
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={3}>
          <Box flex={1}>
            <MetricCard
              title="Saldo Total"
              value={formatCurrency(metrics.totalAmount)}
              subtitle="Por cobrar"
              icon={<AccountBalanceIcon />}
              color="primary"
            />
          </Box>
          
          <Box flex={1}>
            <MetricCard
              title="Total Cuentas"
              value={metrics.totalCount}
              subtitle="Activas"
              icon={<ScheduleIcon />}
              color="info"
            />
          </Box>
          
          <Box flex={1}>
            <MetricCard
              title="Promedio"
              value={formatCurrency(metrics.averageAmount)}
              subtitle="Por cuenta"
              icon={<PaymentIcon />}
              color="secondary"
            />
          </Box>
          
          <Box flex={1}>
            <MetricCard
              title="Tasa de Cobranza"
              value={`${metrics.collectionRate}%`}
              subtitle="Cuentas pagadas"
              icon={<CheckCircleIcon />}
              color={metrics.collectionRate >= 80 ? 'success' : metrics.collectionRate >= 60 ? 'warning' : 'error'}
            />
          </Box>
        </Stack>
        
        {/* Status Distribution */}
        <Stack direction={{ xs: 'column', md: 'row' }} spacing={3}>
          <Box flex={1}>
            <StatusDistribution data={metrics.statusDistribution} />
          </Box>
          
          {/* Aging Analysis */}
          <Box flex={1}>
            <AgingAnalysis data={metrics.agingAnalysis} />
          </Box>
        </Stack>
        
        {/* Summary Cards */}
        <Box>
          <Card>
            <CardHeader title="Resumen Ejecutivo" />
            <CardContent>
              <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} flexWrap="wrap">
                <Box flex={1} textAlign="center" p={2}>
                  <Typography variant="h6" color="error.main">
                    {metrics.statusDistribution.overdue}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    Cuentas Vencidas
                  </Typography>
                </Box>
                
                <Box flex={1} textAlign="center" p={2}>
                  <Typography variant="h6" color="warning.main">
                    {formatCurrency(metrics.agingAnalysis.over90)}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    Más de 120 días
                  </Typography>
                </Box>
                
                <Box flex={1} textAlign="center" p={2}>
                  <Typography variant="h6" color="success.main">
                    {metrics.statusDistribution.paid}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    Cuentas Pagadas
                  </Typography>
                </Box>
                
                <Box flex={1} textAlign="center" p={2}>
                  <Typography variant="h6" color="info.main">
                    {formatCurrency(metrics.agingAnalysis.current)}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    Saldo Corriente
                  </Typography>
                </Box>
              </Stack>
            </CardContent>
          </Card>
        </Box>
      </Stack>
    </Box>
  );
};

export default CollectionDashboard;