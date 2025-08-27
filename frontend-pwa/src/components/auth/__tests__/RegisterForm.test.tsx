import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import RegisterForm from '../RegisterForm';
import authSlice from '../../../store/authSlice';
import * as useAuthHook from '../../../hooks/useAuth';

// Mock the useAuth hook
jest.mock('../../../hooks/useAuth');
const mockUseAuth = useAuthHook.useAuth as jest.MockedFunction<typeof useAuthHook.useAuth>;

// Mock react-router-dom
const mockNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

// Create a test store
const createTestStore = () => {
  return configureStore({
    reducer: {
      auth: authSlice,
    },
  });
};

// Create theme for Material-UI
const theme = createTheme();

// Test wrapper component
const TestWrapper: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const store = createTestStore();
  return (
    <Provider store={store}>
      <BrowserRouter>
        <ThemeProvider theme={theme}>
          {children}
        </ThemeProvider>
      </BrowserRouter>
    </Provider>
  );
};

// Mock useAuth return values
const mockAuthValues = {
  // State
  user: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,
  auth: {
    user: null,
    token: null,
    isAuthenticated: false,
    isLoading: false,
    error: null,
  },
  
  // Actions
  login: jest.fn(),
  register: jest.fn(),
  logout: jest.fn(),
  getProfile: jest.fn(),
  updateProfile: jest.fn(),
  refresh: jest.fn(),
  sendPasswordReset: jest.fn(),
  resetUserPassword: jest.fn(),
  verifyUserEmail: jest.fn(),
  resendVerificationEmail: jest.fn(),
  clearAuthError: jest.fn(),
  
  // Utilities
  hasPermission: jest.fn(),
  hasRole: jest.fn(),
  hasAnyRole: jest.fn(),
  checkTokenExpiration: jest.fn(),
  initializeAuth: jest.fn(),
};

