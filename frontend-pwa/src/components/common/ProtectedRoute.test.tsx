import React from 'react';
import { render, screen } from '@testing-library/react';
import { BrowserRouter, MemoryRouter } from 'react-router-dom';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import ProtectedRoute from './ProtectedRoute';
import { useAuth } from '../../hooks/useAuth';

// Mock the useAuth hook
jest.mock('../../hooks/useAuth');
const mockUseAuth = useAuth as jest.MockedFunction<typeof useAuth>;

// Mock Navigate component
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  Navigate: ({ to, state }: { to: string; state?: any }) => (
    <div data-testid="navigate" data-to={to} data-state={JSON.stringify(state)}>
      Navigate to {to}
    </div>
  ),
}));

const theme = createTheme();

const TestWrapper: React.FC<{ children: React.ReactNode; initialEntries?: string[] }> = ({ 
  children, 
  initialEntries = ['/'] 
}) => (
  <ThemeProvider theme={theme}>
    <MemoryRouter initialEntries={initialEntries}>
      {children}
    </MemoryRouter>
  </ThemeProvider>
);

const TestChild = () => <div data-testid="protected-content">Protected Content</div>;

// Base mock auth return value
const baseMockAuth = {
  error: null,
  auth: { user: null, token: null, isAuthenticated: false, isLoading: false, error: null },
  hasRole: jest.fn(),
  hasPermission: jest.fn(),
  hasAnyRole: jest.fn(),
  login: jest.fn(),
  logout: jest.fn(),
  register: jest.fn(),
  updateProfile: jest.fn(),
  refresh: jest.fn(),
  sendPasswordReset: jest.fn(),
  resetUserPassword: jest.fn(),
  verifyUserEmail: jest.fn(),
  resendVerificationEmail: jest.fn(),
  clearAuthError: jest.fn(),
  getProfile: jest.fn(),
  checkTokenExpiration: jest.fn(),
  initializeAuth: jest.fn(),
};

