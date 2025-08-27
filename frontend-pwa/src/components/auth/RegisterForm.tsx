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
  Email,
  Lock,
  Person,
} from '@mui/icons-material';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import { RegisterFormData } from '../../types';
import { registerSchema } from '../../utils/validationSchemas';
import { useAuth } from '../../hooks/useAuth';

interface RegisterFormProps {
  onSuccess?: () => void;
}

const RegisterForm: React.FC<RegisterFormProps> = ({ onSuccess }) => {
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const { register, isLoading, error, clearAuthError } = useAuth();
  const navigate = useNavigate();

  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
    setError,
    watch,
  } = useForm<RegisterFormData>({
    resolver: yupResolver(registerSchema),
    defaultValues: {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
    },
  });

  const password = watch('password');

  const onSubmit = async (data: RegisterFormData) => {
    try {
      clearAuthError();
      await register({
        name: data.name,
        email: data.email,
        password: data.password,
        password_confirmation: data.password_confirmation,
      });
      
      if (onSuccess) {
        onSuccess();
      } else {
        navigate('/dashboard');
      }
    } catch (err: any) {
      // Handle specific validation errors
      if (err.errors) {
        Object.keys(err.errors).forEach((field) => {
          setError(field as keyof RegisterFormData, {
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

  return (
    <Box component="form" onSubmit={handleSubmit(onSubmit)} noValidate>
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
        name="name"
        control={control}
        render={({ field }) => (
          <TextField
            {...field}
            fullWidth
            label="Nombre Completo"
            type="text"
            autoComplete="name"
            autoFocus
            margin="normal"
            error={!!errors.name}
            helperText={errors.name?.message}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Person color={errors.name ? 'error' : 'action'} />
                </InputAdornment>
              ),
            }}
            disabled={isLoading || isSubmitting}
          />
        )}
      />

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

      <Controller
        name="password"
        control={control}
        render={({ field }) => (
          <Box>
            <TextField
              {...field}
              fullWidth
              label="Contraseña"
              type={showPassword ? 'text' : 'password'}
              autoComplete="new-password"
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
            label="Confirmar Contraseña"
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
            Registrando...
          </>
        ) : (
          'Crear Cuenta'
        )}
      </Button>

      <Box sx={{ textAlign: 'center', mt: 2 }}>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
          Al registrarte, aceptas nuestros términos y condiciones
        </Typography>
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
          ¿Ya tienes cuenta? Inicia sesión aquí
        </Link>
      </Box>
    </Box>
  );
};

export default RegisterForm;