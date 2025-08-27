import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import { configureStore } from '@reduxjs/toolkit';
import LoginForm from '../LoginForm';
import { useAuth } from '../../../hooks/useAuth';

// Mock useAuth hook
const mockLogin = jest.fn();
const mockClearAuthError = jest.fn();

const mockUseAuth = {
  login: mockLogin,
  isLoading: false,
  error: null,
  clearAuthError: mockClearAuthError,
  user: null,
  isAuthenticated: false,
  auth: null,
  register: jest.fn(),
  logout: jest.fn(),
  getProfile: jest.fn(),
  updateProfile: jest.fn(),
  refresh: jest.fn(),
  sendPasswordReset: jest.fn(),
  resetUserPassword: jest.fn(),
  verifyUserEmail: jest.fn(),
  resendVerificationEmail: jest.fn(),
  hasPermission: jest.fn(),
  hasRole: jest.fn(),
  hasAnyRole: jest.fn(),
  checkTokenExpiration: jest.fn(),
  initializeAuth: jest.fn(),
};

jest.mock('../../../hooks/useAuth', () => ({
  useAuth: jest.fn(() => mockUseAuth),
}));

// Mock react-router-dom
const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
  useLocation: () => ({ state: null }),
}));

// Create theme for Material-UI
const theme = createTheme();

// Create test store
const createTestStore = () => {
  return configureStore({
    reducer: {
      auth: (state = { user: null, token: null, isAuthenticated: false }) => state,
    },
  });
};

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

// Helper function to render LoginForm
const renderLoginForm = (props = {}) => {
  return render(<LoginForm {...props} />, { wrapper: TestWrapper });
};

