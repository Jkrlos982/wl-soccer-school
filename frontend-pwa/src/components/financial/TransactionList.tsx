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
  Check as ApproveIcon,
  Close as RejectIcon,
  Cancel as CancelIcon,
  FilterList as FilterIcon,
  Add as AddIcon,
  Download as ExportIcon,
  Refresh as RefreshIcon,
} from '@mui/icons-material';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { es } from 'date-fns/locale';
import { format } from 'date-fns';
import { useAppDispatch, useAppSelector } from '../../store';
import {
  selectTransactions,
  selectConcepts,
  selectFinancialLoading,
  selectFinancialErrors,
  selectFinancialFilters,
} from '../../store';
import {
  fetchTransactions,
  fetchConcepts,
  setFilters,
  resetFilters,
  approveTransaction,
  rejectTransaction,
  cancelTransaction,
  deleteTransaction,
} from '../../store/financialSlice';
import { Transaction, TransactionFilters } from '../../types';

interface TransactionListProps {
  onEdit?: (transaction: Transaction) => void;
  onView?: (transaction: Transaction) => void;
  onCreate?: () => void;
}

const TransactionList: React.FC<TransactionListProps> = ({
  onEdit,
  onView,
  onCreate,
}) => {
  const dispatch = useAppDispatch();
  const transactions = useAppSelector(selectTransactions);
  const concepts = useAppSelector(selectConcepts);
  const loading = useAppSelector(selectFinancialLoading);
  const errors = useAppSelector(selectFinancialErrors);
  const filters = useAppSelector(selectFinancialFilters);

  const [showFilters, setShowFilters] = useState(false);
  const [localFilters, setLocalFilters] = useState<Partial<TransactionFilters>>({
    search: '',
    status: undefined,
    concept_id: '',
    date_from: '',
    date_to: '',
  });
  const [confirmDialog, setConfirmDialog] = useState<{
    open: boolean;
    type: 'approve' | 'reject' | 'cancel' | 'delete';
    transaction: Transaction | null;
  }>({ open: false, type: 'approve', transaction: null });
  const [actionNotes, setActionNotes] = useState('');

  // Load data on component mount
  useEffect(() => {
    dispatch(fetchTransactions(filters));
    if (concepts.length === 0) {
      dispatch(fetchConcepts());
    }
  }, [dispatch, filters, concepts.length]);

  // Handle filter changes
  const handleFilterChange = useCallback(
    (field: keyof TransactionFilters, value: any) => {
      setLocalFilters(prev => ({ ...prev, [field]: value }));
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
      concept_id: '',
      date_from: '',
      date_to: '',
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

  // Handle transaction actions
  const handleAction = useCallback(
    (type: 'approve' | 'reject' | 'cancel' | 'delete', transaction: Transaction) => {
      setConfirmDialog({ open: true, type, transaction });
      setActionNotes('');
    },
    []
  );

  const executeAction = useCallback(async () => {
    const { type, transaction } = confirmDialog;
    if (!transaction) return;

    try {
      switch (type) {
        case 'approve':
          await dispatch(approveTransaction({ id: transaction.id, notes: actionNotes }));
          break;
        case 'reject':
          await dispatch(rejectTransaction({ id: transaction.id, notes: actionNotes }));
          break;
        case 'cancel':
          await dispatch(cancelTransaction({ id: transaction.id, reason: actionNotes }));
          break;
        case 'delete':
          await dispatch(deleteTransaction(transaction.id));
          break;
      }
      setConfirmDialog({ open: false, type: 'approve', transaction: null });
    } catch (error) {
      console.error('Action failed:', error);
    }
  }, [dispatch, confirmDialog, actionNotes]);

  // Get status color
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending':
        return 'warning';
      case 'approved':
        return 'success';
      case 'rejected':
        return 'error';
      case 'cancelled':
        return 'default';
      case 'completed':
        return 'info';
      default:
        return 'default';
    }
  };

  // Get status label
  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'pending':
        return 'Pendiente';
      case 'approved':
        return 'Aprobado';
      case 'rejected':
        return 'Rechazado';
      case 'cancelled':
        return 'Cancelado';
      case 'completed':
        return 'Completado';
      default:
        return status;
    }
  };

  // Check if action is allowed
  const canPerformAction = (transaction: Transaction, action: string) => {
    switch (action) {
      case 'approve':
      case 'reject':
        return transaction.status === 'pending';
      case 'cancel':
        return ['pending', 'approved'].includes(transaction.status);
      case 'edit':
        return transaction.status === 'pending';
      case 'delete':
        return transaction.status === 'pending';
      default:
        return false;
    }
  };

  if (loading.transactions && transactions.data.length === 0) {
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
            Transacciones
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
              onClick={() => dispatch(fetchTransactions(filters))}
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
                Nueva Transacción
              </Button>
            )}
          </Stack>
        </Box>

        {/* Error Alert */}
        {errors.transactions && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {errors.transactions}
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
                      placeholder="Número, descripción..."
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
                        <MenuItem value="approved">Aprobado</MenuItem>
                        <MenuItem value="rejected">Rechazado</MenuItem>
                        <MenuItem value="cancelled">Cancelado</MenuItem>
                        <MenuItem value="completed">Completado</MenuItem>
                      </Select>
                    </FormControl>
                  </Box>
                  <Box sx={{ minWidth: 150 }}>
                    <FormControl fullWidth>
                      <InputLabel>Concepto</InputLabel>
                      <Select
                        value={localFilters.concept_id || ''}
                        onChange={(e) => handleFilterChange('concept_id', e.target.value)}
                        label="Concepto"
                      >
                        <MenuItem value="">Todos</MenuItem>
                        {concepts.map((concept) => (
                          <MenuItem key={concept.id} value={concept.id}>
                            {concept.name}
                          </MenuItem>
                        ))}
                      </Select>
                    </FormControl>
                  </Box>
                </Stack>
                <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                  <Box sx={{ minWidth: 200 }}>
                    <DatePicker
                      label="Fecha desde"
                      value={localFilters.date_from ? new Date(localFilters.date_from) : null}
                      onChange={(date) => handleFilterChange('date_from', date ? format(date, 'yyyy-MM-dd') : '')}
                      slotProps={{ textField: { fullWidth: true } }}
                    />
                  </Box>
                  <Box sx={{ minWidth: 200 }}>
                    <DatePicker
                      label="Fecha hasta"
                      value={localFilters.date_to ? new Date(localFilters.date_to) : null}
                      onChange={(date) => handleFilterChange('date_to', date ? format(date, 'yyyy-MM-dd') : '')}
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

        {/* Transactions Table */}
        <Card>
          <TableContainer>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Número</TableCell>
                  <TableCell>Concepto</TableCell>
                  <TableCell>Descripción</TableCell>
                  <TableCell align="right">Monto</TableCell>
                  <TableCell>Estado</TableCell>
                  <TableCell>Fecha</TableCell>
                  <TableCell align="center">Acciones</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {transactions.data.map((transaction) => (
                  <TableRow key={transaction.id} hover>
                    <TableCell>
                      <Typography variant="body2" fontWeight="medium">
                        {transaction.reference_number}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {transaction.financial_concept?.name || 'N/A'}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2" noWrap sx={{ maxWidth: 200 }}>
                        {transaction.description}
                      </Typography>
                    </TableCell>
                    <TableCell align="right">
                      <Typography variant="body2" fontWeight="medium">
                        ${transaction.amount.toLocaleString()}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={getStatusLabel(transaction.status)}
                        color={getStatusColor(transaction.status) as any}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {format(new Date(transaction.transaction_date), 'dd/MM/yyyy')}
                      </Typography>
                    </TableCell>
                    <TableCell align="center">
                      <Stack direction="row" spacing={0.5} justifyContent="center">
                        {onView && (
                          <Tooltip title="Ver detalles">
                            <IconButton size="small" onClick={() => onView(transaction)}>
                              <ViewIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {onEdit && canPerformAction(transaction, 'edit') && (
                          <Tooltip title="Editar">
                            <IconButton size="small" onClick={() => onEdit(transaction)}>
                              <EditIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {canPerformAction(transaction, 'approve') && (
                          <Tooltip title="Aprobar">
                            <IconButton
                              size="small"
                              color="success"
                              onClick={() => handleAction('approve', transaction)}
                            >
                              <ApproveIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {canPerformAction(transaction, 'reject') && (
                          <Tooltip title="Rechazar">
                            <IconButton
                              size="small"
                              color="error"
                              onClick={() => handleAction('reject', transaction)}
                            >
                              <RejectIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {canPerformAction(transaction, 'cancel') && (
                          <Tooltip title="Cancelar">
                            <IconButton
                              size="small"
                              color="warning"
                              onClick={() => handleAction('cancel', transaction)}
                            >
                              <CancelIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                        {canPerformAction(transaction, 'delete') && (
                          <Tooltip title="Eliminar">
                            <IconButton
                              size="small"
                              color="error"
                              onClick={() => handleAction('delete', transaction)}
                            >
                              <DeleteIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        )}
                      </Stack>
                    </TableCell>
                  </TableRow>
                ))}
                {transactions.data.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={7} align="center">
                      <Typography variant="body2" color="text.secondary">
                        No se encontraron transacciones
                      </Typography>
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </TableContainer>
          
          {/* Pagination */}
          <TablePagination
            component="div"
            count={transactions.total}
            page={(transactions.current_page || 1) - 1}
            onPageChange={handlePageChange}
            rowsPerPage={transactions.per_page || 10}
            onRowsPerPageChange={handleRowsPerPageChange}
            rowsPerPageOptions={[10, 25, 50, 100]}
            labelRowsPerPage="Filas por página:"
            labelDisplayedRows={({ from, to, count }) =>
              `${from}-${to} de ${count !== -1 ? count : `más de ${to}`}`
            }
          />
        </Card>

        {/* Confirmation Dialog */}
        <Dialog
          open={confirmDialog.open}
          onClose={() => setConfirmDialog({ open: false, type: 'approve', transaction: null })}
          maxWidth="sm"
          fullWidth
        >
          <DialogTitle>
            {confirmDialog.type === 'approve' && 'Aprobar Transacción'}
            {confirmDialog.type === 'reject' && 'Rechazar Transacción'}
            {confirmDialog.type === 'cancel' && 'Cancelar Transacción'}
            {confirmDialog.type === 'delete' && 'Eliminar Transacción'}
          </DialogTitle>
          <DialogContent>
            <Typography variant="body1" sx={{ mb: 2 }}>
              ¿Está seguro que desea {confirmDialog.type === 'approve' ? 'aprobar' : 
                confirmDialog.type === 'reject' ? 'rechazar' : 
                confirmDialog.type === 'cancel' ? 'cancelar' : 'eliminar'} la transacción{' '}
              <strong>{confirmDialog.transaction?.reference_number}</strong>?
            </Typography>
            {confirmDialog.type !== 'delete' && (
              <TextField
                fullWidth
                multiline
                rows={3}
                label={confirmDialog.type === 'cancel' ? 'Razón (opcional)' : 'Notas (opcional)'}
                value={actionNotes}
                onChange={(e) => setActionNotes(e.target.value)}
                placeholder="Ingrese observaciones..."
              />
            )}
          </DialogContent>
          <DialogActions>
            <Button
              onClick={() => setConfirmDialog({ open: false, type: 'approve', transaction: null })}
            >
              Cancelar
            </Button>
            <Button
              onClick={executeAction}
              variant="contained"
              color={confirmDialog.type === 'delete' || confirmDialog.type === 'reject' ? 'error' : 'primary'}
            >
              Confirmar
            </Button>
          </DialogActions>
        </Dialog>
      </Box>
    </LocalizationProvider>
  );
};

export default TransactionList;