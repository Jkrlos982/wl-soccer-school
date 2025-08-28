import React, { useEffect } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  CardHeader,
  TextField,
  Stack,
  Alert,
  CircularProgress,
  Typography,
  Divider,
  Chip,
} from '@mui/material';
import {
  Save as SaveIcon,
  Cancel as CancelIcon,
  Schedule as ScheduleIcon,
  Payment as PaymentIcon,
} from '@mui/icons-material';
import { useFormik } from 'formik';
import * as yup from 'yup';
import { useAppDispatch, useAppSelector } from '../../store';
import {
  createPaymentPlan,
  updatePaymentPlan,
} from '../../store/accountsReceivableSlice';
import {
  selectARLoading,
  selectARErrors,
} from '../../store';
import { PaymentPlanData, AccountReceivable, PaymentPlan } from '../../types/financial';
import { formatCurrency, formatDate } from '../../utils';

interface PaymentPlanFormProps {
  accountReceivable: AccountReceivable;
  paymentPlan?: PaymentPlan;
  onSuccess?: () => void;
  onCancel?: () => void;
}

interface FormValues {
  total_amount: number;
  installments: number;
  start_date: Date;
  notes: string;
}

const validationSchema = yup.object({
  total_amount: yup
    .number()
    .positive('El monto total debe ser positivo')
    .required('El monto total es requerido'),
  installments: yup
    .number()
    .integer('El número de cuotas debe ser un entero')
    .min(2, 'Debe tener al menos 2 cuotas')
    .max(24, 'No puede tener más de 24 cuotas')
    .required('El número de cuotas es requerido'),
  start_date: yup
    .date()
    .min(new Date(), 'La fecha de inicio no puede ser anterior a hoy')
    .required('La fecha de inicio es requerida'),
  notes: yup.string().optional(),
});

