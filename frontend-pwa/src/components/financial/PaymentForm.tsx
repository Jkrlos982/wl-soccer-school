import React, { useState, useEffect } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  TextField,
  Typography,
  Stack,
  Alert,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  FormHelperText,
  InputAdornment,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
} from '@mui/material';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { es } from 'date-fns/locale';
import { useFormik } from 'formik';
import * as Yup from 'yup';
import {
  Save as SaveIcon,
  Cancel as CancelIcon,
  AttachMoney as MoneyIcon,
  Receipt as ReceiptIcon,
} from '@mui/icons-material';
import { useAppDispatch, useAppSelector } from '../../store';
import {
  registerPayment,
  updatePayment,
} from '../../store/accountsReceivableSlice';
import {
  selectARLoading,
  selectARErrors,
} from '../../store';
import { AccountReceivable, Payment, PaymentData } from '../../types/financial';
import { formatCurrency } from '../../utils';

interface PaymentFormProps {
  accountReceivable: AccountReceivable;
  payment?: Payment;
  open: boolean;
  onClose: () => void;
  onSuccess?: (payment: Payment) => void;
}

interface FormValues {
  amount: number;
  payment_date: Date;
  payment_method: string;
  reference_number: string;
  notes: string;
  voucher_url?: string;
}

const createValidationSchema = (maxAmount: number) => Yup.object({
  amount: Yup.number()
    .required('El monto es requerido')
    .positive('El monto debe ser positivo')
    .max(maxAmount, 'El monto no puede exceder el saldo pendiente'),
  payment_date: Yup.date()
    .required('La fecha de pago es requerida')
    .max(new Date(), 'La fecha no puede ser futura'),
  payment_method: Yup.string().required('El método de pago es requerido'),
  reference_number: Yup.string()
    .required('El número de referencia es requerido')
    .min(3, 'El número de referencia debe tener al menos 3 caracteres'),
  notes: Yup.string().max(500, 'Las notas no pueden exceder 500 caracteres'),
  voucher_url: Yup.string().url('Debe ser una URL válida').optional(),
});

const paymentMethods = [
  { value: 'cash', label: 'Efectivo' },
  { value: 'bank_transfer', label: 'Transferencia Bancaria' },
  { value: 'credit_card', label: 'Tarjeta de Crédito' },
  { value: 'debit_card', label: 'Tarjeta de Débito' },
  { value: 'check', label: 'Cheque' },
  { value: 'mobile_payment', label: 'Pago Móvil' },
  { value: 'other', label: 'Otro' },
];

