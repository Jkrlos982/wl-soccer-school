import ApiService from './api';
import {
  LoginCredentials,
  RegisterData,
  ForgotPasswordData,
  ResetPasswordData,
  AuthResponse,
  User,
  ApiResponse,
} from '../types';

export class AuthService {
  // Login user
  static async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await ApiService.post<AuthResponse>('/auth/login', credentials);
    
    // Store token and user data
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token);
      localStorage.setItem('user_data', JSON.stringify(response.data.user));
    }
    
    return response.data;
  }

  // Register new user
  static async register(userData: RegisterData): Promise<AuthResponse> {
    const response = await ApiService.post<AuthResponse>('/auth/register', userData);
    
    // Store token and user data
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token);
      localStorage.setItem('user_data', JSON.stringify(response.data.user));
    }
    
    return response.data;
  }

  // Logout user
  static async logout(): Promise<void> {
    try {
      await ApiService.post('/auth/logout');
    } catch (error) {
      // Continue with logout even if API call fails
      console.warn('Logout API call failed:', error);
    } finally {
      // Always clear local storage
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user_data');
    }
  }

  // Get current user profile
  static async getProfile(): Promise<User> {
    const response = await ApiService.get<User>('/auth/profile');
    
    // Update stored user data
    localStorage.setItem('user_data', JSON.stringify(response.data));
    
    return response.data;
  }

  // Update user profile
  static async updateProfile(userData: Partial<User>): Promise<User> {
    const response = await ApiService.put<User>('/auth/profile', userData);
    
    // Update stored user data
    localStorage.setItem('user_data', JSON.stringify(response.data));
    
    return response.data;
  }

  // Refresh authentication token
  static async refreshToken(): Promise<AuthResponse> {
    const response = await ApiService.post<AuthResponse>('/auth/refresh');
    
    // Update stored token
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token);
      localStorage.setItem('user_data', JSON.stringify(response.data.user));
    }
    
    return response.data;
  }

  // Forgot password
  static async forgotPassword(data: ForgotPasswordData): Promise<ApiResponse> {
    return await ApiService.post('/auth/forgot-password', data);
  }

  // Reset password
  static async resetPassword(data: ResetPasswordData): Promise<ApiResponse> {
    return await ApiService.post('/auth/reset-password', data);
  }

  // Verify email
  static async verifyEmail(token: string): Promise<ApiResponse> {
    return await ApiService.post('/auth/verify-email', { token });
  }

  // Resend email verification
  static async resendVerification(): Promise<ApiResponse> {
    return await ApiService.post('/auth/resend-verification');
  }

  // Change password
  static async changePassword(data: {
    current_password: string;
    password: string;
    password_confirmation: string;
  }): Promise<ApiResponse> {
    return await ApiService.post('/auth/change-password', data);
  }

  // Check if user is authenticated
  static isAuthenticated(): boolean {
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user_data');
    return !!(token && userData);
  }

  // Get stored user data
  static getStoredUser(): User | null {
    const userData = localStorage.getItem('user_data');
    if (userData) {
      try {
        return JSON.parse(userData);
      } catch (error) {
        console.error('Error parsing stored user data:', error);
        localStorage.removeItem('user_data');
      }
    }
    return null;
  }

  // Get stored token
  static getStoredToken(): string | null {
    return localStorage.getItem('auth_token');
  }

  // Clear authentication data
  static clearAuthData(): void {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
  }

  // Check if user has specific permission
  static hasPermission(permission: string): boolean {
    const user = this.getStoredUser();
    return user?.permissions?.includes(permission) || false;
  }

  // Check if user has specific role
  static hasRole(role: string): boolean {
    const user = this.getStoredUser();
    return user?.role === role;
  }

  // Check if user has any of the specified roles
  static hasAnyRole(roles: string[]): boolean {
    const user = this.getStoredUser();
    return user ? roles.includes(user.role) : false;
  }
}

export default AuthService;