describe('LoginForm', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Reset mock implementation
    (useAuth as jest.Mock).mockReturnValue(mockUseAuth);
  });

  describe('Rendering', () => {
    test('renders login form with all required elements', () => {
      renderLoginForm();

      expect(screen.getByLabelText(/correo electrónico/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/contraseña/i)).toBeInTheDocument();
      expect(screen.getByRole('checkbox', { name: /recordarme/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /iniciar sesión/i })).toBeInTheDocument();
      expect(screen.getByText(/¿olvidaste tu contraseña?/i)).toBeInTheDocument();
      expect(screen.getByText(/¿no tienes cuenta?/i)).toBeInTheDocument();
    });

    test('renders password visibility toggle button', () => {
      renderLoginForm();

      const passwordField = screen.getByLabelText(/contraseña/i);
      expect(passwordField).toHaveAttribute('type', 'password');
      
      const toggleButton = screen.getByRole('button', { name: /toggle password visibility/i });
      expect(toggleButton).toBeInTheDocument();
    });
  });

  describe('Form Interactions', () => {
    test('allows typing in email field', async () => {
      renderLoginForm();

      const emailField = screen.getByLabelText(/correo electrónico/i);
      await userEvent.type(emailField, 'test@example.com');

      expect(emailField).toHaveValue('test@example.com');
    });

    test('allows typing in password field', async () => {
      renderLoginForm();

      const passwordField = screen.getByLabelText(/contraseña/i);
      await userEvent.type(passwordField, 'password123');

      expect(passwordField).toHaveValue('password123');
    });

    test('toggles password visibility', async () => {
      renderLoginForm();

      const passwordField = screen.getByLabelText(/contraseña/i);
      const toggleButton = screen.getByRole('button', { name: /toggle password visibility/i });

      expect(passwordField).toHaveAttribute('type', 'password');

      // Click toggle button to show password
      await userEvent.click(toggleButton);
      expect(passwordField).toHaveAttribute('type', 'text');

      // Click again to hide password
      await userEvent.click(toggleButton);
      expect(passwordField).toHaveAttribute('type', 'password');
    });

    test('toggles remember me checkbox', async () => {
      renderLoginForm();

      const checkbox = screen.getByRole('checkbox', { name: /recordarme/i });
      
      expect(checkbox).not.toBeChecked();

      // Click to check
      await userEvent.click(checkbox);
      expect(checkbox).toBeChecked();

      // Click again to uncheck
      await userEvent.click(checkbox);
      expect(checkbox).not.toBeChecked();
    });
  });

  describe('Form Validation', () => {
    test('shows validation errors for empty fields', async () => {
      renderLoginForm();

      const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/el correo electrónico es requerido/i)).toBeInTheDocument();
        expect(screen.getByText(/la contraseña es requerida/i)).toBeInTheDocument();
      });
    });

    test('shows validation error for invalid email format', async () => {
      renderLoginForm();

      const emailField = screen.getByLabelText(/correo electrónico/i);
      const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });

      await userEvent.type(emailField, 'invalid-email');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/formato de correo inválido/i)).toBeInTheDocument();
      });
    });

    test('shows validation error for short password', async () => {
      renderLoginForm();

      const passwordField = screen.getByLabelText(/contraseña/i);
      const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });

      await userEvent.type(passwordField, '123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/la contraseña debe tener al menos 6 caracteres/i)).toBeInTheDocument();
      });
    });
  });

  describe('Form Submission', () => {
    test('calls login function with correct data on valid submission', async () => {
      mockLogin.mockResolvedValue({});
      renderLoginForm();

      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/contraseña/i);
      const rememberCheckbox = screen.getByRole('checkbox', { name: /recordarme/i });
      const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });

      await userEvent.type(emailField, 'test@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.click(rememberCheckbox);
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(mockLogin).toHaveBeenCalledWith({
          email: 'test@example.com',
          password: 'password123',
          remember: true,
        });
      });
    });

    test('calls onSuccess callback when provided', async () => {
      const mockOnSuccess = jest.fn();
      mockLogin.mockResolvedValue({});
      render(<LoginForm onSuccess={mockOnSuccess} />, { wrapper: TestWrapper });

      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/contraseña/i);
      const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });

      await userEvent.type(emailField, 'test@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(mockOnSuccess).toHaveBeenCalled();
      });
    });

    test('clears auth error before submission', async () => {
      mockLogin.mockResolvedValue({});
      renderLoginForm();

      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/contraseña/i);
      const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });

      await userEvent.type(emailField, 'test@example.com');
      await userEvent.type(passwordField, 'password123');
      await userEvent.click(submitButton);

      await waitFor(() => {
        expect(mockClearAuthError).toHaveBeenCalled();
      });
    });
  });

  describe('Error Handling', () => {
    test('displays auth error when present', () => {
      (useAuth as jest.Mock).mockReturnValue({
        ...mockUseAuth,
        error: 'Invalid credentials',
      });
      
      renderLoginForm();

      expect(screen.getByText('Invalid credentials')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /close/i })).toBeInTheDocument();
    });

    test('allows closing auth error alert', async () => {
      (useAuth as jest.Mock).mockReturnValue({
        ...mockUseAuth,
        error: 'Login failed',
      });
      
      renderLoginForm();

      const closeButton = screen.getByRole('button', { name: /close/i });
      await userEvent.click(closeButton);

      expect(mockClearAuthError).toHaveBeenCalled();
    });
  });

  describe('Loading States', () => {
    test('shows loading text during form submission', () => {
      (useAuth as jest.Mock).mockReturnValue({
        ...mockUseAuth,
        isLoading: true,
      });
      
      renderLoginForm();

      expect(screen.getByText(/iniciando sesión/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /iniciando sesión/i })).toBeDisabled();
    });

    test('disables form fields during loading', () => {
      (useAuth as jest.Mock).mockReturnValue({
        ...mockUseAuth,
        isLoading: true,
      });
      
      renderLoginForm();

      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/contraseña/i);
      const submitButton = screen.getByRole('button', { name: /iniciando sesión/i });

      expect(emailField).toBeDisabled();
      expect(passwordField).toBeDisabled();
      expect(submitButton).toBeDisabled();
    });
  });

  describe('Navigation Links', () => {
    test('renders forgot password link', () => {
      renderLoginForm();

      const forgotPasswordLink = screen.getByText(/¿olvidaste tu contraseña?/i);
      expect(forgotPasswordLink).toBeInTheDocument();
      expect(forgotPasswordLink.closest('a')).toHaveAttribute('href', '/forgot-password');
    });

    test('renders register link', () => {
      renderLoginForm();

      const registerLink = screen.getByText(/regístrate aquí/i);
      expect(registerLink).toBeInTheDocument();
      expect(registerLink.closest('a')).toHaveAttribute('href', '/register');
    });
  });

  describe('Accessibility', () => {
    test('has proper form structure and labeling', () => {
      renderLoginForm();

      const form = screen.getByRole('form', { name: /iniciar sesión/i });
      expect(form).toBeInTheDocument();

      const emailField = screen.getByLabelText(/correo electrónico/i);
      const passwordField = screen.getByLabelText(/contraseña/i);
      
      expect(emailField).toHaveAttribute('type', 'email');
      expect(passwordField).toHaveAttribute('type', 'password');
      expect(emailField).toBeRequired();
      expect(passwordField).toBeRequired();
    });

    test('associates error messages with form fields', async () => {
      renderLoginForm();

      const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
      await userEvent.click(submitButton);

      await waitFor(() => {
        const emailField = screen.getByLabelText(/correo electrónico/i);
        const passwordField = screen.getByLabelText(/contraseña/i);
        
        expect(emailField).toHaveAttribute('aria-invalid', 'true');
        expect(passwordField).toHaveAttribute('aria-invalid', 'true');
      });
    });
  });
});