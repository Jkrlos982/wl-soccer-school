import React, { useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  TextField,
  Button,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  FormHelperText,
  Alert,
  Divider,
  Stack,
  Chip,
  IconButton,
  Autocomplete,
  InputAdornment,
} from '@mui/material';
import {
  Save as SaveIcon,
  Cancel as CancelIcon,
  Add as AddIcon,
  Delete as DeleteIcon,
  AttachMoney as MoneyIcon,
} from '@mui/icons-material';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { es } from 'date-fns/locale';
import { useFormik } from 'formik';
import * as Yup from 'yup';
import { useAppDispatch, useAppSelector } from '../../store';
import {
  selectConcepts,
  selectAccounts,
  selectFinancialLoading,
  selectFinancialErrors,
} from '../../store';
import {
  createTransaction,
  updateTransaction,
  fetchConcepts,
  fetchAccounts,
} from '../../store/financialSlice';
import {
  Transaction,
  CreateTransactionData,
  UpdateTransactionData,
  TransactionAccount,
} from '../../types';

interface TransactionFormProps {
  transaction?: Transaction;
  onSuccess?: (transaction: Transaction) => void;
  onCancel?: () => void;
}

interface TransactionAccountForm {
  account_id: string;
  amount: number;
  type: 'debit' | 'credit';
}

interface FormValues {
  financial_concept_id: string;
  description: string;
  amount: number;
  transaction_date: Date;
  payment_method: string;
  metadata: Record<string, any>;
  accounts: TransactionAccountForm[];
}

// Validation schema with Spanish messages (based on centralized schema structure)
const validationSchema = Yup.object({
  financial_concept_id: Yup.string().required('El concepto financiero es requerido'),
  description: Yup.string()
    .required('La descripción es requerida')
    .min(3, 'La descripción debe tener al menos 3 caracteres')
    .max(500, 'La descripción no puede exceder 500 caracteres'),
  amount: Yup.number()
    .required('El monto es requerido')
    .positive('El monto debe ser positivo')
    .max(999999999.99, 'El monto es demasiado grande'),
  transaction_date: Yup.date()
    .required('La fecha de transacción es requerida')
    .max(new Date(), 'La fecha no puede ser futura'),
  payment_method: Yup.string()
    .required('El método de pago es requerido')
    .oneOf(['cash', 'bank_transfer', 'card', 'mobile_money', 'cheque', 'other'], 'Método de pago inválido'),
  accounts: Yup.array()
    .of(
      Yup.object({
        account_id: Yup.string().required('La cuenta es requerida'),
        amount: Yup.number()
          .required('El monto es requerido')
          .positive('El monto debe ser positivo'),
        type: Yup.string().oneOf(['debit', 'credit']).required('El tipo es requerido'),
      })
    )
    .min(2, 'Se requieren al menos 2 cuentas (débito y crédito)')
    .required('Las cuentas son requeridas')
    .test('balanced', 'Los débitos y créditos deben estar balanceados', function (accounts) {
      if (!accounts || accounts.length === 0) return false;
      
      const totalDebits = accounts
        .filter(acc => acc.type === 'debit')
        .reduce((sum, acc) => sum + (acc.amount || 0), 0);
      
      const totalCredits = accounts
        .filter(acc => acc.type === 'credit')
        .reduce((sum, acc) => sum + (acc.amount || 0), 0);
      
      return Math.abs(totalDebits - totalCredits) < 0.01;
    }),
  metadata: Yup.object().optional().nullable(),
});

const paymentMethods = [
  { value: 'cash', label: 'Efectivo' },
  { value: 'bank_transfer', label: 'Transferencia Bancaria' },
  { value: 'credit_card', label: 'Tarjeta de Crédito' },
  { value: 'debit_card', label: 'Tarjeta de Débito' },
  { value: 'check', label: 'Cheque' },
  { value: 'other', label: 'Otro' },
];

