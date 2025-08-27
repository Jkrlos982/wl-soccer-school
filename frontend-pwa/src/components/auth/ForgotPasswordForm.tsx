import React from 'react';
import {
  Box,
  TextField,
  Button,
  Link,
  Alert,
  InputAdornment,
  CircularProgress,
  Typography,
} from '@mui/material';
import {
  Email,
  ArrowBack,
} from '@mui/icons-material';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import { Link as RouterLink } from 'react-router-dom';
import { ForgotPasswordFormData } from '../../types';
import { forgotPasswordSchema } from '../../utils/validationSchemas';
import { useAuth } from '../../hooks/useAuth';

interface ForgotPasswordFormProps {
  onSuccess?: () => void;
}

const ForgotPasswordForm: React.FC<ForgotPasswordFormProps> = ({ onSuccess }) => {
  const { sendPasswordReset, isLoading, error, clearAuthError } = useAuth();
  const [emailSent, setEmailSent] = React.useState(false);

  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
    setError,
    getValues,
  } = useForm<ForgotPasswordFormData>({
    resolver: yupResolver(forgotPasswordSchema),
    defaultValues: {
      email: '',
    },
  });

  const onSubmit = async (data: ForgotPasswordFormData) => {
    try {
      clearAuthError();
      await sendPasswordReset({ email: data.email });
      setEmailSent(true);
      
      if (onSuccess) {
        onSuccess();
      }
    } catch (err: any) {
      // Handle specific validation errors
      if (err.errors) {
        Object.keys(err.errors).forEach((field) => {
          setError(field as keyof ForgotPasswordFormData, {
            type: 'server',
            message: err.errors[field][0],
          });
        });
      }
    }
  };

  const handleResendEmail = async () => {
    const email = getValues('email');
    if (email) {
      try {
        clearAuthError();
        await sendPasswordReset({ email });
      } catch (err) {
        // Error is handled by the auth hook
      }
    }
  };

  if (emailSent) {
    return (
      <Box sx={{ textAlign: 'center' }}>
        <Alert severity="success" sx={{ mb: 3 }}>
          <Typography variant="h6" gutterBottom>
            ¡Correo enviado!
          </Typography>
          <Typography variant="body2">
            Hemos enviado un enlace de recuperación a tu correo electrónico.
            Revisa tu bandeja de entrada y sigue las instrucciones.
          </Typography>
        </Alert>

        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          ¿No recibiste el correo? Revisa tu carpeta de spam o
        </Typography>

        <Button
          variant="outlined"
          onClick={handleResendEmail}
          disabled={isLoading}
          sx={{ mb: 3 }}
        >
          {isLoading ? (
            <>
              <CircularProgress size={16} sx={{ mr: 1 }} />
              Reenviando...
            </>
          ) : (
            'Reenviar correo'
          )}
        </Button>

        <Box>
          <Link
            component={RouterLink}
            to="/login"
            variant="body2"
            sx={{
              display: 'inline-flex',
              alignItems: 'center',
              textDecoration: 'none',
              '&:hover': {
                textDecoration: 'underline',
              },
            }}
          >
            <ArrowBack sx={{ mr: 0.5, fontSize: 16 }} />
            Volver al inicio de sesión
          </Link>
        </Box>
      </Box>
    );
  }

  return (
    <Box component="form" onSubmit={handleSubmit(onSubmit)} noValidate>
      <Typography variant="body1" color="text.secondary" sx={{ mb: 3, textAlign: 'center' }}>
        Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
      </Typography>

      {error && (
        <Alert 
          severity="error" 
          sx={{ mb: 2 }}
          onClose={clearAuthError}
        >
          {error}
        </Alert>
      )}

      <Controller
        name="email"
        control={control}
        render={({ field }) => (
          <TextField
            {...field}
            fullWidth
            label="Correo Electrónico"
            type="email"
            autoComplete="email"
            autoFocus
            margin="normal"
            error={!!errors.email}
            helperText={errors.email?.message}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Email color={errors.email ? 'error' : 'action'} />
                </InputAdornment>
              ),
            }}
            disabled={isLoading || isSubmitting}
          />
        )}
      />

      <Button
        type="submit"
        fullWidth
        variant="contained"
        size="large"
        disabled={isLoading || isSubmitting}
        sx={{
          mt: 3,
          mb: 2,
          py: 1.5,
          fontSize: '1.1rem',
          fontWeight: 600,
        }}
      >
        {(isLoading || isSubmitting) ? (
          <>
            <CircularProgress size={20} sx={{ mr: 1 }} />
            Enviando...
          </>
        ) : (
          'Enviar enlace de recuperación'
        )}
      </Button>

      <Box sx={{ textAlign: 'center', mt: 2 }}>
        <Link
          component={RouterLink}
          to="/login"
          variant="body2"
          sx={{
            display: 'inline-flex',
            alignItems: 'center',
            textDecoration: 'none',
            '&:hover': {
              textDecoration: 'underline',
            },
          }}
        >
          <ArrowBack sx={{ mr: 0.5, fontSize: 16 }} />
          Volver al inicio de sesión
        </Link>
      </Box>

      <Box sx={{ textAlign: 'center', mt: 3 }}>
        <Typography variant="body2" color="text.secondary">
          ¿No tienes cuenta?{' '}
          <Link
            component={RouterLink}
            to="/register"
            variant="body2"
            sx={{
              textDecoration: 'none',
              '&:hover': {
                textDecoration: 'underline',
              },
            }}
          >
            Regístrate aquí
          </Link>
        </Typography>
      </Box>
    </Box>
  );
};

export default ForgotPasswordForm;