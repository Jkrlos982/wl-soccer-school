import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import {
  NotificationTemplate,
  NotificationSettings,
  NotificationHistory,
  NotificationQueue,
  NotificationStats,
  NotificationFilters,
  NotificationTemplateForm,
  NotificationSettingsForm,
  NotificationType,
  NotificationChannel,
  NotificationStatus,
  QueueStatus
} from '../types/notification';
import paymentNotificationService from '../services/paymentNotificationService';

// State interface
interface NotificationState {
  templates: {
    data: NotificationTemplate[];
    total: number;
    loading: boolean;
    error: string | null;
  };
  settings: {
    data: NotificationSettings | null;
    loading: boolean;
    error: string | null;
  };
  history: {
    data: NotificationHistory[];
    total: number;
    loading: boolean;
    error: string | null;
  };
  queue: {
    data: NotificationQueue[];
    total: number;
    loading: boolean;
    error: string | null;
  };
  stats: {
    data: NotificationStats | null;
    loading: boolean;
    error: string | null;
  };
  filters: NotificationFilters;
  selectedTemplate: NotificationTemplate | null;
  selectedHistoryItem: NotificationHistory | null;
}

// Initial state
const initialState: NotificationState = {
  templates: {
    data: [],
    total: 0,
    loading: false,
    error: null,
  },
  settings: {
    data: null,
    loading: false,
    error: null,
  },
  history: {
    data: [],
    total: 0,
    loading: false,
    error: null,
  },
  queue: {
    data: [],
    total: 0,
    loading: false,
    error: null,
  },
  stats: {
    data: null,
    loading: false,
    error: null,
  },
  filters: {},
  selectedTemplate: null,
  selectedHistoryItem: null,
};

// Async thunks for notification templates
export const fetchNotificationTemplates = createAsyncThunk(
  'notification/fetchNotificationTemplates',
  async (params?: { page?: number; limit?: number }) => {
    const response = await paymentNotificationService.getPaymentTemplates(params?.page, params?.limit);
    return response;
  }
);

export const fetchNotificationTemplate = createAsyncThunk(
  'notification/fetchNotificationTemplate',
  async (templateId: string) => {
    const response = await paymentNotificationService.getPaymentTemplate(templateId);
    return response;
  }
);

export const createNotificationTemplate = createAsyncThunk(
  'notification/createNotificationTemplate',
  async (data: NotificationTemplateForm) => {
    const response = await paymentNotificationService.createPaymentTemplate(data);
    return response;
  }
);

export const updateNotificationTemplate = createAsyncThunk(
  'notification/updateNotificationTemplate',
  async ({ templateId, data }: { templateId: string; data: Partial<NotificationTemplateForm> }) => {
    const response = await paymentNotificationService.updatePaymentTemplate(templateId, data);
    return response;
  }
);

export const deleteNotificationTemplate = createAsyncThunk(
  'notification/deleteNotificationTemplate',
  async (templateId: string) => {
    await paymentNotificationService.deletePaymentTemplate(templateId);
    return templateId;
  }
);

export const duplicateNotificationTemplate = createAsyncThunk(
  'notification/duplicateNotificationTemplate',
  async ({ templateId, name }: { templateId: string; name: string }) => {
    const response = await paymentNotificationService.duplicatePaymentTemplate(templateId, name);
    return response;
  }
);

// Async thunks for notification settings
export const fetchNotificationSettings = createAsyncThunk(
  'notification/fetchNotificationSettings',
  async () => {
    const response = await paymentNotificationService.getPaymentNotificationSettings();
    return response;
  }
);

export const updateNotificationSettings = createAsyncThunk(
  'notification/updateNotificationSettings',
  async (data: NotificationSettingsForm) => {
    const response = await paymentNotificationService.updatePaymentNotificationSettings(data);
    return response;
  }
);

// Async thunks for notification history
export const fetchNotificationHistory = createAsyncThunk(
  'notification/fetchNotificationHistory',
  async (params?: { filters?: NotificationFilters; page?: number; limit?: number }) => {
    const response = await paymentNotificationService.getPaymentNotificationHistory(
      params?.filters,
      params?.page,
      params?.limit
    );
    return response;
  }
);

export const fetchNotificationHistoryItem = createAsyncThunk(
  'notification/fetchNotificationHistoryItem',
  async (historyId: string) => {
    const response = await paymentNotificationService.getPaymentNotificationById(historyId);
    return response;
  }
);

export const resendNotification = createAsyncThunk(
  'notification/resendNotification',
  async (historyId: string) => {
    const response = await paymentNotificationService.resendPaymentNotification(historyId);
    return response;
  }
);

