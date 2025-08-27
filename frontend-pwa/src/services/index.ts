// Export all services
export { default as ApiService, apiClient } from './api';
export { default as AuthService } from './authService';
export { default as SchoolService } from './schoolService';
export { default as UserService } from './userService';
export { default as NotificationService } from './notificationService';
export { default as FinancialService } from './financialService';
export { default as TransactionService } from './transactionService';

// Re-export types for convenience
export type {
  ApiResponse,
  ApiError,
  LoginCredentials,
  RegisterData,
  ForgotPasswordData,
  ResetPasswordData,
  AuthResponse,
  User,
  PaginatedResponse,
} from '../types';

// Re-export service-specific types
export type {
  School,
  SchoolSettings,
  CreateSchoolData,
  UpdateSchoolData,
} from './schoolService';

export type {
  Student,
  Teacher,
  Staff,
  CreateStudentData,
  CreateTeacherData,
  CreateStaffData,
} from './userService';

export type {
  Notification,
  NotificationRecipient,
  NotificationChannel,
  CreateNotificationData,
  NotificationTemplate,
  NotificationSettings,
} from './notificationService';

export type {
  Fee,
  Payment,
  Invoice,
  InvoiceItem,
  FinancialReport,
  CreateFeeData,
  CreatePaymentData,
  PaymentFilters,
} from './financialService';