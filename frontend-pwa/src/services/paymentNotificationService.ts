import { apiClient } from './api';
import {
  NotificationTemplate,
  NotificationSettings,
  NotificationHistory,
  NotificationQueue,
  NotificationStats,
  NotificationFilters,
  NotificationTemplateForm,
  NotificationSettingsForm,
  NotificationResponse,
  NotificationListResponse,
  NotificationTemplateListResponse,
  NotificationStatsResponse,
  NotificationType,
  NotificationChannel
} from '../types/notification';

/**
 * Servicio especializado para notificaciones de pago
 * Implementa las funcionalidades específicas del Sprint 4 punto 5
 */
class PaymentNotificationService {
  private baseUrl = '/financial/notifications';

  // ========== TEMPLATES DE NOTIFICACIÓN ==========
  
  /**
   * Obtener plantillas de notificación de pago
   */
  async getPaymentTemplates(page = 1, limit = 10): Promise<NotificationTemplateListResponse> {
    const response = await apiClient.get(`${this.baseUrl}/templates`, {
      params: { page, limit }
    });
    return response.data;
  }

  /**
   * Obtener plantilla específica
   */
  async getPaymentTemplate(id: string): Promise<NotificationTemplate> {
    const response = await apiClient.get(`${this.baseUrl}/templates/${id}`);
    return response.data;
  }

  /**
   * Crear nueva plantilla de notificación
   */
  async createPaymentTemplate(template: NotificationTemplateForm): Promise<NotificationTemplate> {
    const response = await apiClient.post(`${this.baseUrl}/templates`, template);
    return response.data;
  }

  /**
   * Actualizar plantilla existente
   */
  async updatePaymentTemplate(id: string, template: Partial<NotificationTemplateForm>): Promise<NotificationTemplate> {
    const response = await apiClient.put(`${this.baseUrl}/templates/${id}`, template);
    return response.data;
  }

  /**
   * Eliminar plantilla
   */
  async deletePaymentTemplate(id: string): Promise<NotificationResponse> {
    const response = await apiClient.delete(`${this.baseUrl}/templates/${id}`);
    return response.data;
  }

  /**
   * Duplicar plantilla
   */
  async duplicatePaymentTemplate(id: string, name: string): Promise<NotificationTemplate> {
    const response = await apiClient.post(`${this.baseUrl}/templates/${id}/duplicate`, { name });
    return response.data;
  }

  /**
   * Vista previa de plantilla con variables
   */
  async previewPaymentTemplate(id: string, variables: Record<string, any>): Promise<{ subject: string; content: string }> {
    const response = await apiClient.post(`${this.baseUrl}/templates/${id}/preview`, { variables });
    return response.data;
  }

  // ========== CONFIGURACIÓN DE NOTIFICACIONES ==========
  
  /**
   * Obtener configuración de notificaciones de pago
   */
  async getPaymentNotificationSettings(): Promise<NotificationSettings> {
    const response = await apiClient.get(`${this.baseUrl}/settings`);
    return response.data;
  }

  /**
   * Actualizar configuración de notificaciones
   */
  async updatePaymentNotificationSettings(settings: NotificationSettingsForm): Promise<NotificationSettings> {
    const response = await apiClient.put(`${this.baseUrl}/settings`, settings);
    return response.data;
  }

  /**
   * Restaurar configuración por defecto
   */
  async resetPaymentNotificationSettings(): Promise<NotificationSettings> {
    const response = await apiClient.post(`${this.baseUrl}/settings/reset`);
    return response.data;
  }

