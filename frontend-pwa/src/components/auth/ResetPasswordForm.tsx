import React, { useState } from 'react';
import {
  Box,
  TextField,
  Button,
  Link,
  Alert,
  InputAdornment,
  IconButton,
  CircularProgress,
  Typography,
} from '@mui/material';
import {
  Visibility,
  VisibilityOff,
  Lock,
  CheckCircle,
} from '@mui/icons-material';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import { Link as RouterLink, useNavigate, useSearchParams } from 'react-router-dom';
import { ResetPasswordFormData } from '../../types';
import { resetPasswordSchema } from '../../utils/validationSchemas';
import { useAuth } from '../../hooks/useAuth';

interface ResetPasswordFormProps {
  onSuccess?: () => void;
}

const ResetPasswordForm: React.FC<ResetPasswordFormProps> = ({ onSuccess }) => {
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [resetSuccess, setResetSuccess] = useState(false);
  const { resetUserPassword, isLoading, error, clearAuthError } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  
  const token = searchParams.get('token');
  const email = searchParams.get('email');

  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
    setError,
    watch,
  } = useForm<ResetPasswordFormData>({
    resolver: yupResolver(resetPasswordSchema),
    defaultValues: {
      password: '',
      password_confirmation: '',
    },
  });

  const password = watch('password');

  // Redirect if no token is provided
  React.useEffect(() => {
    if (!token) {
      navigate('/forgot-password');
    }
  }, [token, navigate]);

  const onSubmit = async (data: ResetPasswordFormData) => {
    try {
      clearAuthError();
      await resetUserPassword({
        email: email || '',
        token: token || '',
        password: data.password,
        password_confirmation: data.password_confirmation,
      });
      
      setResetSuccess(true);
      
      if (onSuccess) {
        onSuccess();
      } else {
        // Redirect to login after 3 seconds
        setTimeout(() => {
          navigate('/login');
        }, 3000);
      }
    } catch (err: any) {
      // Handle specific validation errors
      if (err.errors) {
        Object.keys(err.errors).forEach((field) => {
          setError(field as keyof ResetPasswordFormData, {
            type: 'server',
            message: err.errors[field][0],
          });
        });
      }
    }
  };

  const handleTogglePasswordVisibility = () => {
    setShowPassword(!showPassword);
  };

  const handleToggleConfirmPasswordVisibility = () => {
    setShowConfirmPassword(!showConfirmPassword);
  };

  const getPasswordStrength = (password: string): { strength: number; label: string; color: string } => {
    if (!password) return { strength: 0, label: '', color: '' };
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[@$!%*?&]/.test(password)) strength++;
    
    const labels = ['', 'Muy débil', 'Débil', 'Regular', 'Fuerte', 'Muy fuerte'];
    const colors = ['', '#f44336', '#ff9800', '#ffeb3b', '#4caf50', '#2e7d32'];
    
    return {
      strength: (strength / 5) * 100,
      label: labels[strength],
      color: colors[strength],
    };
  };

  const passwordStrength = getPasswordStrength(password);

  if (resetSuccess) {
    return (
      <Box sx={{ textAlign: 'center' }}>
        <CheckCircle 
          sx={{ 
            fontSize: 64, 
            color: 'success.main', 
            mb: 2 
          }} 
        />
        <Typography variant="h5" gutterBottom color="success.main">
          ¡Contraseña restablecida!
        </Typography>
        <Typography variant="body1" color="text.secondary" sx={{ mb: 3 }}>
          Tu contraseña ha sido restablecida exitosamente.
          Serás redirigido al inicio de sesión en unos segundos.
        </Typography>
        <Button
          variant="contained"
          onClick={() => navigate('/login')}
          sx={{ mt: 2 }}
        >
          Ir al inicio de sesión
        </Button>
      </Box>
    );
  }

  if (!token) {
    return null; // Will redirect
  }

  return (
    <Box component="form" onSubmit={handleSubmit(onSubmit)} noValidate>
      <Typography variant="body1" color="text.secondary" sx={{ mb: 3, textAlign: 'center' }}>
        Ingresa tu nueva contraseña para restablecer el acceso a tu cuenta.
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

      {/* Hidden fields for email and token */}
      <input type="hidden" name="email" value={email || ''} />
      <input type="hidden" name="token" value={token || ''} />

      <Controller
        name="password"
        control={control}
        render={({ field }) => (
          <Box>
            <TextField
              {...field}
              fullWidth
              label="Nueva Contraseña"
              type={showPassword ? 'text' : 'password'}
              autoComplete="new-password"
              autoFocus
              margin="normal"
              error={!!errors.password}
              helperText={errors.password?.message}
              InputProps={{
                startAdornment: (
                  <InputAdornment position="start">
                    <Lock color={errors.password ? 'error' : 'action'} />
                  </InputAdornment>
                ),
                endAdornment: (
                  <InputAdornment position="end">
                    <IconButton
                      aria-label="toggle password visibility"
                      onClick={handleTogglePasswordVisibility}
                      edge="end"
                      disabled={isLoading || isSubmitting}
                    >
                      {showPassword ? <VisibilityOff /> : <Visibility />}
                    </IconButton>
                  </InputAdornment>
                ),
              }}
              disabled={isLoading || isSubmitting}
            />
            {password && (
              <Box sx={{ mt: 1, mb: 1 }}>
                <Box
                  sx={{
                    height: 4,
                    backgroundColor: '#e0e0e0',
                    borderRadius: 2,
                    overflow: 'hidden',
                  }}
                >
                  <Box
                    sx={{
                      height: '100%',
                      width: `${passwordStrength.strength}%`,
                      backgroundColor: passwordStrength.color,
                      transition: 'all 0.3s ease',
                    }}
                  />
                </Box>
                <Typography
                  variant="caption"
                  sx={{ color: passwordStrength.color, fontWeight: 500 }}
                >
                  {passwordStrength.label}
                </Typography>
              </Box>
            )}
          </Box>
        )}
      />

      <Controller
        name="password_confirmation"
        control={control}
        render={({ field }) => (
          <TextField
            {...field}
            fullWidth
            label="Confirmar Nueva Contraseña"
            type={showConfirmPassword ? 'text' : 'password'}
            autoComplete="new-password"
            margin="normal"
            error={!!errors.password_confirmation}
            helperText={errors.password_confirmation?.message}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Lock color={errors.password_confirmation ? 'error' : 'action'} />
                </InputAdornment>
              ),
              endAdornment: (
                <InputAdornment position="end">
                  <IconButton
                    aria-label="toggle confirm password visibility"
                    onClick={handleToggleConfirmPasswordVisibility}
                    edge="end"
                    disabled={isLoading || isSubmitting}
                  >
                    {showConfirmPassword ? <VisibilityOff /> : <Visibility />}
                  </IconButton>
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
            Restableciendo...
          </>
        ) : (
          'Restablecer Contraseña'
        )}
      </Button>

      <Box sx={{ textAlign: 'center', mt: 2 }}>
        <Link
          component={RouterLink}
          to="/login"
          variant="body2"
          sx={{
            textDecoration: 'none',
            '&:hover': {
              textDecoration: 'underline',
            },
          }}
        >
          Volver al inicio de sesión
        </Link>
      </Box>
    </Box>
  );
};

export default ResetPasswordForm;