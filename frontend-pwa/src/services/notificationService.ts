import ApiService from './api';
import { ApiResponse, PaginatedResponse } from '../types';

// Notification types
export interface Notification {
  id: string;
  title: string;
  message: string;
  type: 'info' | 'success' | 'warning' | 'error' | 'announcement';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  sender_id: string;
  sender_name: string;
  recipients: NotificationRecipient[];
  channels: NotificationChannel[];
  scheduled_at?: string;
  sent_at?: string;
  read_at?: string;
  status: 'draft' | 'scheduled' | 'sent' | 'failed';
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
}

export interface NotificationRecipient {
  id: string;
  type: 'user' | 'role' | 'group';
  identifier: string; // user_id, role_name, or group_id
  name: string;
  delivered: boolean;
  read: boolean;
  delivered_at?: string;
  read_at?: string;
}

export interface NotificationChannel {
  type: 'email' | 'sms' | 'push' | 'in_app';
  enabled: boolean;
  config?: Record<string, any>;
}

export interface CreateNotificationData {
  title: string;
  message: string;
  type: Notification['type'];
  priority: Notification['priority'];
  recipients: {
    type: 'user' | 'role' | 'group' | 'all';
    identifiers: string[];
  };
  channels: NotificationChannel[];
  scheduled_at?: string;
  metadata?: Record<string, any>;
}

export interface NotificationTemplate {
  id: string;
  name: string;
  title: string;
  message: string;
  type: Notification['type'];
  variables: string[];
  created_at: string;
  updated_at: string;
}

export interface NotificationSettings {
  email_notifications: boolean;
  sms_notifications: boolean;
  push_notifications: boolean;
  in_app_notifications: boolean;
  notification_types: {
    announcements: boolean;
    academic_updates: boolean;
    financial_alerts: boolean;
    sports_updates: boolean;
    medical_reminders: boolean;
    calendar_events: boolean;
  };
}

export class NotificationService {
  // Get user notifications
  static async getNotifications(params?: {
    page?: number;
    per_page?: number;
    type?: string;
    status?: string;
    unread_only?: boolean;
  }): Promise<PaginatedResponse<Notification>> {
    const response = await ApiService.get<PaginatedResponse<Notification>>(
      '/notifications',
      { params }
    );
    return response.data;
  }

  // Get notification by ID
  static async getNotification(notificationId: string): Promise<Notification> {
    const response = await ApiService.get<Notification>(
      `/notifications/${notificationId}`
    );
    return response.data;
  }

  // Create new notification
  static async createNotification(
    data: CreateNotificationData
  ): Promise<Notification> {
    const response = await ApiService.post<Notification>('/notifications', data);
    return response.data;
  }

  // Update notification
  static async updateNotification(
    notificationId: string,
    data: Partial<CreateNotificationData>
  ): Promise<Notification> {
    const response = await ApiService.put<Notification>(
      `/notifications/${notificationId}`,
      data
    );
    return response.data;
  }

  // Delete notification
  static async deleteNotification(notificationId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/notifications/${notificationId}`);
  }

  // Mark notification as read
  static async markAsRead(notificationId: string): Promise<ApiResponse> {
    return await ApiService.patch(`/notifications/${notificationId}/read`);
  }

  // Mark all notifications as read
  static async markAllAsRead(): Promise<ApiResponse> {
    return await ApiService.patch('/notifications/mark-all-read');
  }

  // Get unread count
  static async getUnreadCount(): Promise<{ count: number }> {
    const response = await ApiService.get('/notifications/unread-count');
    return response.data;
  }

  // Send notification immediately
  static async sendNotification(notificationId: string): Promise<ApiResponse> {
    return await ApiService.post(`/notifications/${notificationId}/send`);
  }

  // Cancel scheduled notification
  static async cancelNotification(notificationId: string): Promise<ApiResponse> {
    return await ApiService.post(`/notifications/${notificationId}/cancel`);
  }

  // Notification Templates
  static async getTemplates(): Promise<NotificationTemplate[]> {
    const response = await ApiService.get<NotificationTemplate[]>(
      '/notifications/templates'
    );
    return response.data;
  }

  static async getTemplate(templateId: string): Promise<NotificationTemplate> {
    const response = await ApiService.get<NotificationTemplate>(
      `/notifications/templates/${templateId}`
    );
    return response.data;
  }

  static async createTemplate(
    data: Omit<NotificationTemplate, 'id' | 'created_at' | 'updated_at'>
  ): Promise<NotificationTemplate> {
    const response = await ApiService.post<NotificationTemplate>(
      '/notifications/templates',
      data
    );
    return response.data;
  }

  static async updateTemplate(
    templateId: string,
    data: Partial<Omit<NotificationTemplate, 'id' | 'created_at' | 'updated_at'>>
  ): Promise<NotificationTemplate> {
    const response = await ApiService.put<NotificationTemplate>(
      `/notifications/templates/${templateId}`,
      data
    );
    return response.data;
  }

  static async deleteTemplate(templateId: string): Promise<ApiResponse> {
    return await ApiService.delete(`/notifications/templates/${templateId}`);
  }

  // Notification Settings
  static async getSettings(): Promise<NotificationSettings> {
    const response = await ApiService.get<NotificationSettings>(
      '/notifications/settings'
    );
    return response.data;
  }

  static async updateSettings(
    settings: Partial<NotificationSettings>
  ): Promise<NotificationSettings> {
    const response = await ApiService.put<NotificationSettings>(
      '/notifications/settings',
      settings
    );
    return response.data;
  }

  // Bulk Operations
  static async sendBulkNotification(data: {
    template_id: string;
    recipients: {
      type: 'user' | 'role' | 'group' | 'all';
      identifiers: string[];
    };
    variables?: Record<string, string>;
    channels: NotificationChannel[];
    scheduled_at?: string;
  }): Promise<ApiResponse> {
    return await ApiService.post('/notifications/bulk-send', data);
  }

  // Real-time notifications (WebSocket)
  static subscribeToNotifications(
    userId: string,
    onNotification: (notification: Notification) => void
  ): () => void {
    // This would typically use WebSocket or Server-Sent Events
    // For now, we'll use polling as a fallback
    const interval = setInterval(async () => {
      try {
        const { count } = await this.getUnreadCount();
        if (count > 0) {
          const notifications = await this.getNotifications({
            unread_only: true,
            per_page: 5,
          });
          notifications.data.forEach(onNotification);
        }
      } catch (error) {
        console.error('Error polling notifications:', error);
      }
    }, 30000); // Poll every 30 seconds

    return () => clearInterval(interval);
  }

  // Push notification registration
  static async registerPushToken(token: string): Promise<ApiResponse> {
    return await ApiService.post('/notifications/push-token', { token });
  }

  static async unregisterPushToken(): Promise<ApiResponse> {
    return await ApiService.delete('/notifications/push-token');
  }
}

export default NotificationService;