const PaymentPlanForm: React.FC<PaymentPlanFormProps> = ({
  accountReceivable,
  paymentPlan,
  onSuccess,
  onCancel,
}) => {
  const dispatch = useAppDispatch();
  const isLoading = useAppSelector(selectARLoading);
  const error = useAppSelector(selectARErrors);
  
  const [submitError, setSubmitError] = React.useState<string | null>(null);
  
  const isEditing = Boolean(paymentPlan);
  const maxAmount = accountReceivable.balance;
  
  const formik = useFormik<FormValues>({
    initialValues: {
      total_amount: paymentPlan?.total_amount || accountReceivable.balance,
      installments: paymentPlan?.installments || 3,
      start_date: paymentPlan?.start_date ? new Date(paymentPlan.start_date) : new Date(),
      notes: paymentPlan?.notes || '',
    },
    validationSchema,
    onSubmit: async (values) => {
      setSubmitError(null);
      
      try {
        const paymentPlanData: PaymentPlanData = {
          total_amount: values.total_amount,
          installments: values.installments,
          start_date: values.start_date.toISOString().split('T')[0],
          notes: values.notes || undefined,
        };
        
        if (isEditing && paymentPlan) {
          await dispatch(updatePaymentPlan({
            planId: paymentPlan.id,
            data: paymentPlanData,
          })).unwrap();
        } else {
          await dispatch(createPaymentPlan({
            arId: accountReceivable.id,
            data: paymentPlanData,
          })).unwrap();
        }
        
        onSuccess?.();
      } catch (err: any) {
        setSubmitError(err.message || 'Error al procesar el plan de pago');
      }
    },
  });
  
  const installmentAmount = formik.values.total_amount / formik.values.installments;
  
  const handleCancel = () => {
    formik.resetForm();
    onCancel?.();
  };
  
  return (
    <Card>
      <CardHeader
        title={
          <Box display="flex" alignItems="center" gap={1}>
            <ScheduleIcon color="primary" />
            <Typography variant="h6">
              {isEditing ? 'Editar Plan de Pago' : 'Crear Plan de Pago'}
            </Typography>
          </Box>
        }
        subheader={`Cuenta por cobrar: ${accountReceivable.description}`}
      />
      
      <CardContent>
        <form onSubmit={formik.handleSubmit}>
          <Stack spacing={3}>
            {/* Account Receivable Info */}
            <Box
              p={2}
              bgcolor="grey.50"
              borderRadius={1}
              border={1}
              borderColor="grey.200"
            >
              <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
                <Box sx={{ flex: 1 }}>
                  <Typography variant="body2" color="text.secondary">
                    Estudiante
                  </Typography>
                  <Typography variant="body1" fontWeight="medium">
                    {accountReceivable.student?.name || 'N/A'}
                  </Typography>
                </Box>
                <Box sx={{ flex: 1 }}>
                  <Typography variant="body2" color="text.secondary">
                    Saldo Pendiente
                  </Typography>
                  <Typography variant="h6" color="error.main">
                    {formatCurrency(accountReceivable.balance)}
                  </Typography>
                </Box>
              </Stack>
            </Box>
            
            {/* Form Fields */}
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
              <Box sx={{ flex: 1 }}>
                <TextField
                  fullWidth
                  name="total_amount"
                  label="Monto Total del Plan"
                  type="number"
                  value={formik.values.total_amount}
                  onChange={formik.handleChange}
                  onBlur={formik.handleBlur}
                  error={formik.touched.total_amount && Boolean(formik.errors.total_amount)}
                  helperText={formik.touched.total_amount && formik.errors.total_amount}
                  InputProps={{
                    inputProps: {
                      min: 0,
                      max: maxAmount,
                      step: 0.01,
                    },
                  }}
                />
              </Box>
              
              <Box sx={{ flex: 1 }}>
                <TextField
                  fullWidth
                  name="installments"
                  label="Número de Cuotas"
                  type="number"
                  value={formik.values.installments}
                  onChange={formik.handleChange}
                  onBlur={formik.handleBlur}
                  error={formik.touched.installments && Boolean(formik.errors.installments)}
                  helperText={formik.touched.installments && formik.errors.installments}
                  InputProps={{
                    inputProps: {
                      min: 2,
                      max: 24,
                      step: 1,
                    },
                  }}
                />
              </Box>
            </Stack>
            
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
              <Box sx={{ flex: 1 }}>
                <TextField
                  fullWidth
                  name="start_date"
                  label="Fecha de Inicio"
                  type="date"
                  value={formik.values.start_date.toISOString().split('T')[0]}
                  onChange={(e) => {
                    formik.setFieldValue('start_date', new Date(e.target.value));
                  }}
                  onBlur={formik.handleBlur}
                  error={formik.touched.start_date && Boolean(formik.errors.start_date)}
                  helperText={formik.touched.start_date && formik.errors.start_date ? String(formik.errors.start_date) : ''}
                  InputLabelProps={{
                    shrink: true,
                  }}
                />
              </Box>
              
              <Box sx={{ flex: 1 }}>
                <Box display="flex" flexDirection="column" gap={1}>
                  <Typography variant="body2" color="text.secondary">
                    Valor por Cuota
                  </Typography>
                  <Chip
                    icon={<PaymentIcon />}
                    label={formatCurrency(installmentAmount)}
                    color="primary"
                    variant="outlined"
                    size="medium"
                  />
                </Box>
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
              placeholder="Observaciones adicionales sobre el plan de pago..."
            />
            
            {/* Plan Summary */}
            <Box>
              <Divider sx={{ my: 2 }} />
              <Typography variant="h6" gutterBottom>
                Resumen del Plan
              </Typography>
              <Box
                p={2}
                bgcolor="primary.50"
                borderRadius={1}
                border={1}
                borderColor="primary.200"
              >
                <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
                  <Box sx={{ flex: 1 }}>
                    <Typography variant="body2" color="text.secondary">
                      Monto Total
                    </Typography>
                    <Typography variant="h6">
                      {formatCurrency(formik.values.total_amount)}
                    </Typography>
                  </Box>
                  <Box sx={{ flex: 1 }}>
                    <Typography variant="body2" color="text.secondary">
                      Número de Cuotas
                    </Typography>
                    <Typography variant="h6">
                      {formik.values.installments}
                    </Typography>
                  </Box>
                  <Box sx={{ flex: 1 }}>
                    <Typography variant="body2" color="text.secondary">
                      Valor por Cuota
                    </Typography>
                    <Typography variant="h6" color="primary.main">
                      {formatCurrency(installmentAmount)}
                    </Typography>
                  </Box>
                </Stack>
              </Box>
            </Box>
            
            {/* Error Display */}
            {(submitError || error?.form) && (
              <Alert severity="error">
                {submitError || error?.form}
              </Alert>
            )}
            
            {/* Action Buttons */}
            <Box display="flex" gap={2} justifyContent="flex-end">
              <Button
                variant="outlined"
                startIcon={<CancelIcon />}
                onClick={handleCancel}
                disabled={isLoading.creating || isLoading.updating}
              >
                Cancelar
              </Button>
              <Button
                type="submit"
                variant="contained"
                startIcon={
                  isLoading.creating || isLoading.updating ? (
                    <CircularProgress size={20} color="inherit" />
                  ) : (
                    <SaveIcon />
                  )
                }
                disabled={isLoading.creating || isLoading.updating || !formik.isValid}
              >
                {isEditing ? 'Actualizar Plan' : 'Crear Plan'}
              </Button>
            </Box>
          </Stack>
        </form>
      </CardContent>
    </Card>
  );
};

export default PaymentPlanForm;