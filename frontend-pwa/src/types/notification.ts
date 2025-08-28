// Tipos para el sistema de notificaciones de pago

export interface NotificationTemplate {
  id: string;
  name: string;
  type: NotificationType;
  subject: string;
  content: string;
  variables: string[];
  isActive: boolean;
  schoolId: string;
  createdAt: string;
  updatedAt: string;
}

export interface NotificationSettings {
  id: string;
  schoolId: string;
  paymentReminderDays: number; // Días antes del vencimiento
  overdueNotificationFrequency: number; // Días entre notificaciones de mora
  enableWhatsApp: boolean;
  enableEmail: boolean;
  enableSMS: boolean;
  templates: {
    paymentReminder: string;
    paymentConfirmation: string;
    overdueNotification: string;
    paymentReceived: string;
  };
  createdAt: string;
  updatedAt: string;
}

export interface NotificationHistory {
  id: string;
  accountReceivableId: string;
  studentId: string;
  type: NotificationType;
  channel: NotificationChannel;
  templateId: string;
  subject: string;
  content: string;
  recipient: string;
  status: NotificationStatus;
  sentAt: string;
  deliveredAt?: string;
  readAt?: string;
  errorMessage?: string;
  metadata?: Record<string, any>;
}

export interface NotificationQueue {
  id: string;
  accountReceivableId: string;
  studentId: string;
  type: NotificationType;
  channel: NotificationChannel;
  templateId: string;
  scheduledFor: string;
  priority: NotificationPriority;
  status: QueueStatus;
  attempts: number;
  maxAttempts: number;
  lastAttemptAt?: string;
  createdAt: string;
  updatedAt: string;
}

export interface NotificationRecipient {
  id: string;
  studentId: string;
  name: string;
  email?: string;
  phone?: string;
  whatsapp?: string;
  preferredChannel: NotificationChannel;
  isActive: boolean;
}

export interface NotificationStats {
  totalSent: number;
  totalDelivered: number;
  totalRead: number;
  totalFailed: number;
  byChannel: {
    email: NotificationChannelStats;
    whatsapp: NotificationChannelStats;
    sms: NotificationChannelStats;
  };
  byType: {
    paymentReminder: NotificationTypeStats;
    paymentConfirmation: NotificationTypeStats;
    overdueNotification: NotificationTypeStats;
    paymentReceived: NotificationTypeStats;
  };
}

export interface NotificationChannelStats {
  sent: number;
  delivered: number;
  read: number;
  failed: number;
  deliveryRate: number;
  readRate: number;
}

export interface NotificationTypeStats {
  sent: number;
  delivered: number;
  read: number;
  failed: number;
  averageDeliveryTime: number;
}

// Enums
export enum NotificationType {
  PAYMENT_REMINDER = 'payment_reminder',
  PAYMENT_CONFIRMATION = 'payment_confirmation',
  OVERDUE_NOTIFICATION = 'overdue_notification',
  PAYMENT_RECEIVED = 'payment_received',
  PAYMENT_PLAN_REMINDER = 'payment_plan_reminder',
  LATE_FEE_NOTIFICATION = 'late_fee_notification'
}

export enum NotificationChannel {
  EMAIL = 'email',
  WHATSAPP = 'whatsapp',
  SMS = 'sms',
  PUSH = 'push'
}

export enum NotificationStatus {
  PENDING = 'pending',
  SENT = 'sent',
  DELIVERED = 'delivered',
  READ = 'read',
  FAILED = 'failed',
  CANCELLED = 'cancelled'
}

export enum QueueStatus {
  PENDING = 'pending',
  PROCESSING = 'processing',
  COMPLETED = 'completed',
  FAILED = 'failed',
  CANCELLED = 'cancelled'
}

export enum NotificationPriority {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  URGENT = 'urgent'
}

// Filtros para notificaciones
export interface NotificationFilters {
  search?: string;
  type?: NotificationType;
  channel?: NotificationChannel;
  status?: NotificationStatus;
  studentId?: string;
  dateFrom?: string;
  dateTo?: string;
  templateId?: string;
}

// Formularios
export interface NotificationTemplateForm {
  name: string;
  type: NotificationType;
  subject: string;
  content: string;
  isActive: boolean;
}

export interface NotificationSettingsForm {
  paymentReminderDays: number;
  overdueNotificationFrequency: number;
  enableWhatsApp: boolean;
  enableEmail: boolean;
  enableSMS: boolean;
  templates: {
    paymentReminder: string;
    paymentConfirmation: string;
    overdueNotification: string;
    paymentReceived: string;
  };
}

// Variables disponibles para templates
export interface TemplateVariables {
  student: {
    name: string;
    email: string;
    phone: string;
    studentNumber: string;
  };
  accountReceivable: {
    id: string;
    amount: number;
    dueDate: string;
    concept: string;
    description: string;
    daysOverdue: number;
  };
  school: {
    name: string;
    address: string;
    phone: string;
    email: string;
    website: string;
  };
  payment: {
    amount: number;
    date: string;
    method: string;
    reference: string;
  };
}

// Respuestas de API
export interface NotificationResponse {
  success: boolean;
  message: string;
  data?: any;
}

export interface NotificationListResponse {
  notifications: NotificationHistory[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface NotificationTemplateListResponse {
  templates: NotificationTemplate[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface NotificationStatsResponse {
  stats: NotificationStats;
  period: {
    from: string;
    to: string;
  };
}