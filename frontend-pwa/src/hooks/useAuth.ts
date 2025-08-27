import { useCallback, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  useAppDispatch,
  useAppSelector,
  selectAuth,
  selectUser,
  selectIsAuthenticated,
  selectAuthLoading,
  selectAuthError,
} from '../store';
import {
  loginUser,
  registerUser,
  logoutUser,
  getUserProfile,
  updateUserProfile,
  refreshToken,
  forgotPassword,
  resetPassword,
  verifyEmail,
  resendVerification,
  clearError,
  clearAuth,
} from '../store/authSlice';
import {
  LoginCredentials,
  RegisterData,
  ForgotPasswordData,
  ResetPasswordData,
  User,
} from '../types';
import AuthService from '../services/authService';
import { isTokenExpired } from '../utils';

export const useAuth = () => {
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  
  const auth = useAppSelector(selectAuth);
  const user = useAppSelector(selectUser);
  const isAuthenticated = useAppSelector(selectIsAuthenticated);
  const isLoading = useAppSelector(selectAuthLoading);
  const error = useAppSelector(selectAuthError);

  // Login function
  const login = useCallback(async (credentials: LoginCredentials) => {
    try {
      const result = await dispatch(loginUser(credentials)).unwrap();
      navigate('/dashboard');
      return result;
    } catch (error) {
      throw error;
    }
  }, [dispatch, navigate]);

  // Register function
  const register = useCallback(async (userData: RegisterData) => {
    try {
      const result = await dispatch(registerUser(userData)).unwrap();
      navigate('/dashboard');
      return result;
    } catch (error) {
      throw error;
    }
  }, [dispatch, navigate]);

  // Logout function
  const logout = useCallback(async () => {
    try {
      await dispatch(logoutUser()).unwrap();
      navigate('/login');
    } catch (error) {
      // Even if logout fails, clear local auth and redirect
      dispatch(clearAuth());
      navigate('/login');
    }
  }, [dispatch, navigate]);

  // Get user profile
  const getProfile = useCallback(async () => {
    try {
      return await dispatch(getUserProfile()).unwrap();
    } catch (error) {
      throw error;
    }
  }, [dispatch]);

  // Update user profile
  const updateProfile = useCallback(async (userData: Partial<User>) => {
    try {
      return await dispatch(updateUserProfile(userData)).unwrap();
    } catch (error) {
      throw error;
    }
  }, [dispatch]);

  // Refresh authentication token
  const refresh = useCallback(async () => {
    try {
      return await dispatch(refreshToken()).unwrap();
    } catch (error) {
      // If refresh fails, logout user
      dispatch(clearAuth());
      navigate('/login');
      throw error;
    }
  }, [dispatch, navigate]);

  // Forgot password
  const sendPasswordReset = useCallback(async (data: ForgotPasswordData) => {
    try {
      return await dispatch(forgotPassword(data)).unwrap();
    } catch (error) {
      throw error;
    }
  }, [dispatch]);

  // Reset password
  const resetUserPassword = useCallback(async (data: ResetPasswordData) => {
    try {
      const result = await dispatch(resetPassword(data)).unwrap();
      navigate('/login');
      return result;
    } catch (error) {
      throw error;
    }
  }, [dispatch, navigate]);

  // Verify email
  const verifyUserEmail = useCallback(async (token: string) => {
    try {
      return await dispatch(verifyEmail(token)).unwrap();
    } catch (error) {
      throw error;
    }
  }, [dispatch]);

  // Resend verification email
  const resendVerificationEmail = useCallback(async () => {
    try {
      return await dispatch(resendVerification()).unwrap();
    } catch (error) {
      throw error;
    }
  }, [dispatch]);

  // Clear authentication error
  const clearAuthError = useCallback(() => {
    dispatch(clearError());
  }, [dispatch]);

  // Check if user has specific permission
  const hasPermission = useCallback((permission: string): boolean => {
    return AuthService.hasPermission(permission);
  }, []);

  // Check if user has specific role
  const hasRole = useCallback((role: string): boolean => {
    return AuthService.hasRole(role);
  }, []);

  // Check if user has any of the specified roles
  const hasAnyRole = useCallback((roles: string[]): boolean => {
    return AuthService.hasAnyRole(roles);
  }, []);

  // Check token expiration and refresh if needed
  const checkTokenExpiration = useCallback(async () => {
    const token = AuthService.getStoredToken();
    if (token && isTokenExpired(token)) {
      try {
        await refresh();
      } catch (error) {
        console.error('Token refresh failed:', error);
      }
    }
  }, [refresh]);

  // Initialize authentication state
  const initializeAuth = useCallback(async () => {
    const token = AuthService.getStoredToken();
    const storedUser = AuthService.getStoredUser();
    
    if (token && storedUser) {
      if (isTokenExpired(token)) {
        try {
          await refresh();
        } catch (error) {
          dispatch(clearAuth());
        }
      } else {
        // Token is valid, get fresh user data
        try {
          await getProfile();
        } catch (error) {
          // If profile fetch fails, use stored data
          console.warn('Failed to fetch fresh profile, using stored data');
        }
      }
    } else {
      dispatch(clearAuth());
    }
  }, [dispatch, refresh, getProfile]);

  // Auto-refresh token before expiration
  useEffect(() => {
    if (isAuthenticated) {
      const interval = setInterval(() => {
        checkTokenExpiration();
      }, 5 * 60 * 1000); // Check every 5 minutes

      return () => clearInterval(interval);
    }
  }, [isAuthenticated, checkTokenExpiration]);

  // Initialize auth on mount
  useEffect(() => {
    initializeAuth();
  }, [initializeAuth]);

  return {
    // State
    user,
    isAuthenticated,
    isLoading,
    error,
    auth,
    
    // Actions
    login,
    register,
    logout,
    getProfile,
    updateProfile,
    refresh,
    sendPasswordReset,
    resetUserPassword,
    verifyUserEmail,
    resendVerificationEmail,
    clearAuthError,
    
    // Utilities
    hasPermission,
    hasRole,
    hasAnyRole,
    checkTokenExpiration,
    initializeAuth,
  };
};

export default useAuth;