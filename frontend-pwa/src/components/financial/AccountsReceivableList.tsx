import React, { useEffect, useState, useCallback } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TablePagination,
  Chip,
  IconButton,
  Button,
  TextField,
  Grid,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Tooltip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Alert,
  Skeleton,
  Stack,
  Paper,
} from '@mui/material';
import {
  Edit as EditIcon,
  Delete as DeleteIcon,
  Visibility as ViewIcon,
  Payment as PaymentIcon,
  Schedule as PlanIcon,
  FilterList as FilterIcon,
  Add as AddIcon,
  Download as ExportIcon,
  Refresh as RefreshIcon,
  Email as EmailIcon,
} from '@mui/icons-material';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { es } from 'date-fns/locale';
import { format } from 'date-fns';
import { useAppDispatch, useAppSelector } from '../../store';
import {
  selectARList,
  selectARLoading,
  selectARErrors,
  selectARFilters,
} from '../../store';
import {
  fetchAccountsReceivable,
  setFilters,
  resetFilters,
  deleteAccountReceivable,
} from '../../store/accountsReceivableSlice';
import { AccountReceivable, ARFilters } from '../../types/financial';

interface AccountsReceivableListProps {
  onEdit?: (ar: AccountReceivable) => void;
  onView?: (ar: AccountReceivable) => void;
  onCreate?: () => void;
  onPayment?: (ar: AccountReceivable) => void;
  onPaymentPlan?: (ar: AccountReceivable) => void;
}

