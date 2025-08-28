import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControl,
  IconButton,
  InputLabel,
  MenuItem,
  Paper,
  Select,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TextField,
  Typography,
  Chip,
  Tooltip,
  Stack
} from '@mui/material';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Search as SearchIcon,
  Refresh as RefreshIcon
} from '@mui/icons-material';
import { CircularProgress } from '@mui/material';
import { useDispatch, useSelector } from 'react-redux';
import { Formik, Form, Field } from 'formik';
import * as Yup from 'yup';
import { format } from 'date-fns';
import { RootState, AppDispatch } from '../../store';
import {
  fetchAccounts,
  createAccount,
  updateAccount,
  deleteAccount
} from '../../store/financialSlice';
import { Account, CreateAccountData, UpdateAccountData } from '../../types/financial';

// Validation schema with English messages (consistent with centralized schema structure)
const validationSchema = Yup.object({
  name: Yup.string()
    .required('Account name is required')
    .min(2, 'Name must be at least 2 characters')
    .max(100, 'Name must not exceed 100 characters'),
  account_number: Yup.string()
    .optional()
    .matches(/^[A-Z0-9-]*$/, 'Account number can only contain uppercase letters, numbers, and hyphens')
    .max(50, 'Account number must not exceed 50 characters'),
  type: Yup.string()
    .required('Account type is required')
    .oneOf(['asset', 'liability', 'equity', 'income', 'expense'], 'Invalid account type'),
  description: Yup.string()
    .optional()
    .max(500, 'Description must not exceed 500 characters'),
  balance: Yup.number()
    .optional()
    .min(-999999999.99, 'Balance is too low')
    .max(999999999.99, 'Balance is too high')
});

