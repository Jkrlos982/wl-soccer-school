import * as yup from 'yup';

// Common validation rules
const emailValidation = yup
  .string()
  .email('Ingrese un email válido')
  .required('El email es requerido');

const passwordValidation = yup
  .string()
  .min(8, 'La contraseña debe tener al menos 8 caracteres')
  .matches(
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/,
    'La contraseña debe contener al menos: 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial'
  )
  .required('La contraseña es requerida');

const nameValidation = yup
  .string()
  .min(2, 'El nombre debe tener al menos 2 caracteres')
  .max(50, 'El nombre no puede exceder 50 caracteres')
  .matches(/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/, 'El nombre solo puede contener letras y espacios')
  .required('El nombre es requerido');

// Login validation schema
export const loginSchema = yup.object({
  email: emailValidation,
  password: yup
    .string()
    .required('La contraseña es requerida'),
  remember_me: yup.boolean().default(false),
});

// Register validation schema
export const registerSchema = yup.object({
  name: nameValidation,
  email: emailValidation,
  password: passwordValidation,
  password_confirmation: yup
    .string()
    .oneOf([yup.ref('password')], 'Las contraseñas deben coincidir')
    .required('La confirmación de contraseña es requerida'),
});

// Forgot password validation schema
export const forgotPasswordSchema = yup.object({
  email: emailValidation,
});

// Reset password validation schema
export const resetPasswordSchema = yup.object({
  password: passwordValidation,
  password_confirmation: yup
    .string()
    .oneOf([yup.ref('password')], 'Las contraseñas deben coincidir')
    .required('La confirmación de contraseña es requerida'),
});

// Change password validation schema
export const changePasswordSchema = yup.object({
  current_password: yup
    .string()
    .required('La contraseña actual es requerida'),
  password: passwordValidation,
  password_confirmation: yup
    .string()
    .oneOf([yup.ref('password')], 'Las contraseñas deben coincidir')
    .required('La confirmación de contraseña es requerida'),
});

// Profile update validation schema
export const profileUpdateSchema = yup.object({
  name: nameValidation,
  email: emailValidation,
});

// Contact validation schema
export const contactSchema = yup.object({
  name: nameValidation,
  email: emailValidation,
  subject: yup
    .string()
    .min(5, 'El asunto debe tener al menos 5 caracteres')
    .max(100, 'El asunto no puede exceder 100 caracteres')
    .required('El asunto es requerido'),
  message: yup
    .string()
    .min(10, 'El mensaje debe tener al menos 10 caracteres')
    .max(1000, 'El mensaje no puede exceder 1000 caracteres')
    .required('El mensaje es requerido'),
});

// School registration schema (for admin)
export const schoolRegistrationSchema = yup.object({
  name: yup
    .string()
    .min(3, 'El nombre de la institución debe tener al menos 3 caracteres')
    .max(100, 'El nombre no puede exceder 100 caracteres')
    .required('El nombre de la institución es requerido'),
  email: emailValidation,
  phone: yup
    .string()
    .matches(/^[+]?[0-9\s\-\(\)]+$/, 'Ingrese un número de teléfono válido')
    .min(10, 'El teléfono debe tener al menos 10 dígitos')
    .required('El teléfono es requerido'),
  address: yup
    .string()
    .min(10, 'La dirección debe tener al menos 10 caracteres')
    .max(200, 'La dirección no puede exceder 200 caracteres')
    .required('La dirección es requerida'),
  city: yup
    .string()
    .min(2, 'La ciudad debe tener al menos 2 caracteres')
    .max(50, 'La ciudad no puede exceder 50 caracteres')
    .required('La ciudad es requerida'),
  country: yup
    .string()
    .min(2, 'El país debe tener al menos 2 caracteres')
    .max(50, 'El país no puede exceder 50 caracteres')
    .required('El país es requerido'),
});

export default {
  loginSchema,
  registerSchema,
  forgotPasswordSchema,
  resetPasswordSchema,
  changePasswordSchema,
  profileUpdateSchema,
  contactSchema,
  schoolRegistrationSchema,
};