  /**
   * Probar canal de notificación
   */
  async testPaymentNotificationChannel(channel: NotificationChannel, recipient: string): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/test`, {
      channel,
      recipient
    });
    return response.data;
  }

  // ========== ENVÍO DE NOTIFICACIONES AUTOMÁTICAS ==========
  
  /**
   * Enviar recordatorio de pago (3 días antes del vencimiento)
   */
  async sendPaymentReminder(accountReceivableId: string): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/send/payment-reminder`, {
      accountReceivableId
    });
    return response.data;
  }

  /**
   * Enviar confirmación de pago recibido
   */
  async sendPaymentConfirmation(paymentId: string): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/send/payment-confirmation`, {
      paymentId
    });
    return response.data;
  }

  /**
   * Enviar notificación de pago vencido
   */
  async sendOverdueNotification(accountReceivableId: string): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/send/overdue-notification`, {
      accountReceivableId
    });
    return response.data;
  }

  /**
   * Enviar notificación de vencimiento próximo
   */
  async sendDueDateNotification(accountReceivableId: string): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/send/due-date-notification`, {
      accountReceivableId
    });
    return response.data;
  }

  // ========== COMANDOS PROGRAMADOS ==========
  
  /**
   * Ejecutar comando diario de recordatorios de pago
   */
  async runDailyPaymentReminders(): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/commands/daily-reminders`);
    return response.data;
  }

  /**
   * Ejecutar comando diario de notificaciones de vencimiento
   */
  async runDailyDueNotifications(): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/commands/daily-due-notifications`);
    return response.data;
  }

  /**
   * Ejecutar comando semanal de pagos vencidos
   */
  async runWeeklyOverdueNotifications(): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/commands/weekly-overdue`);
    return response.data;
  }

  /**
   * Obtener estado de comandos programados
   */
  async getScheduledCommandsStatus(): Promise<{
    dailyReminders: { lastRun: string; nextRun: string; status: string };
    dailyDueNotifications: { lastRun: string; nextRun: string; status: string };
    weeklyOverdue: { lastRun: string; nextRun: string; status: string };
  }> {
    const response = await apiClient.get(`${this.baseUrl}/commands/status`);
    return response.data;
  }

  // ========== OPERACIONES MASIVAS ==========
  
  /**
   * Enviar recordatorios masivos de pago
   */
  async sendBulkPaymentReminders(accountReceivableIds: string[]): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/send/bulk-reminders`, {
      accountReceivableIds
    });
    return response.data;
  }

  /**
   * Enviar notificaciones masivas de vencimiento
   */
  async sendBulkOverdueNotifications(accountReceivableIds: string[]): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/send/bulk-overdue`, {
      accountReceivableIds
    });
    return response.data;
  }

  /**
   * Enviar notificación personalizada a múltiples estudiantes
   */
  async sendCustomBulkNotification(
    studentIds: string[],
    templateId: string,
    channel: NotificationChannel,
    variables?: Record<string, any>
  ): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/send/custom-bulk`, {
      studentIds,
      templateId,
      channel,
      variables
    });
    return response.data;
  }

  // ========== HISTORIAL Y SEGUIMIENTO ==========
  
  /**
   * Obtener historial de notificaciones de pago
   */
  async getPaymentNotificationHistory(
    filters: NotificationFilters = {},
    page = 1,
    limit = 10
  ): Promise<NotificationListResponse> {
    const response = await apiClient.get(`${this.baseUrl}/history`, {
      params: { ...filters, page, limit }
    });
    return response.data;
  }

  /**
   * Obtener notificación específica
   */
  async getPaymentNotificationById(id: string): Promise<NotificationHistory> {
    const response = await apiClient.get(`${this.baseUrl}/history/${id}`);
    return response.data;
  }

  /**
   * Reenviar notificación
   */
  async resendPaymentNotification(id: string): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/history/${id}/resend`);
    return response.data;
  }

  /**
   * Cancelar notificación programada
   */
  async cancelPaymentNotification(id: string): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/history/${id}/cancel`);
    return response.data;
  }

  // ========== COLA DE NOTIFICACIONES ==========
  
  /**
   * Obtener cola de notificaciones pendientes
   */
  async getPaymentNotificationQueue(
    page = 1,
    limit = 10,
    status?: string
  ): Promise<{ queue: NotificationQueue[]; total: number; page: number; limit: number }> {
    const response = await apiClient.get(`${this.baseUrl}/queue`, {
      params: { page, limit, status }
    });
    return response.data;
  }

  /**
   * Procesar cola de notificaciones
   */
  async processPaymentNotificationQueue(): Promise<NotificationResponse> {
    const response = await apiClient.post(`${this.baseUrl}/queue/process`);
    return response.data;
  }

  /**
   * Limpiar cola de notificaciones
   */
  async clearPaymentNotificationQueue(): Promise<NotificationResponse> {
    const response = await apiClient.delete(`${this.baseUrl}/queue`);
    return response.data;
  }

  // ========== ESTADÍSTICAS Y REPORTES ==========
  
  /**
   * Obtener estadísticas de notificaciones de pago
   */
  async getPaymentNotificationStats(
    dateFrom?: string,
    dateTo?: string
  ): Promise<NotificationStatsResponse> {
    const response = await apiClient.get(`${this.baseUrl}/stats`, {
      params: { dateFrom, dateTo }
    });
    return response.data;
  }

  /**
   * Obtener reporte de entrega de notificaciones
   */
  async getPaymentNotificationDeliveryReport(
    dateFrom: string,
    dateTo: string
  ): Promise<{
    totalSent: number;
    deliveryRate: number;
    readRate: number;
    byChannel: Record<string, any>;
    byType: Record<string, any>;
  }> {
    const response = await apiClient.get(`${this.baseUrl}/reports/delivery`, {
      params: { dateFrom, dateTo }
    });
    return response.data;
  }

  /**
   * Obtener reporte de efectividad de cobranza
   */
  async getCollectionEffectivenessReport(
    dateFrom: string,
    dateTo: string
  ): Promise<{
    totalNotificationsSent: number;
    paymentsAfterNotification: number;
    effectivenessRate: number;
    averagePaymentDelay: number;
    byNotificationType: Record<string, any>;
  }> {
    const response = await apiClient.get(`${this.baseUrl}/reports/effectiveness`, {
      params: { dateFrom, dateTo }
    });
    return response.data;
  }

  // ========== EXPORTACIÓN ==========
  
  /**
   * Exportar historial de notificaciones
   */
  async exportPaymentNotificationHistory(
    filters: NotificationFilters = {},
    format: 'csv' | 'excel' = 'excel'
  ): Promise<Blob> {
    const response = await apiClient.get(`${this.baseUrl}/export/history`, {
      params: { ...filters, format },
      responseType: 'blob'
    });
    return response.data;
  }

  /**
   * Exportar estadísticas de notificaciones
   */
  async exportPaymentNotificationStats(
    dateFrom: string,
    dateTo: string,
    format: 'csv' | 'excel' = 'excel'
  ): Promise<Blob> {
    const response = await apiClient.get(`${this.baseUrl}/export/stats`, {
      params: { dateFrom, dateTo, format },
      responseType: 'blob'
    });
    return response.data;
  }

  // ========== VARIABLES DE PLANTILLA ==========
  
  /**
   * Obtener variables disponibles para plantillas
   */
  async getPaymentTemplateVariables(type: NotificationType): Promise<Record<string, any>> {
    const response = await apiClient.get(`${this.baseUrl}/templates/variables/${type}`);
    return response.data;
  }

  /**
   * Validar plantilla con variables
   */
  async validatePaymentTemplate(
    templateContent: string,
    variables: Record<string, any>
  ): Promise<{ isValid: boolean; errors: string[]; preview: string }> {
    const response = await apiClient.post(`${this.baseUrl}/templates/validate`, {
      templateContent,
      variables
    });
    return response.data;
  }
}

export const paymentNotificationService = new PaymentNotificationService();
export default paymentNotificationService;