const AccountsReceivableList: React.FC<AccountsReceivableListProps> = ({
  onEdit,
  onView,
  onCreate,
  onPayment,
  onPaymentPlan,
}) => {
  const dispatch = useAppDispatch();
  const accountsReceivable = useAppSelector(selectARList);
  const loading = useAppSelector(selectARLoading);
  const errors = useAppSelector(selectARErrors);
  const filters = useAppSelector(selectARFilters);

  const [showFilters, setShowFilters] = useState(false);
  const [localFilters, setLocalFilters] = useState<Partial<ARFilters>>({
    search: '',
    status: undefined,
    student_id: '',
    due_date_from: '',
    due_date_to: '',
    overdue_only: false,
  });
  const [confirmDialog, setConfirmDialog] = useState<{
    open: boolean;
    type: 'delete';
    ar: AccountReceivable | null;
  }>({ open: false, type: 'delete', ar: null });

  // Load data on component mount
  useEffect(() => {
    dispatch(fetchAccountsReceivable(filters));
  }, [dispatch, filters]);

  // Handle filter changes
  const handleFilterChange = useCallback(
    (field: keyof ARFilters, value: any) => {
      setLocalFilters((prev: Partial<ARFilters>) => ({ ...prev, [field]: value }));
    },
    []
  );

  // Apply filters
  const applyFilters = useCallback(() => {
    const newFilters = {
      ...filters,
      ...localFilters,
      page: 1, // Reset to first page
    };
    dispatch(setFilters(newFilters));
  }, [dispatch, filters, localFilters]);

  // Clear filters
  const clearFilters = useCallback(() => {
    setLocalFilters({
      search: '',
      status: undefined,
      student_id: '',
      due_date_from: '',
      due_date_to: '',
      overdue_only: false,
    });
    dispatch(resetFilters());
  }, [dispatch]);

  // Handle pagination
  const handlePageChange = useCallback(
    (event: unknown, newPage: number) => {
      dispatch(setFilters({ ...filters, page: newPage + 1 }));
    },
    [dispatch, filters]
  );

  const handleRowsPerPageChange = useCallback(
    (event: React.ChangeEvent<HTMLInputElement>) => {
      dispatch(
        setFilters({
          ...filters,
          per_page: parseInt(event.target.value, 10),
          page: 1,
        })
      );
    },
    [dispatch, filters]
  );

  // Handle delete action
  const handleDelete = useCallback(
    (ar: AccountReceivable) => {
      setConfirmDialog({ open: true, type: 'delete', ar });
    },
    []
  );

  const executeDelete = useCallback(async () => {
    const { ar } = confirmDialog;
    if (!ar) return;

    try {
      await dispatch(deleteAccountReceivable(ar.id));
      setConfirmDialog({ open: false, type: 'delete', ar: null });
    } catch (error) {
      console.error('Delete failed:', error);
    }
  }, [dispatch, confirmDialog]);

  // Get status color
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

  // Get status label
  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'pending':
        return 'Pendiente';
      case 'partial':
        return 'Parcial';
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

  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
    }).format(amount);
  };

  // Check if overdue
  const isOverdue = (dueDate: string) => {
    return new Date(dueDate) < new Date() && new Date(dueDate).toDateString() !== new Date().toDateString();
  };

  if (loading && accountsReceivable.data.length === 0) {
    return (
      <Box>
        <Skeleton variant="rectangular" height={200} sx={{ mb: 2 }} />
        <Skeleton variant="rectangular" height={400} />
      </Box>
    );
  }

  return (
    <LocalizationProvider dateAdapter={AdapterDateFns} adapterLocale={es}>
      <Box>
        {/* Header */}
        <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Typography variant="h4" component="h1">
            Cuentas por Cobrar
          </Typography>
          <Stack direction="row" spacing={1}>
            <Button
              variant="outlined"
              startIcon={<FilterIcon />}
              onClick={() => setShowFilters(!showFilters)}
            >
              Filtros
            </Button>
            <Button
              variant="outlined"
              startIcon={<RefreshIcon />}
              onClick={() => dispatch(fetchAccountsReceivable(filters))}
            >
              Actualizar
            </Button>
            <Button
              variant="outlined"
              startIcon={<ExportIcon />}
              onClick={() => {/* TODO: Implement export */}}
            >
              Exportar
            </Button>
            {onCreate && (
              <Button
                variant="contained"
                startIcon={<AddIcon />}
                onClick={onCreate}
              >
                Nueva Cuenta
              </Button>
            )}
          </Stack>
        </Box>

        {/* Error Alert */}
        {errors && (errors.accountsReceivable || errors.payments || errors.paymentPlans || errors.report || errors.form) && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {errors.accountsReceivable || errors.payments || errors.paymentPlans || errors.report || errors.form}
          </Alert>
        )}

        {/* Filters */}
        {showFilters && (
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Stack spacing={2}>
                <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                  <Box sx={{ flex: 1 }}>
                    <TextField
                      fullWidth
                      label="Buscar"
                      value={localFilters.search || ''}
                      onChange={(e) => handleFilterChange('search', e.target.value)}
                      placeholder="Número, estudiante, descripción..."
                    />
                  </Box>
                  <Box sx={{ minWidth: 150 }}>
                    <FormControl fullWidth>
                      <InputLabel>Estado</InputLabel>
                      <Select
                        value={localFilters.status || ''}
                        onChange={(e) => handleFilterChange('status', e.target.value)}
                        label="Estado"
                      >
                        <MenuItem value="">Todos</MenuItem>
                        <MenuItem value="pending">Pendiente</MenuItem>
                        <MenuItem value="partial">Parcial</MenuItem>
                        <MenuItem value="paid">Pagado</MenuItem>
                        <MenuItem value="overdue">Vencido</MenuItem>
                        <MenuItem value="cancelled">Cancelado</MenuItem>
                      </Select>
                    </FormControl>
                  </Box>
                  <Box sx={{ minWidth: 150 }}>
                    <TextField
                      fullWidth
                      label="ID Estudiante"
                      value={localFilters.student_id || ''}
                      onChange={(e) => handleFilterChange('student_id', e.target.value)}
                    />
                  </Box>
                </Stack>
                <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                  <Box sx={{ minWidth: 200 }}>
                    <DatePicker
                      label="Fecha desde"
                      value={localFilters.due_date_from ? new Date(localFilters.due_date_from) : null}
                      onChange={(date) => handleFilterChange('due_date_from', date ? format(date, 'yyyy-MM-dd') : '')}
                      slotProps={{ textField: { fullWidth: true } }}
                    />
                  </Box>
                  <Box sx={{ minWidth: 200 }}>
                    <DatePicker
                      label="Fecha hasta"
                      value={localFilters.due_date_to ? new Date(localFilters.due_date_to) : null}
                      onChange={(date) => handleFilterChange('due_date_to', date ? format(date, 'yyyy-MM-dd') : '')}
                      slotProps={{ textField: { fullWidth: true } }}
                    />
                  </Box>
                  <Stack direction="row" spacing={1}>
                    <Button variant="contained" onClick={applyFilters}>
                      Aplicar
                    </Button>
                    <Button variant="outlined" onClick={clearFilters}>
                      Limpiar
                    </Button>
                  </Stack>
                </Stack>
              </Stack>
            </CardContent>
          </Card>
        )}

        {/* Accounts Receivable Table */}
        <Card>
          <TableContainer>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Número</TableCell>
                  <TableCell>Estudiante</TableCell>
                  <TableCell>Descripción</TableCell>
                  <TableCell align="right">Monto Total</TableCell>
                  <TableCell align="right">Saldo Pendiente</TableCell>
                  <TableCell>Estado</TableCell>
                  <TableCell>Fecha Vencimiento</TableCell>
                  <TableCell align="center">Acciones</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {accountsReceivable.data.map((ar) => (
                  <TableRow
                    key={ar.id}
                    sx={{
                      backgroundColor: isOverdue(ar.due_date) && ar.status !== 'paid' ? 'rgba(244, 67, 54, 0.05)' : 'inherit',
                    }}
                  >
                    <TableCell>
                      <Typography variant="body2" fontWeight="medium">
                        {ar.invoice_id || ar.id}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {ar.student?.name || 'N/A'}
                      </Typography>
                      <Typography variant="caption" color="text.secondary">
                        ID: {ar.student_id}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {ar.description}
                      </Typography>
                    </TableCell>
                    <TableCell align="right">
                      <Typography variant="body2" fontWeight="medium">
                        {formatCurrency(ar.amount)}
                      </Typography>
                    </TableCell>
                    <TableCell align="right">
                      <Typography 
                        variant="body2" 
                        fontWeight="medium"
                        color={ar.balance > 0 ? 'error.main' : 'success.main'}
                      >
                        {formatCurrency(ar.balance)}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={getStatusLabel(ar.status)}
                        color={getStatusColor(ar.status) as any}
                        size="small"
                      />
                      {isOverdue(ar.due_date) && ar.status !== 'paid' && (
                        <Chip
                          label="Vencido"
                          color="error"
                          size="small"
                          sx={{ ml: 1 }}
                        />
                      )}
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {format(new Date(ar.due_date), 'dd/MM/yyyy')}
                      </Typography>
                      {isOverdue(ar.due_date) && ar.status !== 'paid' && (
                        <Typography variant="caption" color="error">
                          Vencido
                        </Typography>
                      )}
                    </TableCell>
                    <TableCell align="center">
                      <Stack direction="row" spacing={0.5} justifyContent="center">
                        {onView && (
                          <Tooltip title="Ver detalle">
                            <IconButton
                              size="small"
                              onClick={() => onView(ar)}
                            >
                              <ViewIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {onPayment && ar.balance > 0 && (
                          <Tooltip title="Registrar pago">
                            <IconButton
                              size="small"
                              onClick={() => onPayment(ar)}
                              color="primary"
                            >
                              <PaymentIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {onPaymentPlan && ar.balance > 0 && (
                          <Tooltip title="Plan de pagos">
                            <IconButton
                              size="small"
                              onClick={() => onPaymentPlan(ar)}
                              color="info"
                            >
                              <PlanIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {onEdit && ar.status === 'pending' && (
                          <Tooltip title="Editar">
                            <IconButton
                              size="small"
                              onClick={() => onEdit(ar)}
                            >
                              <EditIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {ar.status === 'pending' && (
                          <Tooltip title="Eliminar">
                            <IconButton
                              size="small"
                              onClick={() => handleDelete(ar)}
                              color="error"
                            >
                              <DeleteIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                      </Stack>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
          
          {/* Pagination */}
          <TablePagination
            component="div"
            count={accountsReceivable.total || 0}
            page={(accountsReceivable.current_page || 1) - 1}
            onPageChange={handlePageChange}
            rowsPerPage={accountsReceivable.per_page || 10}
            onRowsPerPageChange={handleRowsPerPageChange}
            rowsPerPageOptions={[5, 10, 25, 50]}
            labelRowsPerPage="Filas por página:"
            labelDisplayedRows={({ from, to, count }) =>
              `${from}-${to} de ${count !== -1 ? count : `más de ${to}`}`
            }
          />
        </Card>

        {/* Delete Confirmation Dialog */}
        <Dialog
          open={confirmDialog.open}
          onClose={() => setConfirmDialog({ open: false, type: 'delete', ar: null })}
          maxWidth="sm"
          fullWidth
        >
          <DialogTitle>
            Confirmar Eliminación
          </DialogTitle>
          <DialogContent>
            <Typography>
              ¿Está seguro de que desea eliminar la cuenta por cobrar #{confirmDialog.ar?.invoice_id || confirmDialog.ar?.id}?
            </Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
              Esta acción no se puede deshacer.
            </Typography>
          </DialogContent>
          <DialogActions>
            <Button
              onClick={() => setConfirmDialog({ open: false, type: 'delete', ar: null })}
            >
              Cancelar
            </Button>
            <Button
              onClick={executeDelete}
              color="error"
              variant="contained"
            >
              Eliminar
            </Button>
          </DialogActions>
        </Dialog>
      </Box>
    </LocalizationProvider>
  );
};

export default AccountsReceivableList;