// Async thunks for notification queue
export const fetchNotificationQueue = createAsyncThunk(
  'notification/fetchNotificationQueue',
  async (params?: { page?: number; limit?: number; status?: string }) => {
    const response = await paymentNotificationService.getPaymentNotificationQueue(
      params?.page,
      params?.limit,
      params?.status
    );
    return response;
  }
);

export const cancelQueuedNotification = createAsyncThunk(
  'notification/cancelQueuedNotification',
  async (queueId: string) => {
    await paymentNotificationService.cancelPaymentNotification(queueId);
    return queueId;
  }
);

export const retryFailedNotification = createAsyncThunk(
  'notification/retryFailedNotification',
  async (queueId: string) => {
    const response = await paymentNotificationService.resendPaymentNotification(queueId);
    return response;
  }
);

export const processQueue = createAsyncThunk(
  'notification/processQueue',
  async () => {
    const response = await paymentNotificationService.processPaymentNotificationQueue();
    return response;
  }
);

// Async thunks for automatic notifications
export const sendPaymentReminder = createAsyncThunk(
  'notification/sendPaymentReminder',
  async (accountReceivableId: string) => {
    const response = await paymentNotificationService.sendPaymentReminder(accountReceivableId);
    return response;
  }
);

export const sendPaymentConfirmation = createAsyncThunk(
  'notification/sendPaymentConfirmation',
  async (paymentId: string) => {
    const response = await paymentNotificationService.sendPaymentConfirmation(paymentId);
    return response;
  }
);

export const sendOverdueNotification = createAsyncThunk(
  'notification/sendOverdueNotification',
  async (accountReceivableId: string) => {
    const response = await paymentNotificationService.sendOverdueNotification(accountReceivableId);
    return response;
  }
);

// Async thunks for bulk operations
export const sendBulkReminders = createAsyncThunk(
  'notification/sendBulkReminders',
  async (data: {
    studentIds: string[];
    templateId: string;
    channel: NotificationChannel;
    variables?: Record<string, any>;
  }) => {
    const response = await paymentNotificationService.sendCustomBulkNotification(
      data.studentIds,
      data.templateId,
      data.channel,
      data.variables
    );
    return response;
  }
);

export const sendBulkNotifications = createAsyncThunk(
  'notification/sendBulkNotifications',
  async (data: {
    studentIds: string[];
    templateId: string;
    channel: NotificationChannel;
    variables?: Record<string, any>;
  }) => {
    const response = await paymentNotificationService.sendCustomBulkNotification(
      data.studentIds,
      data.templateId,
      data.channel,
      data.variables
    );
    return response;
  }
);

// Async thunks for statistics and reports
export const fetchNotificationStats = createAsyncThunk(
  'notification/fetchNotificationStats',
  async (params?: {
    dateFrom?: string;
    dateTo?: string;
  }) => {
    const response = await paymentNotificationService.getPaymentNotificationStats(
      params?.dateFrom,
      params?.dateTo
    );
    return response;
  }
);

export const generateNotificationReport = createAsyncThunk(
  'notification/generateNotificationReport',
  async (data: {
    dateFrom: string;
    dateTo: string;
    format: 'csv' | 'excel';
  }) => {
    const response = await paymentNotificationService.exportPaymentNotificationStats(
      data.dateFrom,
      data.dateTo,
      data.format
    );
    return response;
  }
);

// Async thunks for scheduled commands
export const scheduleAutomaticReminders = createAsyncThunk(
  'notification/scheduleAutomaticReminders',
  async () => {
    const response = await paymentNotificationService.runDailyPaymentReminders();
    return response;
  }
);

export const scheduleOverdueNotifications = createAsyncThunk(
  'notification/scheduleOverdueNotifications',
  async () => {
    const response = await paymentNotificationService.runWeeklyOverdueNotifications();
    return response;
  }
);