describe('RegisterForm', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockUseAuth.mockReturnValue(mockAuthValues);
  });

  const renderRegisterForm = (props = {}) => {
    return render(
      <TestWrapper>
        <RegisterForm {...props} />
      </TestWrapper>
    );
  };

  describe('Rendering', () => {
    test('renders all form elements correctly', () => {
      renderRegisterForm();

      expect(screen.getByLabelText(/nombre completo/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/correo electrónico/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/^contraseña$/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/confirmar contraseña/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /crear cuenta/i })).toBeInTheDocument();
      expect(screen.getByText(/al registrarte, aceptas nuestros términos y condiciones/i)).toBeInTheDocument();
      expect(screen.getByText(/¿ya tienes cuenta\? inicia sesión aquí/i)).toBeInTheDocument();
    });

    test('renders form fields with correct attributes', () => {
      renderRegisterForm();

      const nameField = screen.getByLabelText(/nombre completo/i);
      expect(nameField).toHaveAttribute('type', 'text');
      expect(nameField).toHaveAttribute('autocomplete', 'name');
      expect(nameField).toHaveAttribute('autofocus');

      const emailField = screen.getByLabelText(/correo electrónico/i);
      expect(emailField).toHaveAttribute('type', 'email');
      expect(emailField).toHaveAttribute('autocomplete', 'email');

      const passwordField = screen.getByLabelText(/^contraseña$/i);
      expect(passwordField).toHaveAttribute('type', 'password');
      expect(passwordField).toHaveAttribute('autocomplete', 'new-password');

      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      expect(confirmPasswordField).toHaveAttribute('type', 'password');
      expect(confirmPasswordField).toHaveAttribute('autocomplete', 'new-password');
    });

    test('renders password visibility toggle buttons', () => {
      renderRegisterForm();

      const passwordToggle = screen.getByLabelText(/toggle password visibility/i);
      const confirmPasswordToggle = screen.getByLabelText(/toggle confirm password visibility/i);
      
      expect(passwordToggle).toBeInTheDocument();
      expect(confirmPasswordToggle).toBeInTheDocument();
    });

    test('renders icons for form fields', () => {
      renderRegisterForm();

      // Check for Material-UI icons by their data-testid or role
      expect(screen.getByTestId('PersonIcon') || screen.getByRole('img', { hidden: true })).toBeTruthy();
      expect(screen.getByTestId('EmailIcon') || screen.getByRole('img', { hidden: true })).toBeTruthy();
      expect(screen.getByTestId('LockIcon') || screen.getByRole('img', { hidden: true })).toBeTruthy();
    });
  });

  describe('Form Interactions', () => {
    test('allows typing in name field', async () => {
      renderRegisterForm();

      const nameField = screen.getByLabelText(/nombre completo/i);
      await userEvent.type(nameField, 'Juan Pérez');

      expect(nameField).toHaveValue('Juan Pérez');
    });

    test('allows typing in email field', async () => {
      renderRegisterForm();

      const emailField = screen.getByLabelText(/correo electrónico/i);
      await userEvent.type(emailField, 'juan@example.com');

      expect(emailField).toHaveValue('juan@example.com');
    });

    test('allows typing in password field', async () => {
      renderRegisterForm();

      const passwordField = screen.getByLabelText(/^contraseña$/i);
      await userEvent.type(passwordField, 'password123');

      expect(passwordField).toHaveValue('password123');
    });

    test('allows typing in confirm password field', async () => {
      renderRegisterForm();

      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      await userEvent.type(confirmPasswordField, 'password123');

      expect(confirmPasswordField).toHaveValue('password123');
    });

    test('toggles password visibility', async () => {
      renderRegisterForm();

      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const toggleButton = screen.getByLabelText(/toggle password visibility/i);

      // Initially password should be hidden
      expect(passwordField).toHaveAttribute('type', 'password');

      // Click toggle button to show password
      await userEvent.click(toggleButton);
      expect(passwordField).toHaveAttribute('type', 'text');

      // Click again to hide password
      await userEvent.click(toggleButton);
      expect(passwordField).toHaveAttribute('type', 'password');
    });

    test('toggles confirm password visibility', async () => {
      renderRegisterForm();

      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      const toggleButton = screen.getByLabelText(/toggle confirm password visibility/i);

      // Initially password should be hidden
      expect(confirmPasswordField).toHaveAttribute('type', 'password');

      // Click toggle button to show password
      await userEvent.click(toggleButton);
      expect(confirmPasswordField).toHaveAttribute('type', 'text');

      // Click again to hide password
      await userEvent.click(toggleButton);
      expect(confirmPasswordField).toHaveAttribute('type', 'password');
    });
  });

  describe('Password Strength Indicator', () => {
    test('does not show password strength for empty password', () => {
      renderRegisterForm();

      // Password strength should not be visible initially
      expect(screen.queryByText(/muy débil|débil|regular|fuerte|muy fuerte/i)).not.toBeInTheDocument();
    });

    test('shows password strength indicator when typing password', async () => {
      renderRegisterForm();

      const passwordField = screen.getByLabelText(/^contraseña$/i);
      await userEvent.type(passwordField, 'weak');

      await waitFor(() => {
        expect(screen.getByText(/muy débil|débil/i)).toBeInTheDocument();
      });
    });

    test('shows stronger password indicator for complex passwords', async () => {
      renderRegisterForm();

      const passwordField = screen.getByLabelText(/^contraseña$/i);
      await userEvent.type(passwordField, 'StrongPass123!');

      await waitFor(() => {
        expect(screen.getByText(/fuerte|muy fuerte/i)).toBeInTheDocument();
      });
    });

    test('updates password strength in real-time', async () => {
      renderRegisterForm();

      const passwordField = screen.getByLabelText(/^contraseña$/i);
      
      // Type weak password
      await userEvent.type(passwordField, 'weak');
      await waitFor(() => {
        expect(screen.getByText(/muy débil|débil/i)).toBeInTheDocument();
      });

      // Clear and type stronger password
      await userEvent.clear(passwordField);
      await userEvent.type(passwordField, 'StrongPass123!');
      await waitFor(() => {
        expect(screen.getByText(/fuerte|muy fuerte/i)).toBeInTheDocument();
      });
    });
  });

  describe('Form Validation', () => {
    test('shows validation errors for empty fields', async () => {
      renderRegisterForm();

      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/el nombre es requerido/i)).toBeInTheDocument();
        expect(screen.getByText(/el correo electrónico es requerido/i)).toBeInTheDocument();
        expect(screen.getByText(/la contraseña es requerida/i)).toBeInTheDocument();
      });
    });

    test('shows validation error for invalid email format', async () => {
      renderRegisterForm();

      const emailField = screen.getByLabelText(/correo electrónico/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(emailField, 'invalid-email');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/formato de correo electrónico inválido/i)).toBeInTheDocument();
      });
    });

    test('shows validation error for short password', async () => {
      renderRegisterForm();

      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(passwordField, '123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/la contraseña debe tener al menos 8 caracteres/i)).toBeInTheDocument();
      });
    });

    test('shows validation error for password mismatch', async () => {
      renderRegisterForm();

      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(passwordField, 'password123');
      await userEvent.type(confirmPasswordField, 'different123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/las contraseñas no coinciden/i)).toBeInTheDocument();
      });
    });

    test('shows validation error for short name', async () => {
      renderRegisterForm();

      const nameField = screen.getByLabelText(/nombre completo/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(nameField, 'A');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/el nombre debe tener al menos 2 caracteres/i)).toBeInTheDocument();
      });
    });
  });

  describe('Form Submission', () => {
    test('calls register function with correct data on valid submission', async () => {
      const mockRegister = jest.fn().mockResolvedValue({ user: null, token: null, message: 'Success' });
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        register: mockRegister,
      });

      renderRegisterForm();

      const nameField = screen.getByLabelText(/nombre completo/i);
      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(nameField, 'Juan Pérez');
      await userEvent.type(emailField, 'juan@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.type(confirmPasswordField, 'password123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(mockRegister).toHaveBeenCalledWith({
          name: 'Juan Pérez',
          email: 'juan@example.com',
          password: 'password123',
          password_confirmation: 'password123',
        });
      });
    });

    test('calls onSuccess callback when provided', async () => {
      const mockOnSuccess = jest.fn();
      const mockRegister = jest.fn().mockResolvedValue({ user: null, token: null, message: 'Success' });
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        register: mockRegister,
      });

      renderRegisterForm({ onSuccess: mockOnSuccess });

      const nameField = screen.getByLabelText(/nombre completo/i);
      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(nameField, 'Juan Pérez');
      await userEvent.type(emailField, 'juan@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.type(confirmPasswordField, 'password123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(mockOnSuccess).toHaveBeenCalled();
      });
    });

    test('navigates to dashboard on successful registration without onSuccess callback', async () => {
      const mockRegister = jest.fn().mockResolvedValue({ user: null, token: null, message: 'Success' });
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        register: mockRegister,
      });

      renderRegisterForm();

      const nameField = screen.getByLabelText(/nombre completo/i);
      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(nameField, 'Juan Pérez');
      await userEvent.type(emailField, 'juan@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.type(confirmPasswordField, 'password123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/dashboard');
      });
    });

    test('clears auth error before submission', async () => {
      const mockRegister = jest.fn().mockResolvedValue({ user: null, token: null, message: 'Success' });
      const mockClearAuthError = jest.fn();
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        register: mockRegister,
        clearAuthError: mockClearAuthError,
      });

      renderRegisterForm();

      const nameField = screen.getByLabelText(/nombre completo/i);
      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(nameField, 'Juan Pérez');
      await userEvent.type(emailField, 'juan@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.type(confirmPasswordField, 'password123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(mockClearAuthError).toHaveBeenCalled();
      });
    });
  });

  describe('Error Handling', () => {
    test('displays auth error when present', () => {
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        error: 'Registration failed',
      });

      renderRegisterForm();

      expect(screen.getByText('Registration failed')).toBeInTheDocument();
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    test('allows closing auth error alert', async () => {
      const mockClearAuthError = jest.fn();
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        error: 'Registration failed',
        clearAuthError: mockClearAuthError,
      });

      renderRegisterForm();

      const closeButton = screen.getByRole('button', { name: /close/i });
      await userEvent.click(closeButton);

      expect(mockClearAuthError).toHaveBeenCalled();
    });

    test('handles server validation errors', async () => {
      const mockRegister = jest.fn().mockRejectedValue({
        errors: {
          email: ['The email has already been taken'],
          name: ['The name field is required'],
        },
      });
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        register: mockRegister,
      });

      renderRegisterForm();

      const nameField = screen.getByLabelText(/nombre completo/i);
      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(nameField, 'Juan Pérez');
      await userEvent.type(emailField, 'juan@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.type(confirmPasswordField, 'password123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText('The email has already been taken')).toBeInTheDocument();
        expect(screen.getByText('The name field is required')).toBeInTheDocument();
      });
    });
  });

  describe('Loading States', () => {
    test('shows loading state during submission', () => {
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        isLoading: true,
      });

      renderRegisterForm();

      expect(screen.getByText(/registrando.../i)).toBeInTheDocument();
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });

    test('disables form fields during loading', () => {
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        isLoading: true,
      });

      renderRegisterForm();

      expect(screen.getByLabelText(/nombre completo/i)).toBeDisabled();
      expect(screen.getByLabelText(/correo electrónico/i)).toBeDisabled();
      expect(screen.getByLabelText(/^contraseña$/i)).toBeDisabled();
      expect(screen.getByLabelText(/confirmar contraseña/i)).toBeDisabled();
      expect(screen.getByLabelText(/toggle password visibility/i)).toBeDisabled();
      expect(screen.getByLabelText(/toggle confirm password visibility/i)).toBeDisabled();
      expect(screen.getByRole('button', { name: /registrando.../i })).toBeDisabled();
    });

    test('disables form fields during form submission', async () => {
      let resolveRegister: (value: any) => void;
      const mockRegister = jest.fn().mockImplementation(() => new Promise(resolve => {
        resolveRegister = resolve;
      }));
      
      mockUseAuth.mockReturnValue({
        ...mockAuthValues,
        register: mockRegister,
      });

      renderRegisterForm();

      const nameField = screen.getByLabelText(/nombre completo/i);
      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/^contraseña$/i);
      const confirmPasswordField = screen.getByLabelText(/confirmar contraseña/i);
      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });

      await userEvent.type(nameField, 'Juan Pérez');
      await userEvent.type(emailField, 'juan@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.type(confirmPasswordField, 'password123');
      await userEvent.click(submitButton);

      // During submission, fields should be disabled
      await waitFor(() => {
        expect(screen.getByLabelText(/nombre completo/i)).toBeDisabled();
        expect(screen.getByLabelText(/correo electrónico/i)).toBeDisabled();
        expect(screen.getByLabelText(/^contraseña$/i)).toBeDisabled();
        expect(screen.getByLabelText(/confirmar contraseña/i)).toBeDisabled();
      });

      // Resolve the register promise
      resolveRegister!({ user: null, token: null, message: 'Success' });
    });
  });

  describe('Navigation Links', () => {
    test('renders login link with correct route', () => {
      renderRegisterForm();

      const loginLink = screen.getByText(/¿ya tienes cuenta\? inicia sesión aquí/i);
      expect(loginLink.closest('a')).toHaveAttribute('href', '/login');
    });
  });

  describe('Terms and Conditions', () => {
    test('displays terms and conditions text', () => {
      renderRegisterForm();

      expect(screen.getByText(/al registrarte, aceptas nuestros términos y condiciones/i)).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    test('has proper form structure', () => {
      renderRegisterForm();

      const form = screen.getByRole('form');
      expect(form).toBeInTheDocument();
      expect(form).toHaveAttribute('novalidate');
    });

    test('has proper labeling for form controls', () => {
      renderRegisterForm();

      expect(screen.getByLabelText(/nombre completo/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/correo electrónico/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/^contraseña$/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/confirmar contraseña/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/toggle password visibility/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/toggle confirm password visibility/i)).toBeInTheDocument();
    });

    test('shows error messages with proper association', async () => {
      renderRegisterForm();

      const submitButton = screen.getByRole('button', { name: /crear cuenta/i });
      await userEvent.click(submitButton);

      await waitFor(() => {
        const nameField = screen.getByLabelText(/nombre completo/i);
        const emailField = screen.getByLabelText(/correo electrónico/i);
        const passwordField = screen.getByLabelText(/^contraseña$/i);
        
        expect(nameField).toHaveAttribute('aria-invalid', 'true');
        expect(emailField).toHaveAttribute('aria-invalid', 'true');
        expect(passwordField).toHaveAttribute('aria-invalid', 'true');
      });
    });
  });
});