const PaymentForm: React.FC<PaymentFormProps> = ({
  accountReceivable,
  payment,
  open,
  onClose,
  onSuccess,
}) => {
  const dispatch = useAppDispatch();
  const loading = useAppSelector(selectARLoading);
  const errors = useAppSelector(selectARErrors);

  const [submitError, setSubmitError] = useState<string | null>(null);

  const isEditing = Boolean(payment);
  const maxAmount = isEditing 
    ? accountReceivable.balance + (payment?.amount || 0)
    : accountReceivable.balance;

  const formik = useFormik<FormValues>({
    initialValues: {
      amount: payment?.amount || 0,
      payment_date: payment?.payment_date ? new Date(payment.payment_date) : new Date(),
      payment_method: payment?.payment_method || 'cash',
      reference_number: payment?.reference_number || '',
      notes: payment?.notes || '',
    },
    validationSchema: createValidationSchema(maxAmount),
    onSubmit: async (values) => {
      setSubmitError(null);
      
      try {
        const paymentData: PaymentData = {
          amount: values.amount,
          payment_date: values.payment_date.toISOString().split('T')[0],
          payment_method: values.payment_method as Payment['payment_method'],
          reference_number: values.reference_number || undefined,
          notes: values.notes || undefined,
        };

        let result;
        if (isEditing && payment) {
          result = await dispatch(
            updatePayment({
              paymentId: payment.id,
              data: paymentData,
            })
          ).unwrap();
        } else {
          result = await dispatch(
            registerPayment({
              arId: accountReceivable.id,
              paymentData,
            })
          ).unwrap();
        }

        if (onSuccess) {
          onSuccess(result);
        }
        onClose();
      } catch (error: any) {
        setSubmitError(
          error.message || `Error al ${isEditing ? 'actualizar' : 'registrar'} el pago`
        );
      }
    },
  });

  // Reset form when dialog opens/closes
  useEffect(() => {
    if (open) {
      formik.resetForm();
      setSubmitError(null);
    }
  }, [open]);

  const handleClose = () => {
    formik.resetForm();
    setSubmitError(null);
    onClose();
  };

  return (
    <LocalizationProvider dateAdapter={AdapterDateFns} adapterLocale={es}>
      <Dialog
        open={open}
        onClose={handleClose}
        maxWidth="md"
        fullWidth
        PaperProps={{
          sx: { minHeight: '60vh' },
        }}
      >
        <DialogTitle>
          <Stack direction="row" alignItems="center" spacing={1}>
            <ReceiptIcon />
            <Typography variant="h6">
              {isEditing ? 'Editar Pago' : 'Registrar Pago'}
            </Typography>
          </Stack>
        </DialogTitle>

        <DialogContent>
          {/* Account Receivable Info */}
          <Card sx={{ mb: 3, bgcolor: 'background.default' }}>
            <CardContent>
              <Typography variant="subtitle1" gutterBottom>
                Información de la Cuenta por Cobrar
              </Typography>
              <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
                <Box>
                  <Typography variant="body2" color="text.secondary">
                    Estudiante:
                  </Typography>
                  <Typography variant="body1">
                    {accountReceivable.student?.name || 'N/A'}
                  </Typography>
                </Box>
                <Box>
                  <Typography variant="body2" color="text.secondary">
                    Concepto:
                  </Typography>
                  <Typography variant="body1">
                    {accountReceivable.concept?.name || accountReceivable.description}
                  </Typography>
                </Box>
                <Box>
                  <Typography variant="body2" color="text.secondary">
                    Saldo Pendiente:
                  </Typography>
                  <Typography variant="h6" color="error.main">
                    {formatCurrency(accountReceivable.balance)}
                  </Typography>
                </Box>
              </Stack>
            </CardContent>
          </Card>

          {(submitError || errors.form) && (
            <Alert severity="error" sx={{ mb: 3 }}>
              {submitError || errors.form}
            </Alert>
          )}

          <form onSubmit={formik.handleSubmit}>
            <Stack spacing={3}>
              {/* Payment Details */}
              <Card>
                <CardContent>
                  <Typography variant="h6" sx={{ mb: 2 }}>
                    Detalles del Pago
                  </Typography>
                  <Stack spacing={2}>
                    <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                      <Box sx={{ flex: 1 }}>
                        <TextField
                          fullWidth
                          name="amount"
                          label="Monto del Pago"
                          type="number"
                          inputProps={{ step: '0.01', min: '0.01', max: maxAmount }}
                          value={formik.values.amount}
                          onChange={formik.handleChange}
                          onBlur={formik.handleBlur}
                          error={formik.touched.amount && Boolean(formik.errors.amount)}
                          helperText={
                            formik.touched.amount && formik.errors.amount
                              ? formik.errors.amount
                              : `Máximo: ${formatCurrency(maxAmount)}`
                          }
                          InputProps={{
                            startAdornment: (
                              <InputAdornment position="start">
                                <MoneyIcon />
                              </InputAdornment>
                            ),
                          }}
                        />
                      </Box>
                      <Box sx={{ flex: 1 }}>
                        <DatePicker
                          label="Fecha de Pago"
                          value={formik.values.payment_date}
                          onChange={(date) => formik.setFieldValue('payment_date', date)}
                          maxDate={new Date()}
                          slotProps={{
                            textField: {
                              fullWidth: true,
                              error: formik.touched.payment_date && Boolean(formik.errors.payment_date),
                              helperText: formik.touched.payment_date && formik.errors.payment_date
                                ? String(formik.errors.payment_date)
                                : undefined,
                            },
                          }}
                        />
                      </Box>
                    </Stack>

                    <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
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
                      <Box sx={{ flex: 1 }}>
                        <TextField
                          fullWidth
                          name="reference_number"
                          label="Número de Referencia"
                          value={formik.values.reference_number}
                          onChange={formik.handleChange}
                          onBlur={formik.handleBlur}
                          error={formik.touched.reference_number && Boolean(formik.errors.reference_number)}
                          helperText={formik.touched.reference_number && formik.errors.reference_number}
                          placeholder="Ej: TRX-001, CHK-123, REF-456"
                        />
                      </Box>
                    </Stack>

                    <TextField
                      fullWidth
                      name="notes"
                      label="Notas (Opcional)"
                      multiline
                      rows={3}
                      value={formik.values.notes}
                      onChange={formik.handleChange}
                      onBlur={formik.handleBlur}
                      error={formik.touched.notes && Boolean(formik.errors.notes)}
                      helperText={formik.touched.notes && formik.errors.notes}
                      placeholder="Información adicional sobre el pago..."
                    />

                    <TextField
                      fullWidth
                      name="voucher_url"
                      label="URL del Comprobante (Opcional)"
                      type="url"
                      value={formik.values.voucher_url}
                      onChange={formik.handleChange}
                      onBlur={formik.handleBlur}
                      error={formik.touched.voucher_url && Boolean(formik.errors.voucher_url)}
                      helperText={
                        formik.touched.voucher_url && formik.errors.voucher_url
                          ? formik.errors.voucher_url
                          : "Enlace al comprobante digital del pago"
                      }
                      placeholder="https://ejemplo.com/comprobante.pdf"
                    />
                  </Stack>
                </CardContent>
              </Card>
            </Stack>
          </form>
        </DialogContent>

        <DialogActions sx={{ p: 3 }}>
          <Button
            variant="outlined"
            startIcon={<CancelIcon />}
            onClick={handleClose}
            disabled={loading.paying}
          >
            Cancelar
          </Button>
          <Button
            variant="contained"
            startIcon={<SaveIcon />}
            onClick={formik.submitForm}
            disabled={loading.paying || !formik.isValid || !formik.dirty}
            sx={{
              minWidth: 120,
            }}
          >
            {loading.paying
              ? 'Procesando...'
              : isEditing
              ? 'Actualizar'
              : 'Registrar Pago'
            }
          </Button>
        </DialogActions>
      </Dialog>
    </LocalizationProvider>
  );
};

export default PaymentForm;