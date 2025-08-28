import React, { useState, useEffect, useCallback } from 'react';
import {
  Box,
  Container,
  Typography,
  Tabs,
  Tab,
  Paper,
  Stack,
  Fab,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Alert,
  Snackbar,
  Breadcrumbs,
  Link,
} from '@mui/material';
import {
  Add as AddIcon,
  Dashboard as DashboardIcon,
  List as ListIcon,
  Search as SearchIcon,
  Payment as PaymentIcon,
  Schedule as ScheduleIcon,
  Home as HomeIcon,
} from '@mui/icons-material';
import { useAppDispatch, useAppSelector } from '../store';
import {
  selectARList,
  selectSelectedAR,
  selectARLoading,
  selectARErrors,
  selectARFilters,
} from '../store';
import {
  fetchAccountsReceivable,
  setSelectedAR,
  resetFilters,
} from '../store/accountsReceivableSlice';
import { ARFilters, AccountReceivable } from '../types/financial';
import CollectionDashboard from '../components/financial/CollectionDashboard';
import AccountsReceivableList from '../components/financial/AccountsReceivableList';
import AccountReceivableDetail from '../components/financial/AccountReceivableDetail';
import SearchAndFilters from '../components/financial/SearchAndFilters';
import PaymentForm from '../components/financial/PaymentForm';
import PaymentPlanForm from '../components/financial/PaymentPlanForm';

interface TabPanelProps {
  children?: React.ReactNode;
  index: number;
  value: number;
}

function TabPanel(props: TabPanelProps) {
  const { children, value, index, ...other } = props;

  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`accounts-receivable-tabpanel-${index}`}
      aria-labelledby={`accounts-receivable-tab-${index}`}
      {...other}
    >
      {value === index && (
        <Box sx={{ py: 3 }}>
          {children}
        </Box>
      )}
    </div>
  );
}

function a11yProps(index: number) {
  return {
    id: `accounts-receivable-tab-${index}`,
    'aria-controls': `accounts-receivable-tabpanel-${index}`,
  };
}

