import AuthService from '../authService';
import ApiService from '../api';
import {
  LoginCredentials,
  RegisterData,
  ForgotPasswordData,
  ResetPasswordData,
  AuthResponse,
  User,
  ApiResponse,
} from '../../types';

// Mock ApiService
jest.mock('../api');
const mockApiService = ApiService as jest.Mocked<typeof ApiService>;

// Mock localStorage
const mockLocalStorage = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
Object.defineProperty(window, 'localStorage', {
  value: mockLocalStorage,
});

// Mock console.warn and console.error
const mockConsoleWarn = jest.spyOn(console, 'warn').mockImplementation();
const mockConsoleError = jest.spyOn(console, 'error').mockImplementation();

describe('AuthService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockLocalStorage.getItem.mockClear();
    mockLocalStorage.setItem.mockClear();
    mockLocalStorage.removeItem.mockClear();
    mockLocalStorage.clear.mockClear();
  });

  afterAll(() => {
    mockConsoleWarn.mockRestore();
    mockConsoleError.mockRestore();
  });

  describe('login', () => {
    const mockCredentials: LoginCredentials = {
      email: 'test@example.com',
      password: 'password123',
      remember_me: false,
    };

    const mockAuthResponse: AuthResponse = {
      token: 'mock-token',
      expires_in: 3600,
      token_type: 'Bearer',
      user: {
        id: '1',
        name: 'Test User',
        email: 'test@example.com',
        role: 'student',
        permissions: ['read'],
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
      },
    };

    it('should login successfully and store token and user data', async () => {
      mockApiService.post.mockResolvedValue({
        data: mockAuthResponse,
        status: 200,
        success: true,
      });

      const result = await AuthService.login(mockCredentials);

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/login', mockCredentials);
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith('auth_token', 'mock-token');
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'user_data',
        JSON.stringify(mockAuthResponse.user)
      );
      expect(result).toEqual(mockAuthResponse);
    });

    it('should not store data if no token in response', async () => {
      const responseWithoutToken = { ...mockAuthResponse, token: '' };
      mockApiService.post.mockResolvedValue({
        data: responseWithoutToken,
        status: 200,
        success: true,
      });

      const result = await AuthService.login(mockCredentials);

      expect(mockLocalStorage.setItem).not.toHaveBeenCalled();
      expect(result).toEqual(responseWithoutToken);
    });

    it('should throw error when API call fails', async () => {
      const error = new Error('Login failed');
      mockApiService.post.mockRejectedValue(error);

      await expect(AuthService.login(mockCredentials)).rejects.toThrow('Login failed');
    });
  });

  describe('register', () => {
    const mockRegisterData: RegisterData = {
      name: 'Test User',
      email: 'test@example.com',
      password: 'password123',
      password_confirmation: 'password123',
    };

    const mockAuthResponse: AuthResponse = {
      token: 'mock-token',
      expires_in: 3600,
      token_type: 'Bearer',
      user: {
        id: '1',
        name: 'Test User',
        email: 'test@example.com',
        role: 'student',
        permissions: ['read'],
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
      },
    };

    it('should register successfully and store token and user data', async () => {
      mockApiService.post.mockResolvedValue({
        data: mockAuthResponse,
        status: 201,
        success: true,
      });

      const result = await AuthService.register(mockRegisterData);

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/register', mockRegisterData);
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith('auth_token', 'mock-token');
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'user_data',
        JSON.stringify(mockAuthResponse.user)
      );
      expect(result).toEqual(mockAuthResponse);
    });
  });

  describe('logout', () => {
    it('should logout successfully and clear localStorage', async () => {
      mockApiService.post.mockResolvedValue({
        data: {},
        status: 200,
        success: true,
      });

      await AuthService.logout();

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/logout');
      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('auth_token');
      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('user_data');
    });

    it('should clear localStorage even if API call fails', async () => {
      const error = new Error('Logout failed');
      mockApiService.post.mockRejectedValue(error);

      await AuthService.logout();

      expect(mockConsoleWarn).toHaveBeenCalledWith('Logout API call failed:', error);
      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('auth_token');
      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('user_data');
    });
  });

  describe('getProfile', () => {
    const mockUser: User = {
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      role: 'student',
      permissions: ['read'],
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    };

    it('should get profile and update stored user data', async () => {
      mockApiService.get.mockResolvedValue({
        data: mockUser,
        status: 200,
        success: true,
      });

      const result = await AuthService.getProfile();

      expect(mockApiService.get).toHaveBeenCalledWith('/auth/profile');
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'user_data',
        JSON.stringify(mockUser)
      );
      expect(result).toEqual(mockUser);
    });
  });

  describe('updateProfile', () => {
    const mockUser: User = {
      id: '1',
      name: 'Updated User',
      email: 'test@example.com',
      role: 'student',
      permissions: ['read'],
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    };

    const updateData = { name: 'Updated User' };

    it('should update profile and store updated user data', async () => {
      mockApiService.put.mockResolvedValue({
        data: mockUser,
        status: 200,
        success: true,
      });

      const result = await AuthService.updateProfile(updateData);

      expect(mockApiService.put).toHaveBeenCalledWith('/auth/profile', updateData);
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'user_data',
        JSON.stringify(mockUser)
      );
      expect(result).toEqual(mockUser);
    });
  });

  describe('refreshToken', () => {
    const mockAuthResponse: AuthResponse = {
      token: 'new-token',
      expires_in: 3600,
      token_type: 'Bearer',
      user: {
        id: '1',
        name: 'Test User',
        email: 'test@example.com',
        role: 'student',
        permissions: ['read'],
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
      },
    };

    it('should refresh token and update stored data', async () => {
      mockApiService.post.mockResolvedValue({
        data: mockAuthResponse,
        status: 200,
        success: true,
      });

      const result = await AuthService.refreshToken();

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/refresh');
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith('auth_token', 'new-token');
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'user_data',
        JSON.stringify(mockAuthResponse.user)
      );
      expect(result).toEqual(mockAuthResponse);
    });
  });

  describe('forgotPassword', () => {
    const mockData: ForgotPasswordData = {
      email: 'test@example.com',
    };

    const mockResponse: ApiResponse = {
      data: null,
      message: 'Password reset email sent',
      status: 200,
      success: true,
    };

    it('should send forgot password request', async () => {
      mockApiService.post.mockResolvedValue(mockResponse);

      const result = await AuthService.forgotPassword(mockData);

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/forgot-password', mockData);
      expect(result).toEqual(mockResponse);
    });
  });

  describe('resetPassword', () => {
    const mockData: ResetPasswordData = {
      token: 'reset-token',
      email: 'test@example.com',
      password: 'newpassword123',
      password_confirmation: 'newpassword123',
    };

    const mockResponse: ApiResponse = {
      data: null,
      message: 'Password reset successfully',
      status: 200,
      success: true,
    };

    it('should reset password', async () => {
      mockApiService.post.mockResolvedValue(mockResponse);

      const result = await AuthService.resetPassword(mockData);

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/reset-password', mockData);
      expect(result).toEqual(mockResponse);
    });
  });

  describe('verifyEmail', () => {
    const mockResponse: ApiResponse = {
      data: null,
      message: 'Email verified successfully',
      status: 200,
      success: true,
    };

    it('should verify email', async () => {
      mockApiService.post.mockResolvedValue(mockResponse);

      const result = await AuthService.verifyEmail('verify-token');

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/verify-email', {
        token: 'verify-token',
      });
      expect(result).toEqual(mockResponse);
    });
  });

  describe('resendVerification', () => {
    const mockResponse: ApiResponse = {
      data: null,
      message: 'Verification email sent',
      status: 200,
      success: true,
    };

    it('should resend verification email', async () => {
      mockApiService.post.mockResolvedValue(mockResponse);

      const result = await AuthService.resendVerification();

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/resend-verification');
      expect(result).toEqual(mockResponse);
    });
  });

  describe('changePassword', () => {
    const mockData = {
      current_password: 'oldpassword',
      password: 'newpassword123',
      password_confirmation: 'newpassword123',
    };

    const mockResponse: ApiResponse = {
      data: null,
      message: 'Password changed successfully',
      status: 200,
      success: true,
    };

    it('should change password', async () => {
      mockApiService.post.mockResolvedValue(mockResponse);

      const result = await AuthService.changePassword(mockData);

      expect(mockApiService.post).toHaveBeenCalledWith('/auth/change-password', mockData);
      expect(result).toEqual(mockResponse);
    });
  });

  describe('isAuthenticated', () => {
    it('should return true when token and user data exist', () => {
      mockLocalStorage.getItem.mockImplementation((key) => {
        if (key === 'auth_token') return 'mock-token';
        if (key === 'user_data') return JSON.stringify({ id: '1', name: 'Test' });
        return null;
      });

      const result = AuthService.isAuthenticated();

      expect(result).toBe(true);
    });

    it('should return false when token is missing', () => {
      mockLocalStorage.getItem.mockImplementation((key) => {
        if (key === 'auth_token') return null;
        if (key === 'user_data') return JSON.stringify({ id: '1', name: 'Test' });
        return null;
      });

      const result = AuthService.isAuthenticated();

      expect(result).toBe(false);
    });

    it('should return false when user data is missing', () => {
      mockLocalStorage.getItem.mockImplementation((key) => {
        if (key === 'auth_token') return 'mock-token';
        if (key === 'user_data') return null;
        return null;
      });

      const result = AuthService.isAuthenticated();

      expect(result).toBe(false);
    });
  });

  describe('getStoredUser', () => {
    const mockUser = {
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      role: 'student',
      permissions: ['read'],
    };

    it('should return parsed user data', () => {
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockUser));

      const result = AuthService.getStoredUser();

      expect(mockLocalStorage.getItem).toHaveBeenCalledWith('user_data');
      expect(result).toEqual(mockUser);
    });

    it('should return null when no user data', () => {
      mockLocalStorage.getItem.mockReturnValue(null);

      const result = AuthService.getStoredUser();

      expect(result).toBeNull();
    });

    it('should handle invalid JSON and clear storage', () => {
      mockLocalStorage.getItem.mockReturnValue('invalid-json');

      const result = AuthService.getStoredUser();

      expect(mockConsoleError).toHaveBeenCalledWith(
        'Error parsing stored user data:',
        expect.any(SyntaxError)
      );
      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('user_data');
      expect(result).toBeNull();
    });
  });

  describe('getStoredToken', () => {
    it('should return stored token', () => {
      mockLocalStorage.getItem.mockReturnValue('mock-token');

      const result = AuthService.getStoredToken();

      expect(mockLocalStorage.getItem).toHaveBeenCalledWith('auth_token');
      expect(result).toBe('mock-token');
    });

    it('should return null when no token', () => {
      mockLocalStorage.getItem.mockReturnValue(null);

      const result = AuthService.getStoredToken();

      expect(result).toBeNull();
    });
  });

  describe('clearAuthData', () => {
    it('should clear auth token and user data', () => {
      AuthService.clearAuthData();

      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('auth_token');
      expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('user_data');
    });
  });

  describe('hasPermission', () => {
    it('should return true when user has permission', () => {
      const mockUser = {
        id: '1',
        name: 'Test User',
        permissions: ['read', 'write'],
      };
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockUser));

      const result = AuthService.hasPermission('read');

      expect(result).toBe(true);
    });

    it('should return false when user does not have permission', () => {
      const mockUser = {
        id: '1',
        name: 'Test User',
        permissions: ['read'],
      };
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockUser));

      const result = AuthService.hasPermission('write');

      expect(result).toBe(false);
    });

    it('should return false when no user data', () => {
      mockLocalStorage.getItem.mockReturnValue(null);

      const result = AuthService.hasPermission('read');

      expect(result).toBe(false);
    });

    it('should return false when user has no permissions', () => {
      const mockUser = {
        id: '1',
        name: 'Test User',
        permissions: undefined,
      };
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockUser));

      const result = AuthService.hasPermission('read');

      expect(result).toBe(false);
    });
  });

  describe('hasRole', () => {
    it('should return true when user has role', () => {
      const mockUser = {
        id: '1',
        name: 'Test User',
        role: 'admin',
      };
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockUser));

      const result = AuthService.hasRole('admin');

      expect(result).toBe(true);
    });

    it('should return false when user does not have role', () => {
      const mockUser = {
        id: '1',
        name: 'Test User',
        role: 'student',
      };
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockUser));

      const result = AuthService.hasRole('admin');

      expect(result).toBe(false);
    });

    it('should return false when no user data', () => {
      mockLocalStorage.getItem.mockReturnValue(null);

      const result = AuthService.hasRole('admin');

      expect(result).toBe(false);
    });
  });

  describe('hasAnyRole', () => {
    it('should return true when user has one of the roles', () => {
      const mockUser = {
        id: '1',
        name: 'Test User',
        role: 'teacher',
      };
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockUser));

      const result = AuthService.hasAnyRole(['admin', 'teacher', 'student']);

      expect(result).toBe(true);
    });

    it('should return false when user does not have any of the roles', () => {
      const mockUser = {
        id: '1',
        name: 'Test User',
        role: 'student',
      };
      mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockUser));

      const result = AuthService.hasAnyRole(['admin', 'teacher']);

      expect(result).toBe(false);
    });

    it('should return false when no user data', () => {
      mockLocalStorage.getItem.mockReturnValue(null);

      const result = AuthService.hasAnyRole(['admin', 'teacher']);

      expect(result).toBe(false);
    });
  });
});