// Notification slice
const notificationSlice = createSlice({
  name: 'notification',
  initialState,
  reducers: {
    // Filter actions
    setFilters: (state, action: PayloadAction<Partial<NotificationFilters>>) => {
      state.filters = { ...state.filters, ...action.payload };
    },
    clearFilters: (state) => {
      state.filters = {};
    },

    // Selection actions
    setSelectedTemplate: (state, action: PayloadAction<NotificationTemplate | null>) => {
      state.selectedTemplate = action.payload;
    },
    setSelectedHistoryItem: (state, action: PayloadAction<NotificationHistory | null>) => {
      state.selectedHistoryItem = action.payload;
    },

    // Reset actions
    resetTemplates: (state) => {
      state.templates = {
        data: [],
        total: 0,
        loading: false,
        error: null,
      };
    },
    resetHistory: (state) => {
      state.history = {
        data: [],
        total: 0,
        loading: false,
        error: null,
      };
    },
    resetQueue: (state) => {
      state.queue = {
        data: [],
        total: 0,
        loading: false,
        error: null,
      };
    },
    resetState: () => initialState,
  },
  extraReducers: (builder) => {
    // Fetch notification templates
    builder
      .addCase(fetchNotificationTemplates.pending, (state) => {
        state.templates.loading = true;
        state.templates.error = null;
      })
      .addCase(fetchNotificationTemplates.fulfilled, (state, action) => {
        state.templates.loading = false;
        state.templates.data = action.payload.templates || [];
        state.templates.total = action.payload.total || 0;
      })
      .addCase(fetchNotificationTemplates.rejected, (state, action) => {
        state.templates.loading = false;
        state.templates.error = action.error.message || 'Failed to fetch notification templates';
      })

      // Fetch single notification template
      .addCase(fetchNotificationTemplate.pending, (state) => {
        state.templates.loading = true;
      })
      .addCase(fetchNotificationTemplate.fulfilled, (state, action) => {
        state.templates.loading = false;
        state.selectedTemplate = action.payload;
      })
      .addCase(fetchNotificationTemplate.rejected, (state, action) => {
        state.templates.loading = false;
        state.templates.error = action.error.message || 'Failed to fetch notification template';
      })

      // Create notification template
      .addCase(createNotificationTemplate.pending, (state) => {
        state.templates.loading = true;
        state.templates.error = null;
      })
      .addCase(createNotificationTemplate.fulfilled, (state, action) => {
        state.templates.loading = false;
        state.templates.data.unshift(action.payload);
        state.templates.total += 1;
      })
      .addCase(createNotificationTemplate.rejected, (state, action) => {
        state.templates.loading = false;
        state.templates.error = action.error.message || 'Failed to create notification template';
      })

      // Update notification template
      .addCase(updateNotificationTemplate.pending, (state) => {
        state.templates.loading = true;
        state.templates.error = null;
      })
      .addCase(updateNotificationTemplate.fulfilled, (state, action) => {
        state.templates.loading = false;
        const index = state.templates.data.findIndex(t => t.id === action.payload.id);
        if (index !== -1) {
          state.templates.data[index] = action.payload;
        }
        if (state.selectedTemplate?.id === action.payload.id) {
          state.selectedTemplate = action.payload;
        }
      })
      .addCase(updateNotificationTemplate.rejected, (state, action) => {
        state.templates.loading = false;
        state.templates.error = action.error.message || 'Failed to update notification template';
      })

      // Delete notification template
      .addCase(deleteNotificationTemplate.pending, (state) => {
        state.templates.loading = true;
      })
      .addCase(deleteNotificationTemplate.fulfilled, (state, action) => {
        state.templates.loading = false;
        state.templates.data = state.templates.data.filter(t => t.id !== action.payload);
        state.templates.total -= 1;
        if (state.selectedTemplate?.id === action.payload) {
          state.selectedTemplate = null;
        }
      })
      .addCase(deleteNotificationTemplate.rejected, (state, action) => {
        state.templates.loading = false;
        state.templates.error = action.error.message || 'Failed to delete notification template';
      })

      // Duplicate notification template
      .addCase(duplicateNotificationTemplate.pending, (state) => {
        state.templates.loading = true;
      })
      .addCase(duplicateNotificationTemplate.fulfilled, (state, action) => {
        state.templates.loading = false;
        state.templates.data.unshift(action.payload);
        state.templates.total += 1;
      })
      .addCase(duplicateNotificationTemplate.rejected, (state, action) => {
        state.templates.loading = false;
        state.templates.error = action.error.message || 'Failed to duplicate notification template';
      })

      // Fetch notification settings
      .addCase(fetchNotificationSettings.pending, (state) => {
        state.settings.loading = true;
        state.settings.error = null;
      })
      .addCase(fetchNotificationSettings.fulfilled, (state, action) => {
        state.settings.loading = false;
        state.settings.data = action.payload;
      })
      .addCase(fetchNotificationSettings.rejected, (state, action) => {
        state.settings.loading = false;
        state.settings.error = action.error.message || 'Failed to fetch notification settings';
      })

      // Update notification settings
      .addCase(updateNotificationSettings.pending, (state) => {
        state.settings.loading = true;
        state.settings.error = null;
      })
      .addCase(updateNotificationSettings.fulfilled, (state, action) => {
        state.settings.loading = false;
        state.settings.data = action.payload;
      })
      .addCase(updateNotificationSettings.rejected, (state, action) => {
        state.settings.loading = false;
        state.settings.error = action.error.message || 'Failed to update notification settings';
      })

      // Fetch notification history
      .addCase(fetchNotificationHistory.pending, (state) => {
        state.history.loading = true;
        state.history.error = null;
      })
      .addCase(fetchNotificationHistory.fulfilled, (state, action) => {
        state.history.loading = false;
        state.history.data = action.payload.notifications || [];
        state.history.total = action.payload.total || 0;
      })
      .addCase(fetchNotificationHistory.rejected, (state, action) => {
        state.history.loading = false;
        state.history.error = action.error.message || 'Failed to fetch notification history';
      })

      // Fetch notification queue
      .addCase(fetchNotificationQueue.pending, (state) => {
        state.queue.loading = true;
        state.queue.error = null;
      })
      .addCase(fetchNotificationQueue.fulfilled, (state, action) => {
        state.queue.loading = false;
        state.queue.data = action.payload.queue || [];
        state.queue.total = action.payload.total || 0;
      })
      .addCase(fetchNotificationQueue.rejected, (state, action) => {
        state.queue.loading = false;
        state.queue.error = action.error.message || 'Failed to fetch notification queue';
      })

      // Cancel queued notification
      .addCase(cancelQueuedNotification.pending, (state) => {
        state.queue.loading = true;
      })
      .addCase(cancelQueuedNotification.fulfilled, (state, action) => {
        state.queue.loading = false;
        const index = state.queue.data.findIndex(q => q.id === action.payload);
        if (index !== -1) {
          state.queue.data[index].status = QueueStatus.CANCELLED;
        }
      })
      .addCase(cancelQueuedNotification.rejected, (state, action) => {
        state.queue.loading = false;
        state.queue.error = action.error.message || 'Failed to cancel queued notification';
      })

      // Retry failed notification
      .addCase(retryFailedNotification.pending, (state) => {
        state.queue.loading = true;
      })
      .addCase(retryFailedNotification.fulfilled, (state, action) => {
        state.queue.loading = false;
        const queueId = action.meta.arg;
        const index = state.queue.data.findIndex(q => q.id === queueId);
        if (index !== -1) {
          state.queue.data[index].status = QueueStatus.PENDING;
          state.queue.data[index].attempts = 0;
        }
      })
      .addCase(retryFailedNotification.rejected, (state, action) => {
        state.queue.loading = false;
        state.queue.error = action.error.message || 'Failed to retry failed notification';
      })

      // Send payment reminder
      .addCase(sendPaymentReminder.pending, (state) => {
        state.history.loading = true;
      })
      .addCase(sendPaymentReminder.fulfilled, (state) => {
        state.history.loading = false;
      })
      .addCase(sendPaymentReminder.rejected, (state, action) => {
        state.history.loading = false;
        state.history.error = action.error.message || 'Failed to send payment reminder';
      })

      // Send payment confirmation
      .addCase(sendPaymentConfirmation.pending, (state) => {
        state.history.loading = true;
      })
      .addCase(sendPaymentConfirmation.fulfilled, (state) => {
        state.history.loading = false;
      })
      .addCase(sendPaymentConfirmation.rejected, (state, action) => {
        state.history.loading = false;
        state.history.error = action.error.message || 'Failed to send payment confirmation';
      })

      // Send overdue notification
      .addCase(sendOverdueNotification.pending, (state) => {
        state.history.loading = true;
      })
      .addCase(sendOverdueNotification.fulfilled, (state) => {
        state.history.loading = false;
      })
      .addCase(sendOverdueNotification.rejected, (state, action) => {
        state.history.loading = false;
        state.history.error = action.error.message || 'Failed to send overdue notification';
      })

      // Fetch notification stats
      .addCase(fetchNotificationStats.pending, (state) => {
        state.stats.loading = true;
        state.stats.error = null;
      })
      .addCase(fetchNotificationStats.fulfilled, (state, action) => {
        state.stats.loading = false;
        state.stats.data = action.payload.stats;
      })
      .addCase(fetchNotificationStats.rejected, (state, action) => {
        state.stats.loading = false;
        state.stats.error = action.error.message || 'Failed to fetch notification stats';
      });
  },
});

export const {
  setFilters,
  clearFilters,
  setSelectedTemplate,
  setSelectedHistoryItem,
  resetTemplates,
  resetHistory,
  resetQueue,
  resetState,
} = notificationSlice.actions;

export default notificationSlice.reducer;