import React, { useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardContent,
  CardHeader,
  Typography,
  Button,
  Stack,
  Chip,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Divider,
  Alert,
  CircularProgress,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Menu,
  MenuItem,
  IconButton,
  Tooltip,
} from '@mui/material';
import {
  ArrowBack as ArrowBackIcon,
  Payment as PaymentIcon,
  Schedule as ScheduleIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Print as PrintIcon,
  MoreVert as MoreVertIcon,
  AccountBalance as AccountBalanceIcon,
  Person as PersonIcon,
  CalendarToday as CalendarIcon,
  Description as DescriptionIcon,
} from '@mui/icons-material';
import { useAppDispatch, useAppSelector } from '../../store';
import {
  selectSelectedAR,
  selectARLoading,
  selectARErrors,
  selectARPayments,
  selectARPaymentPlans,
} from '../../store';
import {
  fetchAccountReceivable,
  fetchPayments,
  fetchPaymentPlans,
  deleteAccountReceivable,
} from '../../store/accountsReceivableSlice';
import { AccountReceivable, Payment, PaymentPlan } from '../../types/financial';

interface AccountReceivableDetailProps {
  arId: string;
  onBack?: () => void;
  onEdit?: (ar: AccountReceivable) => void;
  onPayment?: (ar: AccountReceivable) => void;
  onPaymentPlan?: (ar: AccountReceivable) => void;
}