const TransactionForm: React.FC<TransactionFormProps> = ({
  transaction,
  onSuccess,
  onCancel,
}) => {
  const dispatch = useAppDispatch();
  const concepts = useAppSelector(selectConcepts);
  const accounts = useAppSelector(selectAccounts);
  const loading = useAppSelector(selectFinancialLoading);
  const errors = useAppSelector(selectFinancialErrors);

  const [submitError, setSubmitError] = useState<string | null>(null);

  const isEditing = Boolean(transaction);

  // Load concepts and accounts if not already loaded
  useEffect(() => {
    if (concepts.length === 0) {
      dispatch(fetchConcepts());
    }
    if (accounts.length === 0) {
      dispatch(fetchAccounts());
    }
  }, [dispatch, concepts.length, accounts.length]);

  const formik = useFormik<FormValues>({
    initialValues: {
      financial_concept_id: transaction?.financial_concept_id || '',
      description: transaction?.description || '',
      amount: transaction?.amount || 0,
      transaction_date: transaction ? new Date(transaction.transaction_date) : new Date(),
      payment_method: transaction?.payment_method || '',
      metadata: transaction?.metadata || {},
      accounts: transaction?.accounts?.map((acc: any) => ({
        account_id: acc.account_id,
        amount: acc.amount,
        type: acc.type,
      })) || [
        { account_id: '', amount: 0, type: 'debit' as const },
        { account_id: '', amount: 0, type: 'credit' as const },
      ],
    },
    validationSchema,
    onSubmit: async (values) => {
      setSubmitError(null);
      
      try {
        const transactionData = {
          financial_concept_id: values.financial_concept_id,
          description: values.description,
          amount: values.amount,
          transaction_date: values.transaction_date.toISOString().split('T')[0],
          payment_method: values.payment_method,
          metadata: values.metadata,
          accounts: values.accounts,
        };

        let result;
        if (isEditing && transaction) {
          result = await dispatch(
            updateTransaction({
              id: transaction.id,
              data: transactionData as UpdateTransactionData,
            })
          ).unwrap();
        } else {
          result = await dispatch(
            createTransaction(transactionData as CreateTransactionData)
          ).unwrap();
        }

        if (onSuccess) {
          onSuccess(result);
        }
      } catch (error: any) {
        setSubmitError(
          error.message || `Error al ${isEditing ? 'actualizar' : 'crear'} la transacción`
        );
      }
    },
  });

  // Add new account row
  const addAccount = () => {
    formik.setFieldValue('accounts', [
      ...formik.values.accounts,
      { account_id: '', amount: 0, type: 'debit' as const },
    ]);
  };

  // Remove account row
  const removeAccount = (index: number) => {
    if (formik.values.accounts.length > 2) {
      const newAccounts = formik.values.accounts.filter((_, i) => i !== index);
      formik.setFieldValue('accounts', newAccounts);
    }
  };

  // Update account field
  const updateAccount = (index: number, field: keyof TransactionAccountForm, value: any) => {
    const newAccounts = [...formik.values.accounts];
    newAccounts[index] = { ...newAccounts[index], [field]: value };
    formik.setFieldValue('accounts', newAccounts);
  };

  // Calculate totals
  const totalDebits = formik.values.accounts
    .filter(acc => acc.type === 'debit')
    .reduce((sum, acc) => sum + (acc.amount || 0), 0);
  
  const totalCredits = formik.values.accounts
    .filter(acc => acc.type === 'credit')
    .reduce((sum, acc) => sum + (acc.amount || 0), 0);
  
  const isBalanced = Math.abs(totalDebits - totalCredits) < 0.01;

  return (
    <LocalizationProvider dateAdapter={AdapterDateFns} adapterLocale={es}>
      <Box>
        <Typography variant="h5" component="h2" sx={{ mb: 3 }}>
          {isEditing ? 'Editar Transacción' : 'Nueva Transacción'}
        </Typography>

        {(submitError || errors.form) && (
          <Alert severity="error" sx={{ mb: 3 }}>
            {submitError || errors.form}
          </Alert>
        )}

        <form onSubmit={formik.handleSubmit}>
          <Stack spacing={3}>
            {/* Basic Information */}
            <Card>
              <CardContent>
                <Typography variant="h6" sx={{ mb: 2 }}>
                  Información Básica
                </Typography>
                <Stack spacing={2}>
                  <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                    <Box sx={{ flex: 1 }}>
                      <FormControl
                        fullWidth
                        error={formik.touched.financial_concept_id && Boolean(formik.errors.financial_concept_id)}
                      >
                        <InputLabel>Concepto Financiero</InputLabel>
                        <Select
                          name="financial_concept_id"
                          value={formik.values.financial_concept_id}
                          onChange={formik.handleChange}
                          onBlur={formik.handleBlur}
                          label="Concepto Financiero"
                        >
                          {concepts.map((concept) => (
                            <MenuItem key={concept.id} value={concept.id}>
                              {concept.name} - {concept.type}
                            </MenuItem>
                          ))}
                        </Select>
                        {formik.touched.financial_concept_id && formik.errors.financial_concept_id && (
                          <FormHelperText>{formik.errors.financial_concept_id}</FormHelperText>
                        )}
                      </FormControl>
                    </Box>
                    <Box sx={{ flex: 1 }}>
                      <TextField
                        fullWidth
                        name="amount"
                        label="Monto"
                        type="number"
                        value={formik.values.amount}
                        onChange={formik.handleChange}
                        onBlur={formik.handleBlur}
                        error={formik.touched.amount && Boolean(formik.errors.amount)}
                        helperText={formik.touched.amount && formik.errors.amount}
                        InputProps={{
                          startAdornment: (
                            <InputAdornment position="start">
                              <MoneyIcon />
                            </InputAdornment>
                          ),
                        }}
                      />
                    </Box>
                  </Stack>
                   <TextField
                        fullWidth
                        name="description"
                        label="Descripción"
                        multiline
                        rows={3}
                        value={formik.values.description}
                        onChange={formik.handleChange}
                        onBlur={formik.handleBlur}
                        error={formik.touched.description && Boolean(formik.errors.description)}
                        helperText={formik.touched.description && formik.errors.description}
                      />
                  <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                    <Box sx={{ flex: 1 }}>
                      <DatePicker
                        label="Fecha de Transacción"
                        value={formik.values.transaction_date}
                        onChange={(date) => formik.setFieldValue('transaction_date', date)}
                        slotProps={{
                          textField: {
                            fullWidth: true,
                            error: formik.touched.transaction_date && Boolean(formik.errors.transaction_date),
                            helperText: formik.touched.transaction_date && formik.errors.transaction_date ? String(formik.errors.transaction_date) : undefined,
                          },
                        }}
                      />
                    </Box>
                    <Box sx={{ flex: 1 }}>
                      <FormControl
                        fullWidth
                        error={formik.touched.payment_method && Boolean(formik.errors.payment_method)}
                      >
                        <InputLabel>Método de Pago</InputLabel>
                        <Select
                          name="payment_method"
                          value={formik.values.payment_method}
                          onChange={formik.handleChange}
                          onBlur={formik.handleBlur}
                          label="Método de Pago"
                        >
                          {paymentMethods.map((method) => (
                            <MenuItem key={method.value} value={method.value}>
                              {method.label}
                            </MenuItem>
                          ))}
                        </Select>
                        {formik.touched.payment_method && formik.errors.payment_method && (
                          <FormHelperText>{formik.errors.payment_method}</FormHelperText>
                        )}
                      </FormControl>
                    </Box>
                  </Stack>
                </Stack>
              </CardContent>
            </Card>

            {/* Accounts */}
            <Card>
                <CardContent>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                    <Typography variant="h6">
                      Cuentas Contables
                    </Typography>
                    <Button
                      variant="outlined"
                      size="small"
                      startIcon={<AddIcon />}
                      onClick={addAccount}
                    >
                      Agregar Cuenta
                    </Button>
                  </Box>

                  {formik.values.accounts.map((account, index) => (
                    <Box key={index} sx={{ mb: 2, p: 2, border: 1, borderColor: 'divider', borderRadius: 1 }}>
                      <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems="center">
                        <Box sx={{ flex: 1, minWidth: { md: '33%' } }}>
                          <Autocomplete
                            options={accounts}
                            getOptionLabel={(option) => `${(option as any).code || option.id} - ${option.name}`}
                            value={accounts.find(acc => acc.id === account.account_id) || null}
                            onChange={(_, value) => updateAccount(index, 'account_id', value?.id || '')}
                            renderInput={(params) => (
                              <TextField
                                {...params}
                                label="Cuenta"
                                error={Boolean(formik.errors.accounts?.[index] && typeof formik.errors.accounts[index] === 'object' && (formik.errors.accounts[index] as any)?.account_id)}
                                helperText={formik.errors.accounts?.[index] && typeof formik.errors.accounts[index] === 'object' ? (formik.errors.accounts[index] as any)?.account_id : undefined}
                              />
                            )}
                          />
                        </Box>
                        <Box sx={{ flex: 1, minWidth: { md: '25%' } }}>
                          <TextField
                            fullWidth
                            label="Monto"
                            type="number"
                            value={account.amount}
                            onChange={(e) => updateAccount(index, 'amount', parseFloat(e.target.value) || 0)}
                            error={Boolean(formik.errors.accounts?.[index] && typeof formik.errors.accounts[index] === 'object' && (formik.errors.accounts[index] as any)?.amount)}
                            helperText={formik.errors.accounts?.[index] && typeof formik.errors.accounts[index] === 'object' ? (formik.errors.accounts[index] as any)?.amount : undefined}
                          />
                        </Box>
                        <Box sx={{ flex: 1, minWidth: { md: '25%' } }}>
                          <FormControl fullWidth>
                            <InputLabel>Tipo</InputLabel>
                            <Select
                              value={account.type}
                              onChange={(e) => updateAccount(index, 'type', e.target.value)}
                              label="Tipo"
                            >
                              <MenuItem value="debit">Débito</MenuItem>
                              <MenuItem value="credit">Crédito</MenuItem>
                            </Select>
                          </FormControl>
                        </Box>
                        <Box sx={{ minWidth: { md: '15%' } }}>
                          <IconButton
                            color="error"
                            onClick={() => removeAccount(index)}
                            disabled={formik.values.accounts.length <= 2}
                          >
                            <DeleteIcon />
                          </IconButton>
                        </Box>
                      </Stack>
                    </Box>
                  ))}

                  {/* Balance Summary */}
                  <Box sx={{ mt: 2, p: 2, bgcolor: 'background.paper', borderRadius: 1 }}>
                    <Stack direction="row" spacing={2}>
                      <Box sx={{ flex: 1 }}>
                        <Typography variant="body2" color="text.secondary">
                          Total Débitos:
                        </Typography>
                        <Typography variant="h6" color="error.main">
                          ${totalDebits.toLocaleString()}
                        </Typography>
                      </Box>
                      <Box sx={{ flex: 1 }}>
                        <Typography variant="body2" color="text.secondary">
                          Total Créditos:
                        </Typography>
                        <Typography variant="h6" color="success.main">
                          ${totalCredits.toLocaleString()}
                        </Typography>
                      </Box>
                      <Box sx={{ flex: 1 }}>
                        <Typography variant="body2" color="text.secondary">
                          Balance:
                        </Typography>
                        <Chip
                          label={isBalanced ? 'Balanceado' : 'Desbalanceado'}
                          color={isBalanced ? 'success' : 'error'}
                          size="small"
                        />
                      </Box>
                    </Stack>
                  </Box>

                  {formik.errors.accounts && typeof formik.errors.accounts === 'string' && (
                    <Alert severity="error" sx={{ mt: 2 }}>
                      {formik.errors.accounts}
                    </Alert>
                  )}
                </CardContent>
              </Card>

            {/* Actions */}
              <Stack direction="row" spacing={2} justifyContent="flex-end">
                <Button
                  variant="outlined"
                  startIcon={<CancelIcon />}
                  onClick={onCancel}
                  disabled={loading.creating || loading.updating}
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  variant="contained"
                  startIcon={<SaveIcon />}
                  disabled={loading.creating || loading.updating || !formik.isValid}
                  loading={loading.creating || loading.updating}
                >
                  {isEditing ? 'Actualizar' : 'Crear'} Transacción
                </Button>
              </Stack>
            </Stack>
        </form>
      </Box>
    </LocalizationProvider>
  );
};

export default TransactionForm;