const AccountsReceivablePage: React.FC = () => {
  const dispatch = useAppDispatch();
  const arData = useAppSelector(selectARList);
  const selectedAR = useAppSelector(selectSelectedAR);
  const loading = useAppSelector(selectARLoading);
  const errors = useAppSelector(selectARErrors);
  const currentFilters = useAppSelector(selectARFilters);

  const [activeTab, setActiveTab] = useState(0);
  const [showPaymentDialog, setShowPaymentDialog] = useState(false);
  const [showPaymentPlanDialog, setShowPaymentPlanDialog] = useState(false);
  const [showDetailDialog, setShowDetailDialog] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [snackbarSeverity, setSnackbarSeverity] = useState<'success' | 'error' | 'warning' | 'info'>('info');
  const [showSnackbar, setShowSnackbar] = useState(false);

  // Mock data for students and concepts (in a real app, these would come from API)
  const students = [
    { id: '1', name: 'Juan Pérez', email: 'juan.perez@email.com' },
    { id: '2', name: 'María García', email: 'maria.garcia@email.com' },
    { id: '3', name: 'Carlos López', email: 'carlos.lopez@email.com' },
  ];

  const concepts = [
    { id: '1', name: 'Matrícula', description: 'Matrícula anual' },
    { id: '2', name: 'Pensión', description: 'Pensión mensual' },
    { id: '3', name: 'Transporte', description: 'Servicio de transporte' },
    { id: '4', name: 'Uniformes', description: 'Uniformes escolares' },
  ];

  const savedFilters = [
    {
      id: '1',
      name: 'Vencidas este mes',
      filters: {
        overdue_only: true,
        due_date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        due_date_to: new Date().toISOString().split('T')[0],
        page: 1,
        per_page: 10,
        sort_by: 'due_date',
        sort_order: 'asc',
      } as ARFilters,
    },
    {
      id: '2',
      name: 'Matrículas pendientes',
      filters: {
        concept_id: '1',
        status: 'pending',
        page: 1,
        per_page: 10,
        sort_by: 'created_at',
        sort_order: 'desc',
      } as ARFilters,
    },
  ];

  // Load initial data
  useEffect(() => {
    dispatch(fetchAccountsReceivable(currentFilters));
  }, [dispatch, currentFilters]);

  // Handle tab change
  const handleTabChange = useCallback((event: React.SyntheticEvent, newValue: number) => {
    setActiveTab(newValue);
  }, []);

  // Handle AR selection
  const handleARSelect = useCallback((ar: AccountReceivable) => {
    dispatch(setSelectedAR(ar));
    setShowDetailDialog(true);
  }, [dispatch]);

  // Handle filters applied
  const handleFiltersApplied = useCallback((filters: ARFilters) => {
    // Filters are already applied in SearchAndFilters component
    // This callback can be used for additional logic if needed
    console.log('Filters applied:', filters);
  }, []);

  // Handle save filters
  const handleSaveFilters = useCallback((name: string, filters: ARFilters) => {
    // In a real app, this would save to backend
    setSnackbarMessage(`Filtros "${name}" guardados exitosamente`);
    setSnackbarSeverity('success');
    setShowSnackbar(true);
  }, []);

  // Handle payment success
  const handlePaymentSuccess = useCallback(() => {
    setShowPaymentDialog(false);
    setSnackbarMessage('Pago registrado exitosamente');
    setSnackbarSeverity('success');
    setShowSnackbar(true);
    
    // Refresh data
    dispatch(fetchAccountsReceivable(currentFilters));
  }, [dispatch, currentFilters]);

  // Handle payment plan success
  const handlePaymentPlanSuccess = useCallback(() => {
    setShowPaymentPlanDialog(false);
    setSnackbarMessage('Plan de pago creado exitosamente');
    setSnackbarSeverity('success');
    setShowSnackbar(true);
    
    // Refresh data
    dispatch(fetchAccountsReceivable(currentFilters));
  }, [dispatch, currentFilters]);

  // Handle dialog close
  const handleCloseDialogs = useCallback(() => {
    setShowPaymentDialog(false);
    setShowPaymentPlanDialog(false);
    setShowDetailDialog(false);
    dispatch(setSelectedAR(null));
  }, [dispatch]);

  // Handle snackbar close
  const handleSnackbarClose = useCallback(() => {
    setShowSnackbar(false);
  }, []);

  return (
    <Container maxWidth="xl" sx={{ py: 3 }}>
      {/* Breadcrumbs */}
      <Breadcrumbs aria-label="breadcrumb" sx={{ mb: 2 }}>
        <Link
          underline="hover"
          sx={{ display: 'flex', alignItems: 'center' }}
          color="inherit"
          href="/"
        >
          <HomeIcon sx={{ mr: 0.5 }} fontSize="inherit" />
          Inicio
        </Link>
        <Link
          underline="hover"
          sx={{ display: 'flex', alignItems: 'center' }}
          color="inherit"
          href="/financial"
        >
          Financiero
        </Link>
        <Typography
          sx={{ display: 'flex', alignItems: 'center' }}
          color="text.primary"
        >
          Cuentas por Cobrar
        </Typography>
      </Breadcrumbs>

      {/* Page Header */}
      <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
        <Typography variant="h4" component="h1">
          Cuentas por Cobrar
        </Typography>
        
        <Stack direction="row" spacing={1}>
          <Button
            variant="outlined"
            startIcon={<PaymentIcon />}
            onClick={() => setShowPaymentDialog(true)}
            disabled={!selectedAR}
          >
            Registrar Pago
          </Button>
          <Button
            variant="outlined"
            startIcon={<ScheduleIcon />}
            onClick={() => setShowPaymentPlanDialog(true)}
            disabled={!selectedAR}
          >
            Plan de Pago
          </Button>
        </Stack>
      </Stack>

      {/* Error Alert */}
      {errors.accountsReceivable && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {errors.accountsReceivable}
        </Alert>
      )}

      {/* Main Content */}
      <Paper sx={{ width: '100%' }}>
        {/* Tabs */}
        <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
          <Tabs value={activeTab} onChange={handleTabChange} aria-label="cuentas por cobrar tabs">
            <Tab
              label="Dashboard"
              icon={<DashboardIcon />}
              iconPosition="start"
              {...a11yProps(0)}
            />
            <Tab
              label="Lista de Cuentas"
              icon={<ListIcon />}
              iconPosition="start"
              {...a11yProps(1)}
            />
            <Tab
              label="Búsqueda y Filtros"
              icon={<SearchIcon />}
              iconPosition="start"
              {...a11yProps(2)}
            />
          </Tabs>
        </Box>

        {/* Tab Panels */}
        <TabPanel value={activeTab} index={0}>
          <CollectionDashboard />
        </TabPanel>

        <TabPanel value={activeTab} index={1}>
          <AccountsReceivableList
            onView={handleARSelect}
            onPayment={(ar) => {
              dispatch(setSelectedAR(ar));
              setShowPaymentDialog(true);
            }}
            onPaymentPlan={(ar) => {
              dispatch(setSelectedAR(ar));
              setShowPaymentPlanDialog(true);
            }}
          />
        </TabPanel>

        <TabPanel value={activeTab} index={2}>
          <SearchAndFilters
            onFiltersApplied={handleFiltersApplied}
            students={students}
            concepts={concepts}
            savedFilters={savedFilters}
            onSaveFilters={handleSaveFilters}
          />
          
          {/* Results below filters */}
          <Box sx={{ mt: 3 }}>
            <AccountsReceivableList
              onView={handleARSelect}
              onPayment={(ar) => {
                dispatch(setSelectedAR(ar));
                setShowPaymentDialog(true);
              }}
              onPaymentPlan={(ar) => {
                dispatch(setSelectedAR(ar));
                setShowPaymentPlanDialog(true);
              }}
            />
          </Box>
        </TabPanel>
      </Paper>

      {/* Floating Action Button */}
      <Fab
        color="primary"
        aria-label="add"
        sx={{
          position: 'fixed',
          bottom: 16,
          right: 16,
        }}
        onClick={() => {
          // In a real app, this would open a form to create new AR
          setSnackbarMessage('Funcionalidad de crear nueva cuenta por implementar');
          setSnackbarSeverity('info');
          setShowSnackbar(true);
        }}
      >
        <AddIcon />
      </Fab>

      {/* Account Receivable Detail Dialog */}
      <Dialog
        open={showDetailDialog}
        onClose={handleCloseDialogs}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>
          Detalle de Cuenta por Cobrar
        </DialogTitle>
        <DialogContent>
          {selectedAR && (
            <AccountReceivableDetail
              arId={selectedAR.id}
              onPayment={() => {
                setShowDetailDialog(false);
                setShowPaymentDialog(true);
              }}
              onPaymentPlan={() => {
                setShowDetailDialog(false);
                setShowPaymentPlanDialog(true);
              }}
            />
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseDialogs}>Cerrar</Button>
        </DialogActions>
      </Dialog>

      {/* Payment Dialog */}
      {selectedAR && (
        <PaymentForm
          accountReceivable={selectedAR}
          open={showPaymentDialog}
          onClose={handleCloseDialogs}
          onSuccess={handlePaymentSuccess}
        />
      )}

      {/* Payment Plan Dialog */}
      <Dialog
        open={showPaymentPlanDialog}
        onClose={handleCloseDialogs}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>
          Crear Plan de Pago
        </DialogTitle>
        <DialogContent>
          {selectedAR && (
            <PaymentPlanForm
              accountReceivable={selectedAR}
              onSuccess={handlePaymentPlanSuccess}
              onCancel={handleCloseDialogs}
            />
          )}
        </DialogContent>
      </Dialog>

      {/* Snackbar for notifications */}
      <Snackbar
        open={showSnackbar}
        autoHideDuration={6000}
        onClose={handleSnackbarClose}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
      >
        <Alert
          onClose={handleSnackbarClose}
          severity={snackbarSeverity}
          sx={{ width: '100%' }}
        >
          {snackbarMessage}
        </Alert>
      </Snackbar>
    </Container>
  );
};

export default AccountsReceivablePage;