describe('ProtectedRoute', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Loading State', () => {
    it('should show loading spinner when authentication is loading', () => {
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: false,
        isLoading: true,
        user: null,
      });

      render(
        <TestWrapper>
          <ProtectedRoute>
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      expect(screen.getByRole('progressbar')).toBeInTheDocument();
      expect(screen.getByText('Verificando autenticación...')).toBeInTheDocument();
      expect(screen.queryByTestId('protected-content')).not.toBeInTheDocument();
    });
  });

  describe('Unauthenticated State', () => {
    it('should redirect to login when user is not authenticated', () => {
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: false,
        isLoading: false,
        user: null,
      });

      render(
        <TestWrapper initialEntries={['/dashboard']}>
          <ProtectedRoute>
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      const navigate = screen.getByTestId('navigate');
      expect(navigate).toHaveAttribute('data-to', '/login');
      expect(navigate).toHaveAttribute('data-state', JSON.stringify({ from: '/dashboard' }));
      expect(screen.queryByTestId('protected-content')).not.toBeInTheDocument();
    });

    it('should redirect to custom fallback path when specified', () => {
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: false,
        isLoading: false,
        user: null,
      });

      render(
        <TestWrapper>
          <ProtectedRoute fallbackPath="/signin">
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      const navigate = screen.getByTestId('navigate');
      expect(navigate).toHaveAttribute('data-to', '/signin');
    });

    it('should redirect to login when user is null even if authenticated is true', () => {
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: true,
        isLoading: false,
        user: null,
      });

      render(
        <TestWrapper>
          <ProtectedRoute>
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      const navigate = screen.getByTestId('navigate');
      expect(navigate).toHaveAttribute('data-to', '/login');
    });
  });

  describe('Authenticated State', () => {
    const mockUser = {
      id: '1',
      email: 'test@example.com',
      name: 'Test User',
      role: 'user',
      permissions: ['read', 'write'],
      email_verified_at: '2023-01-01T00:00:00Z',
      created_at: '2023-01-01T00:00:00Z',
      updated_at: '2023-01-01T00:00:00Z',
    };

    it('should render children when user is authenticated and no role/permission requirements', () => {
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: true,
        isLoading: false,
        user: mockUser,
      });

      render(
        <TestWrapper>
          <ProtectedRoute>
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      expect(screen.getByTestId('protected-content')).toBeInTheDocument();
      expect(screen.queryByTestId('navigate')).not.toBeInTheDocument();
    });
  });

  describe('Role-based Access Control', () => {
    const mockUser = {
      id: '1',
      email: 'admin@example.com',
      name: 'Admin User',
      role: 'admin',
      permissions: ['read', 'write', 'delete'],
      email_verified_at: '2023-01-01T00:00:00Z',
      created_at: '2023-01-01T00:00:00Z',
      updated_at: '2023-01-01T00:00:00Z',
    };

    it('should render children when user has required role', () => {
      const mockHasAnyRole = jest.fn().mockReturnValue(true);
      
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: true,
        isLoading: false,
        user: mockUser,
        hasAnyRole: mockHasAnyRole,
      });

      render(
        <TestWrapper>
          <ProtectedRoute requiredRoles={['admin']}>
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      expect(mockHasAnyRole).toHaveBeenCalledWith(['admin']);
      expect(screen.getByTestId('protected-content')).toBeInTheDocument();
    });

    it('should redirect to unauthorized when user lacks required role', () => {
      const mockHasAnyRole = jest.fn().mockReturnValue(false);
      
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: true,
        isLoading: false,
        user: mockUser,
        hasAnyRole: mockHasAnyRole,
      });

      render(
        <TestWrapper initialEntries={['/admin']}>
          <ProtectedRoute requiredRoles={['admin']}>
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      const navigate = screen.getByTestId('navigate');
      expect(navigate).toHaveAttribute('data-to', '/unauthorized');
      
      const state = JSON.parse(navigate.getAttribute('data-state') || '{}');
      expect(state.from).toBe('/admin');
      expect(state.requiredRoles).toEqual(['admin']);
      expect(state.userRoles).toEqual(['admin']);
    });
  });

  describe('Permission-based Access Control', () => {
    const mockUser = {
      id: '1',
      email: 'user@example.com',
      name: 'Regular User',
      role: 'user',
      permissions: ['read', 'write'],
      email_verified_at: '2023-01-01T00:00:00Z',
      created_at: '2023-01-01T00:00:00Z',
      updated_at: '2023-01-01T00:00:00Z',
    };

    it('should render children when user has all required permissions', () => {
      const mockHasPermission = jest.fn()
        .mockReturnValueOnce(true) // read permission
        .mockReturnValueOnce(true); // write permission
      
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: true,
        isLoading: false,
        user: mockUser,
        hasPermission: mockHasPermission,
      });

      render(
        <TestWrapper>
          <ProtectedRoute requiredPermissions={['read', 'write']}>
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      expect(mockHasPermission).toHaveBeenCalledWith('read');
      expect(mockHasPermission).toHaveBeenCalledWith('write');
      expect(screen.getByTestId('protected-content')).toBeInTheDocument();
    });

    it('should redirect to unauthorized when user lacks required permissions', () => {
      const mockHasPermission = jest.fn()
        .mockReturnValueOnce(true)  // read permission
        .mockReturnValueOnce(false); // delete permission (missing)
      
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: true,
        isLoading: false,
        user: mockUser,
        hasPermission: mockHasPermission,
      });

      render(
        <TestWrapper initialEntries={['/admin/delete']}>
          <ProtectedRoute requiredPermissions={['read', 'delete']}>
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      const navigate = screen.getByTestId('navigate');
      expect(navigate).toHaveAttribute('data-to', '/unauthorized');
      
      const state = JSON.parse(navigate.getAttribute('data-state') || '{}');
      expect(state.from).toBe('/admin/delete');
      expect(state.requiredPermissions).toEqual(['read', 'delete']);
      expect(state.userPermissions).toEqual(['read', 'write']);
    });
  });

  describe('Combined Role and Permission Requirements', () => {
    const mockUser = {
      id: '1',
      email: 'admin@example.com',
      name: 'Admin User',
      role: 'admin',
      permissions: ['read', 'write', 'delete'],
      email_verified_at: '2023-01-01T00:00:00Z',
      created_at: '2023-01-01T00:00:00Z',
      updated_at: '2023-01-01T00:00:00Z',
    };

    it('should render children when user has both required roles and permissions', () => {
      const mockHasAnyRole = jest.fn().mockReturnValue(true);
      const mockHasPermission = jest.fn().mockReturnValue(true);
      
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: true,
        isLoading: false,
        user: mockUser,
        hasPermission: mockHasPermission,
        hasAnyRole: mockHasAnyRole,
      });

      render(
        <TestWrapper>
          <ProtectedRoute 
            requiredRoles={['admin']} 
            requiredPermissions={['delete']}
          >
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      expect(mockHasAnyRole).toHaveBeenCalledWith(['admin']);
      expect(mockHasPermission).toHaveBeenCalledWith('delete');
      expect(screen.getByTestId('protected-content')).toBeInTheDocument();
    });

    it('should redirect to unauthorized when user has role but lacks permission', () => {
      const mockHasAnyRole = jest.fn().mockReturnValue(true);
      const mockHasPermission = jest.fn().mockReturnValue(false);
      
      mockUseAuth.mockReturnValue({
        ...baseMockAuth,
        isAuthenticated: true,
        isLoading: false,
        user: mockUser,
        hasPermission: mockHasPermission,
        hasAnyRole: mockHasAnyRole,
      });

      render(
        <TestWrapper>
          <ProtectedRoute 
            requiredRoles={['admin']} 
            requiredPermissions={['super_admin']}
          >
            <TestChild />
          </ProtectedRoute>
        </TestWrapper>
      );

      // Should check role first, then permission
      expect(mockHasAnyRole).toHaveBeenCalledWith(['admin']);
      expect(mockHasPermission).toHaveBeenCalledWith('super_admin');
      
      const navigate = screen.getByTestId('navigate');
      expect(navigate).toHaveAttribute('data-to', '/unauthorized');
    });
  });
});