const AccountReceivableDetail: React.FC<AccountReceivableDetailProps> = ({
  arId,
  onBack,
  onEdit,
  onPayment,
  onPaymentPlan,
}) => {
  const dispatch = useAppDispatch();
  const selectedAR = useAppSelector(selectSelectedAR);
  const loading = useAppSelector(selectARLoading);
  const errors = useAppSelector(selectARErrors);
  const payments = useAppSelector(selectARPayments);
  const paymentPlans = useAppSelector(selectARPaymentPlans);

  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [menuAnchor, setMenuAnchor] = useState<null | HTMLElement>(null);
  const [isDeleting, setIsDeleting] = useState(false);

  useEffect(() => {
    if (arId) {
      dispatch(fetchAccountReceivable(arId));
      dispatch(fetchPayments({ account_receivable_id: arId }));
      dispatch(fetchPaymentPlans(arId));
    }
  }, [dispatch, arId]);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending':
        return 'warning';
      case 'partial':
        return 'info';
      case 'paid':
        return 'success';
      case 'overdue':
        return 'error';
      case 'cancelled':
        return 'default';
      default:
        return 'default';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'pending':
        return 'Pendiente';
      case 'partial':
        return 'Pago Parcial';
      case 'paid':
        return 'Pagado';
      case 'overdue':
        return 'Vencido';
      case 'cancelled':
        return 'Cancelado';
      default:
        return status;
    }
  };

  const isOverdue = (dueDate: string) => {
    return new Date(dueDate) < new Date() && selectedAR?.status !== 'paid';
  };

  const handleMenuClick = (event: React.MouseEvent<HTMLElement>) => {
    setMenuAnchor(event.currentTarget);
  };

  const handleMenuClose = () => {
    setMenuAnchor(null);
  };

  const handleDelete = async () => {
    if (!selectedAR) return;
    
    setIsDeleting(true);
    try {
      await dispatch(deleteAccountReceivable(selectedAR.id)).unwrap();
      setDeleteDialogOpen(false);
      if (onBack) onBack();
    } catch (error) {
      console.error('Error deleting account receivable:', error);
    } finally {
      setIsDeleting(false);
    }
  };

  const handlePrint = () => {
    window.print();
    handleMenuClose();
  };

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight={400}>
        <CircularProgress />
      </Box>
    );
  }

  if (errors.accountsReceivable) {
    return (
      <Alert severity="error" sx={{ mb: 2 }}>
        {errors.accountsReceivable}
      </Alert>
    );
  }

  if (!selectedAR) {
    return (
      <Alert severity="info" sx={{ mb: 2 }}>
        No se encontró la cuenta por cobrar solicitada.
      </Alert>
    );
  }

  return (
    <Box>
      {/* Header */}
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Box display="flex" alignItems="center" gap={2}>
          {onBack && (
            <IconButton onClick={onBack} size="small">
              <ArrowBackIcon />
            </IconButton>
          )}
          <Typography variant="h5" component="h1">
            Cuenta por Cobrar #{selectedAR.invoice_id || selectedAR.id}
          </Typography>
          <Chip
            label={getStatusLabel(selectedAR.status)}
            color={getStatusColor(selectedAR.status) as any}
            size="small"
          />
          {isOverdue(selectedAR.due_date) && (
            <Chip label="Vencido" color="error" size="small" />
          )}
        </Box>
        
        <Box display="flex" gap={1}>
          {selectedAR.status !== 'paid' && selectedAR.status !== 'cancelled' && (
            <>
              <Button
                variant="contained"
                startIcon={<PaymentIcon />}
                onClick={() => onPayment?.(selectedAR)}
                color="primary"
              >
                Registrar Pago
              </Button>
              <Button
                variant="outlined"
                startIcon={<ScheduleIcon />}
                onClick={() => onPaymentPlan?.(selectedAR)}
              >
                Plan de Pago
              </Button>
            </>
          )}
          <Button
            variant="outlined"
            startIcon={<EditIcon />}
            onClick={() => onEdit?.(selectedAR)}
          >
            Editar
          </Button>
          <IconButton onClick={handleMenuClick}>
            <MoreVertIcon />
          </IconButton>
        </Box>
      </Box>

      <Stack spacing={3}>
        {/* Main Information Card */}
        <Card>
          <CardHeader
            avatar={<AccountBalanceIcon color="primary" />}
            title="Información General"
          />
          <CardContent>
            <Stack spacing={2}>
              <Box display="flex" gap={4}>
                <Box flex={1}>
                  <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                    <PersonIcon fontSize="small" sx={{ mr: 1, verticalAlign: 'middle' }} />
                    Estudiante
                  </Typography>
                  <Typography variant="body1">
                    {selectedAR.student?.name || 'N/A'}
                  </Typography>
                </Box>
                <Box flex={1}>
                  <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                    <DescriptionIcon fontSize="small" sx={{ mr: 1, verticalAlign: 'middle' }} />
                    Concepto
                  </Typography>
                  <Typography variant="body1">
                    {selectedAR.concept?.name || 'N/A'}
                  </Typography>
                </Box>
              </Box>
              
              <Divider />
              
              <Box display="flex" gap={4}>
                <Box flex={1}>
                  <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                    Descripción
                  </Typography>
                  <Typography variant="body1">
                    {selectedAR.description || 'Sin descripción'}
                  </Typography>
                </Box>
                <Box flex={1}>
                  <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                    <CalendarIcon fontSize="small" sx={{ mr: 1, verticalAlign: 'middle' }} />
                    Fecha de Vencimiento
                  </Typography>
                  <Typography 
                    variant="body1"
                    color={isOverdue(selectedAR.due_date) ? 'error.main' : 'text.primary'}
                  >
                    {formatDate(selectedAR.due_date)}
                  </Typography>
                </Box>
              </Box>
              
              <Divider />
              
              <Box display="flex" gap={4}>
                <Box flex={1}>
                  <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                    Monto Total
                  </Typography>
                  <Typography variant="h6" color="primary.main">
                    {formatCurrency(selectedAR.amount)}
                  </Typography>
                </Box>
                <Box flex={1}>
                  <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                    Saldo Pendiente
                  </Typography>
                  <Typography 
                    variant="h6" 
                    color={selectedAR.balance > 0 ? 'error.main' : 'success.main'}
                  >
                    {formatCurrency(selectedAR.balance)}
                  </Typography>
                </Box>
              </Box>
            </Stack>
          </CardContent>
        </Card>

        {/* Payments History */}
        {Array.isArray(payments) && payments.length > 0 && (
          <Card>
            <CardHeader title="Historial de Pagos" />
            <CardContent>
              <TableContainer>
                <Table>
                  <TableHead>
                    <TableRow>
                      <TableCell>Fecha</TableCell>
                      <TableCell>Monto</TableCell>
                      <TableCell>Método</TableCell>
                      <TableCell>Referencia</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {payments.map((payment: Payment) => (
                      <TableRow key={payment.id}>
                        <TableCell>{formatDate(payment.payment_date)}</TableCell>
                        <TableCell>{formatCurrency(payment.amount)}</TableCell>
                        <TableCell>{payment.payment_method}</TableCell>
                        <TableCell>{payment.reference_number || 'N/A'}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </TableContainer>
            </CardContent>
          </Card>
        )}

        {/* Payment Plans */}
        {Array.isArray(paymentPlans) && paymentPlans.length > 0 && (
          <Card>
            <CardHeader title="Planes de Pago" />
            <CardContent>
              {paymentPlans.map((plan: PaymentPlan) => (
                <Box key={plan.id} mb={2}>
                  <Box display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                    <Typography variant="subtitle1">
                      Plan #{plan.id}
                    </Typography>
                    <Chip
                      label={getStatusLabel(plan.status)}
                      color={getStatusColor(plan.status) as any}
                      size="small"
                    />
                  </Box>
                  <Typography variant="body2" color="text.secondary" gutterBottom>
                    {plan.installments} cuotas - Total: {formatCurrency(plan.total_amount)}
                  </Typography>
                  {plan.installment_payments && plan.installment_payments.length > 0 && (
                    <TableContainer component={Paper} variant="outlined" sx={{ mt: 1 }}>
                      <Table size="small">
                        <TableHead>
                          <TableRow>
                            <TableCell>Cuota</TableCell>
                            <TableCell>Fecha Vencimiento</TableCell>
                            <TableCell>Monto</TableCell>
                            <TableCell>Estado</TableCell>
                          </TableRow>
                        </TableHead>
                        <TableBody>
                          {plan.installment_payments.map((installment, index) => (
                            <TableRow key={installment.id}>
                              <TableCell>{installment.installment_number}</TableCell>
                              <TableCell>{formatDate(installment.due_date)}</TableCell>
                              <TableCell>{formatCurrency(installment.amount)}</TableCell>
                              <TableCell>
                                <Chip
                                  label={getStatusLabel(installment.status)}
                                  color={getStatusColor(installment.status) as any}
                                  size="small"
                                />
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </TableContainer>
                  )}
                </Box>
              ))}
            </CardContent>
          </Card>
        )}
      </Stack>

      {/* Actions Menu */}
      <Menu
        anchorEl={menuAnchor}
        open={Boolean(menuAnchor)}
        onClose={handleMenuClose}
      >
        <MenuItem onClick={handlePrint}>
          <PrintIcon sx={{ mr: 1 }} />
          Imprimir
        </MenuItem>
        <MenuItem 
          onClick={() => {
            setDeleteDialogOpen(true);
            handleMenuClose();
          }}
          sx={{ color: 'error.main' }}
        >
          <DeleteIcon sx={{ mr: 1 }} />
          Eliminar
        </MenuItem>
      </Menu>

      {/* Delete Confirmation Dialog */}
      <Dialog open={deleteDialogOpen} onClose={() => setDeleteDialogOpen(false)}>
        <DialogTitle>Confirmar Eliminación</DialogTitle>
        <DialogContent>
          <Typography>
            ¿Está seguro de que desea eliminar esta cuenta por cobrar? Esta acción no se puede deshacer.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDeleteDialogOpen(false)}>Cancelar</Button>
          <Button 
            onClick={handleDelete} 
            color="error" 
            disabled={isDeleting}
            startIcon={isDeleting ? <CircularProgress size={16} /> : <DeleteIcon />}
          >
            {isDeleting ? 'Eliminando...' : 'Eliminar'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default AccountReceivableDetail;