import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import {
  AuthState,
  User,
  LoginCredentials,
  RegisterData,
  ForgotPasswordData,
  ResetPasswordData,
  AuthResponse,
  ApiError,
} from '../types';
import AuthService from '../services/authService';

// Initial state
const initialState: AuthState = {
  user: AuthService.getStoredUser(),
  token: AuthService.getStoredToken(),
  isLoading: false,
  isAuthenticated: AuthService.isAuthenticated(),
  error: null,
};

// Async thunks
export const loginUser = createAsyncThunk<
  AuthResponse,
  LoginCredentials,
  { rejectValue: ApiError }
>('auth/login', async (credentials, { rejectWithValue }) => {
  try {
    return await AuthService.login(credentials);
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const registerUser = createAsyncThunk<
  AuthResponse,
  RegisterData,
  { rejectValue: ApiError }
>('auth/register', async (userData, { rejectWithValue }) => {
  try {
    return await AuthService.register(userData);
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const logoutUser = createAsyncThunk<
  void,
  void,
  { rejectValue: ApiError }
>('auth/logout', async (_, { rejectWithValue }) => {
  try {
    await AuthService.logout();
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const getUserProfile = createAsyncThunk<
  User,
  void,
  { rejectValue: ApiError }
>('auth/getProfile', async (_, { rejectWithValue }) => {
  try {
    return await AuthService.getProfile();
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const updateUserProfile = createAsyncThunk<
  User,
  Partial<User>,
  { rejectValue: ApiError }
>('auth/updateProfile', async (userData, { rejectWithValue }) => {
  try {
    return await AuthService.updateProfile(userData);
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const refreshToken = createAsyncThunk<
  AuthResponse,
  void,
  { rejectValue: ApiError }
>('auth/refreshToken', async (_, { rejectWithValue }) => {
  try {
    return await AuthService.refreshToken();
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const forgotPassword = createAsyncThunk<
  string,
  ForgotPasswordData,
  { rejectValue: ApiError }
>('auth/forgotPassword', async (data, { rejectWithValue }) => {
  try {
    const response = await AuthService.forgotPassword(data);
    return response.message || 'Password reset email sent successfully';
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const resetPassword = createAsyncThunk<
  string,
  ResetPasswordData,
  { rejectValue: ApiError }
>('auth/resetPassword', async (data, { rejectWithValue }) => {
  try {
    const response = await AuthService.resetPassword(data);
    return response.message || 'Password reset successfully';
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const verifyEmail = createAsyncThunk<
  string,
  string,
  { rejectValue: ApiError }
>('auth/verifyEmail', async (token, { rejectWithValue }) => {
  try {
    const response = await AuthService.verifyEmail(token);
    return response.message || 'Email verified successfully';
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

export const resendVerification = createAsyncThunk<
  string,
  void,
  { rejectValue: ApiError }
>('auth/resendVerification', async (_, { rejectWithValue }) => {
  try {
    const response = await AuthService.resendVerification();
    return response.message || 'Verification email sent successfully';
  } catch (error) {
    return rejectWithValue(error as ApiError);
  }
});

// Auth slice
const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    clearAuth: (state) => {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;
      state.error = null;
      state.isLoading = false;
      AuthService.clearAuthData();
    },
    setUser: (state, action: PayloadAction<User>) => {
      state.user = action.payload;
      state.isAuthenticated = true;
    },
    setToken: (state, action: PayloadAction<string>) => {
      state.token = action.payload;
      state.isAuthenticated = true;
    },
  },
  extraReducers: (builder) => {
    // Login
    builder
      .addCase(loginUser.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(loginUser.fulfilled, (state, action) => {
        state.isLoading = false;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.isAuthenticated = true;
        state.error = null;
      })
      .addCase(loginUser.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Login failed';
        state.isAuthenticated = false;
      });

    // Register
    builder
      .addCase(registerUser.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(registerUser.fulfilled, (state, action) => {
        state.isLoading = false;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.isAuthenticated = true;
        state.error = null;
      })
      .addCase(registerUser.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Registration failed';
        state.isAuthenticated = false;
      });

    // Logout
    builder
      .addCase(logoutUser.pending, (state) => {
        state.isLoading = true;
      })
      .addCase(logoutUser.fulfilled, (state) => {
        state.isLoading = false;
        state.user = null;
        state.token = null;
        state.isAuthenticated = false;
        state.error = null;
      })
      .addCase(logoutUser.rejected, (state, action) => {
        state.isLoading = false;
        // Still clear auth data even if logout API fails
        state.user = null;
        state.token = null;
        state.isAuthenticated = false;
        state.error = action.payload?.message || 'Logout failed';
      });

    // Get Profile
    builder
      .addCase(getUserProfile.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(getUserProfile.fulfilled, (state, action) => {
        state.isLoading = false;
        state.user = action.payload;
        state.error = null;
      })
      .addCase(getUserProfile.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Failed to get profile';
      });

    // Update Profile
    builder
      .addCase(updateUserProfile.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(updateUserProfile.fulfilled, (state, action) => {
        state.isLoading = false;
        state.user = action.payload;
        state.error = null;
      })
      .addCase(updateUserProfile.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Failed to update profile';
      });

    // Refresh Token
    builder
      .addCase(refreshToken.pending, (state) => {
        state.isLoading = true;
      })
      .addCase(refreshToken.fulfilled, (state, action) => {
        state.isLoading = false;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.isAuthenticated = true;
        state.error = null;
      })
      .addCase(refreshToken.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Token refresh failed';
        // Clear auth on refresh failure
        state.user = null;
        state.token = null;
        state.isAuthenticated = false;
        AuthService.clearAuthData();
      });

    // Forgot Password
    builder
      .addCase(forgotPassword.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(forgotPassword.fulfilled, (state) => {
        state.isLoading = false;
        state.error = null;
      })
      .addCase(forgotPassword.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Failed to send reset email';
      });

    // Reset Password
    builder
      .addCase(resetPassword.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(resetPassword.fulfilled, (state) => {
        state.isLoading = false;
        state.error = null;
      })
      .addCase(resetPassword.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Failed to reset password';
      });

    // Verify Email
    builder
      .addCase(verifyEmail.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(verifyEmail.fulfilled, (state) => {
        state.isLoading = false;
        state.error = null;
      })
      .addCase(verifyEmail.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Email verification failed';
      });

    // Resend Verification
    builder
      .addCase(resendVerification.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(resendVerification.fulfilled, (state) => {
        state.isLoading = false;
        state.error = null;
      })
      .addCase(resendVerification.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload?.message || 'Failed to resend verification';
      });
  },
});

export const { clearError, clearAuth, setUser, setToken } = authSlice.actions;
export default authSlice.reducer;