const AccountManager: React.FC = () => {
  const dispatch = useDispatch<AppDispatch>();
  const {
    accounts,
    isLoading,
    error
  } = useSelector((state: RootState) => state.financial);

  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingAccount, setEditingAccount] = useState<Account | null>(null);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [searchTerm, setSearchTerm] = useState('');
  const [typeFilter, setTypeFilter] = useState<Account['type'] | ''>('');
  const [statusFilter, setStatusFilter] = useState<boolean | ''>('');

  useEffect(() => {
    dispatch(fetchAccounts());
  }, [dispatch]);

  // Filter accounts locally
  const filteredAccounts = accounts.filter((account) => {
    const matchesSearch = account.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         (account.account_number && account.account_number.toLowerCase().includes(searchTerm.toLowerCase())) ||
                         (account.description && account.description.toLowerCase().includes(searchTerm.toLowerCase()));
    const matchesType = !typeFilter || account.type === typeFilter;
    const matchesStatus = statusFilter === '' || account.is_active === statusFilter;
    return matchesSearch && matchesType && matchesStatus;
  });

  // Paginated accounts
  const paginatedAccounts = filteredAccounts.slice(
    page * rowsPerPage,
    page * rowsPerPage + rowsPerPage
  );

  const handleSubmit = async (values: CreateAccountData | UpdateAccountData) => {
    try {
      if (editingAccount) {
        await dispatch(updateAccount({ id: editingAccount.id, data: values as UpdateAccountData })).unwrap();
      } else {
        await dispatch(createAccount(values as CreateAccountData)).unwrap();
      }
      setDialogOpen(false);
      setEditingAccount(null);
    } catch (error) {
      console.error('Error saving account:', error);
    }
  };

  const handleEdit = (account: Account) => {
    setEditingAccount(account);
    setDialogOpen(true);
  };

  const handleDelete = async (id: string) => {
    if (window.confirm('Are you sure you want to delete this account?')) {
      try {
        await dispatch(deleteAccount(id)).unwrap();
      } catch (error) {
        console.error('Error deleting account:', error);
      }
    }
  };

  const handleCloseDialog = () => {
    setDialogOpen(false);
    setEditingAccount(null);
  };

  const getInitialValues = (): CreateAccountData => {
    if (editingAccount) {
      return {
        name: editingAccount.name,
        account_number: editingAccount.account_number || '',
        type: editingAccount.type,
        description: editingAccount.description || '',
        balance: editingAccount.balance
      };
    }
    return {
      name: '',
      account_number: '',
      type: 'asset',
      description: '',
      balance: 0
    };
  };

  const getAccountTypeColor = (type: Account['type']) => {
    const colors = {
      asset: 'primary',
      liability: 'error',
      equity: 'info',
      income: 'success',
      expense: 'warning'
    } as const;
    return colors[type] || 'default';
  };

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        Account Management
      </Typography>

      {/* Filters */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems="center">
            <TextField
               label="Search accounts"
               value={searchTerm}
               onChange={(e) => setSearchTerm(e.target.value)}
               InputProps={{
                 startAdornment: <SearchIcon />
               }}
               sx={{ minWidth: 200 }}
             />
            <FormControl sx={{ minWidth: 150 }}>
               <InputLabel>Account Type</InputLabel>
               <Select
                 value={typeFilter}
                 onChange={(e) => setTypeFilter(e.target.value as Account['type'] | '')}
                 label="Account Type"
               >
                 <MenuItem value="">All Types</MenuItem>
                 <MenuItem value="asset">Asset</MenuItem>
                 <MenuItem value="liability">Liability</MenuItem>
                 <MenuItem value="equity">Equity</MenuItem>
                 <MenuItem value="income">Income</MenuItem>
                 <MenuItem value="expense">Expense</MenuItem>
               </Select>
             </FormControl>
            <FormControl sx={{ minWidth: 120 }}>
               <InputLabel>Status</InputLabel>
               <Select
                 value={statusFilter === '' ? '' : statusFilter.toString()}
                 onChange={(e) => {
                   const value = e.target.value;
                   setStatusFilter(value === '' ? '' : value === 'true');
                 }}
                 label="Status"
               >
                 <MenuItem value="">All</MenuItem>
                 <MenuItem value="true">Active</MenuItem>
                 <MenuItem value="false">Inactive</MenuItem>
               </Select>
             </FormControl>
            <Box sx={{ flexGrow: 1 }} />
            <Button
              variant="contained"
              startIcon={<AddIcon />}
              onClick={() => setDialogOpen(true)}
            >
              Add Account
            </Button>
            <Tooltip title="Refresh">
               <IconButton onClick={() => dispatch(fetchAccounts())}>
                 <RefreshIcon />
               </IconButton>
             </Tooltip>
          </Stack>
        </CardContent>
      </Card>

      {/* Loading State */}
      {isLoading && (
        <Box display="flex" justifyContent="center" p={2}>
          <CircularProgress />
        </Box>
      )}

      {/* Accounts Table */}
      <Card>
        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Name</TableCell>
                <TableCell>Account Number</TableCell>
                <TableCell>Type</TableCell>
                <TableCell>Balance</TableCell>
                <TableCell>Status</TableCell>
                <TableCell>Created</TableCell>
                <TableCell>Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
               {paginatedAccounts.map((account) => (
                <TableRow key={account.id}>
                  <TableCell>
                    <Box>
                      <Typography variant="body2" fontWeight="medium">
                        {account.name}
                      </Typography>
                      {account.description && (
                        <Typography variant="caption" color="text.secondary">
                          {account.description}
                        </Typography>
                      )}
                    </Box>
                  </TableCell>
                  <TableCell>{account.account_number || '-'}</TableCell>
                  <TableCell>
                    <Chip
                      label={account.type}
                      color={getAccountTypeColor(account.type)}
                      size="small"
                    />
                  </TableCell>
                  <TableCell>
                    <Typography
                      color={account.balance >= 0 ? 'success.main' : 'error.main'}
                      fontWeight="medium"
                    >
                      ${account.balance.toFixed(2)}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    <Chip
                      label={account.is_active ? 'Active' : 'Inactive'}
                      color={account.is_active ? 'success' : 'default'}
                      size="small"
                    />
                  </TableCell>
                  <TableCell>
                    {format(new Date(account.created_at), 'MMM dd, yyyy')}
                  </TableCell>
                  <TableCell>
                    <Stack direction="row" spacing={1}>
                      <Tooltip title="Edit">
                        <IconButton
                          size="small"
                          onClick={() => handleEdit(account)}
                        >
                          <EditIcon />
                        </IconButton>
                      </Tooltip>
                      <Tooltip title="Delete">
                        <IconButton
                          size="small"
                          color="error"
                          onClick={() => handleDelete(account.id)}
                        >
                          <DeleteIcon />
                        </IconButton>
                      </Tooltip>
                    </Stack>
                  </TableCell>
                </TableRow>
              )) || []}
            </TableBody>
          </Table>
        </TableContainer>
        <TablePagination
           component="div"
           count={filteredAccounts.length}
           page={page}
           onPageChange={(_, newPage) => setPage(newPage)}
           rowsPerPage={rowsPerPage}
           onRowsPerPageChange={(e) => {
             setRowsPerPage(parseInt(e.target.value, 10));
             setPage(0);
           }}
         />
      </Card>

      {/* Create/Edit Dialog */}
      <Dialog open={dialogOpen} onClose={handleCloseDialog} maxWidth="md" fullWidth>
        <DialogTitle>
          {editingAccount ? 'Edit Account' : 'Create New Account'}
        </DialogTitle>
        <Formik
          initialValues={getInitialValues()}
          validationSchema={validationSchema}
          onSubmit={handleSubmit}
          enableReinitialize
        >
          {({ errors, touched, isSubmitting }) => (
            <Form>
              <DialogContent>
                <Stack spacing={3}>
                  <Field name="name">
                    {({ field }: any) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Account Name"
                        error={touched.name && !!errors.name}
                        helperText={touched.name && errors.name}
                      />
                    )}
                  </Field>
                  <Field name="account_number">
                    {({ field }: any) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Account Number"
                        error={touched.account_number && !!errors.account_number}
                        helperText={touched.account_number && errors.account_number}
                      />
                    )}
                  </Field>
                  <Field name="type">
                    {({ field }: any) => (
                      <FormControl fullWidth error={touched.type && !!errors.type}>
                        <InputLabel>Account Type</InputLabel>
                        <Select {...field} label="Account Type">
                          <MenuItem value="asset">Asset</MenuItem>
                          <MenuItem value="liability">Liability</MenuItem>
                          <MenuItem value="equity">Equity</MenuItem>
                          <MenuItem value="income">Income</MenuItem>
                          <MenuItem value="expense">Expense</MenuItem>
                        </Select>
                        {touched.type && errors.type && (
                          <Typography variant="caption" color="error" sx={{ mt: 1, ml: 2 }}>
                            {errors.type}
                          </Typography>
                        )}
                      </FormControl>
                    )}
                  </Field>
                  <Field name="description">
                    {({ field }: any) => (
                      <TextField
                        {...field}
                        fullWidth
                        multiline
                        rows={3}
                        label="Description"
                        error={touched.description && !!errors.description}
                        helperText={touched.description && errors.description}
                      />
                    )}
                  </Field>
                  {!editingAccount && (
                    <Field name="balance">
                      {({ field }: any) => (
                        <TextField
                          {...field}
                          fullWidth
                          type="number"
                          label="Initial Balance"
                          error={touched.balance && !!errors.balance}
                          helperText={touched.balance && errors.balance}
                        />
                      )}
                    </Field>
                  )}
                </Stack>
              </DialogContent>
              <DialogActions>
                <Button onClick={handleCloseDialog}>Cancel</Button>
                <Button type="submit" variant="contained" disabled={isSubmitting}>
                  {editingAccount ? 'Update' : 'Create'}
                </Button>
              </DialogActions>
            </Form>
          )}
        </Formik>
      </Dialog>
    </Box>
  );
};

export default AccountManager;