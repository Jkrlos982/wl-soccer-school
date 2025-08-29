import { apiClient as api } from './api';
import {
  Category,
  CreateCategoryData,
  UpdateCategoryData,
  CategoryFilters,
  Player,
  CreatePlayerData,
  UpdatePlayerData,
  PlayerFilters,
  PlayerStats,
  Training,
  CreateTrainingData,
  UpdateTrainingData,
  CompleteTrainingData,
  TrainingFilters,
  Attendance,
  UpdateAttendanceData,
  BulkAttendanceData,
  AttendanceFilters,
  AttendanceStats,
  PaginatedResponse,
  SportsDashboardData
} from '../types/sports';

class SportsService {
  private baseURL = '/api/v1';

  // ==================== CATEGORÍAS ====================
  
  /**
   * Obtener lista de categorías con filtros
   */
  async getCategories(filters?: CategoryFilters): Promise<PaginatedResponse<Category>> {
    const params = new URLSearchParams();
    
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          if (Array.isArray(value)) {
            value.forEach(v => params.append(`${key}[]`, v.toString()));
          } else {
            params.append(key, value.toString());
          }
        }
      });
    }
    
    const response = await api.get(`${this.baseURL}/categories?${params.toString()}`);
    return response.data;
  }

  /**
   * Obtener categorías públicas (sin autenticación)
   */
  async getPublicCategories(): Promise<Category[]> {
    const response = await api.get(`${this.baseURL}/public/categories`);
    return response.data.data;
  }

  /**
   * Obtener una categoría por ID
   */
  async getCategory(id: string): Promise<Category> {
    const response = await api.get(`${this.baseURL}/categories/${id}`);
    return response.data.data;
  }

  /**
   * Crear nueva categoría
   */
  async createCategory(data: CreateCategoryData): Promise<Category> {
    const response = await api.post(`${this.baseURL}/categories`, data);
    return response.data.data;
  }

  /**
   * Actualizar categoría
   */
  async updateCategory(id: string, data: UpdateCategoryData): Promise<Category> {
    const response = await api.put(`${this.baseURL}/categories/${id}`, data);
    return response.data.data;
  }

  /**
   * Eliminar categoría
   */
  async deleteCategory(id: string): Promise<void> {
    await api.delete(`${this.baseURL}/categories/${id}`);
  }

  // ==================== JUGADORES ====================

  /**
   * Obtener lista de jugadores con filtros
   */
  async getPlayers(filters?: PlayerFilters): Promise<PaginatedResponse<Player>> {
    const params = new URLSearchParams();
    
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, value.toString());
        }
      });
    }
    
    const response = await api.get(`${this.baseURL}/players?${params.toString()}`);
    return response.data;
  }

  /**
   * Obtener jugadores por categoría
   */
  async getPlayersByCategory(categoryId: string): Promise<Player[]> {
    const response = await api.get(`${this.baseURL}/categories/${categoryId}/players`);
    return response.data.data;
  }

  /**
   * Obtener un jugador por ID
   */
  async getPlayer(id: string): Promise<Player> {
    const response = await api.get(`${this.baseURL}/players/${id}`);
    return response.data.data;
  }

  /**
   * Crear nuevo jugador
   */
  async createPlayer(data: CreatePlayerData): Promise<Player> {
    const response = await api.post(`${this.baseURL}/players`, data);
    return response.data.data;
  }

  /**
   * Actualizar jugador
   */
  async updatePlayer(id: string, data: UpdatePlayerData): Promise<Player> {
    const response = await api.put(`${this.baseURL}/players/${id}`, data);
    return response.data.data;
  }

  /**
   * Eliminar jugador
   */
  async deletePlayer(id: string): Promise<void> {
    await api.delete(`${this.baseURL}/players/${id}`);
  }

  /**
   * Obtener estadísticas de un jugador
   */
  async getPlayerStats(id: string): Promise<PlayerStats> {
    const response = await api.get(`${this.baseURL}/players/${id}/statistics`);
    return response.data.data;
  }

  /**
   * Subir foto de jugador
   */
  async uploadPlayerPhoto(id: string, file: File): Promise<{ photo_url: string }> {
    const formData = new FormData();
    formData.append('photo', file);
    
    const response = await api.post(`${this.baseURL}/players/${id}/upload-photo`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data.data;
  }

  // ==================== ENTRENAMIENTOS ====================

  /**
   * Obtener lista de entrenamientos con filtros
   */
  async getTrainings(filters?: TrainingFilters): Promise<PaginatedResponse<Training>> {
    const params = new URLSearchParams();
    
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          if (Array.isArray(value)) {
            value.forEach(v => params.append(`${key}[]`, v.toString()));
          } else {
            params.append(key, value.toString());
          }
        }
      });
    }
    
    const response = await api.get(`${this.baseURL}/trainings?${params.toString()}`);
    return response.data;
  }

  /**
   * Obtener próximos entrenamientos
   */
  async getUpcomingTrainings(): Promise<Training[]> {
    const response = await api.get(`${this.baseURL}/trainings/upcoming`);
    return response.data.data;
  }

  /**
   * Obtener entrenamientos por categoría
   */
  async getTrainingsByCategory(categoryId: string): Promise<Training[]> {
    const response = await api.get(`${this.baseURL}/categories/${categoryId}/trainings`);
    return response.data.data;
  }

  /**
   * Obtener un entrenamiento por ID
   */
  async getTraining(id: string): Promise<Training> {
    const response = await api.get(`${this.baseURL}/trainings/${id}`);
    return response.data.data;
  }

  /**
   * Crear nuevo entrenamiento
   */
  async createTraining(data: CreateTrainingData): Promise<Training> {
    const response = await api.post(`${this.baseURL}/trainings`, data);
    return response.data.data;
  }

  /**
   * Actualizar entrenamiento
   */
  async updateTraining(id: string, data: UpdateTrainingData): Promise<Training> {
    const response = await api.put(`${this.baseURL}/trainings/${id}`, data);
    return response.data.data;
  }

  /**
   * Eliminar entrenamiento
   */
  async deleteTraining(id: string): Promise<void> {
    await api.delete(`${this.baseURL}/trainings/${id}`);
  }

  /**
   * Iniciar entrenamiento
   */
  async startTraining(id: string): Promise<Training> {
    const response = await api.post(`${this.baseURL}/trainings/${id}/start`);
    return response.data.data;
  }

  /**
   * Completar entrenamiento
   */
  async completeTraining(id: string, data: CompleteTrainingData): Promise<Training> {
    const response = await api.post(`${this.baseURL}/trainings/${id}/complete`, data);
    return response.data.data;
  }

  /**
   * Cancelar entrenamiento
   */
  async cancelTraining(id: string): Promise<Training> {
    const response = await api.post(`${this.baseURL}/trainings/${id}/cancel`);
    return response.data.data;
  }

  /**
   * Obtener estadísticas de entrenamientos
   */
  async getTrainingStatistics(): Promise<any> {
    const response = await api.get(`${this.baseURL}/trainings/statistics`);
    return response.data.data;
  }

  // ==================== ASISTENCIAS ====================

  /**
   * Obtener lista de asistencias con filtros
   */
  async getAttendances(filters?: AttendanceFilters): Promise<PaginatedResponse<Attendance>> {
    const params = new URLSearchParams();
    
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, value.toString());
        }
      });
    }
    
    const response = await api.get(`${this.baseURL}/attendances?${params.toString()}`);
    return response.data;
  }

  /**
   * Obtener asistencias de un entrenamiento
   */
  async getAttendancesByTraining(trainingId: string): Promise<Attendance[]> {
    const response = await api.get(`${this.baseURL}/trainings/${trainingId}/attendances`);
    return response.data.data;
  }

  /**
   * Actualizar asistencia individual
   */
  async updateAttendance(id: string, data: UpdateAttendanceData): Promise<Attendance> {
    const response = await api.put(`${this.baseURL}/attendances/${id}`, data);
    return response.data.data;
  }

  /**
   * Actualización masiva de asistencias
   */
  async bulkUpdateAttendance(data: BulkAttendanceData): Promise<Attendance[]> {
    const response = await api.put(`${this.baseURL}/attendances/bulk-update`, data);
    return response.data.data;
  }

  /**
   * Obtener estadísticas de asistencia de un jugador
   */
  async getPlayerAttendanceStats(playerId: string, period?: number): Promise<AttendanceStats> {
    const params = period ? `?period=${period}` : '';
    const response = await api.get(`${this.baseURL}/players/${playerId}/attendance-stats${params}`);
    return response.data.data;
  }

  /**
   * Obtener reporte de asistencia por categoría
   */
  async getCategoryAttendanceReport(categoryId: string): Promise<any> {
    const response = await api.get(`${this.baseURL}/categories/${categoryId}/attendance-report`);
    return response.data.data;
  }

  // ==================== DASHBOARD ====================

  /**
   * Obtener datos del dashboard deportivo
   */
  async getDashboardData(): Promise<SportsDashboardData> {
    const [summary, todaysTrainings, upcomingTrainings, attendanceTrends, topPlayers, categoryActivity] = await Promise.all([
      this.getSummaryStats(),
      this.getTodaysTrainings(),
      this.getUpcomingTrainings(),
      this.getAttendanceTrends(),
      this.getTopPlayersByAttendance(),
      this.getCategoryActivity()
    ]);

    return {
      summary,
      todays_trainings: todaysTrainings,
      upcoming_trainings: upcomingTrainings.slice(0, 5), // Limitar a 5
      attendance_trends: attendanceTrends,
      top_players: topPlayers,
      category_activity: categoryActivity,
      recent_activities: [] // Se puede implementar más tarde
    };
  }

  /**
   * Obtener estadísticas resumidas
   */
  private async getSummaryStats(): Promise<SportsDashboardData['summary']> {
    // Obtener datos básicos
    const [categories, players, todaysTrainings] = await Promise.all([
      this.getCategories({ is_active: true, per_page: 1 }),
      this.getPlayers({ is_active: true, per_page: 1 }),
      this.getTodaysTrainings()
    ]);

    const upcomingTrainings = await this.getUpcomingTrainings();
    
    // Calcular tasa de asistencia general (simplificado)
    let overallAttendanceRate = 0;
    try {
      const attendanceStats = await this.getTrainingStatistics();
      overallAttendanceRate = attendanceStats.overall_attendance_rate || 0;
    } catch (error) {
      console.warn('No se pudieron obtener estadísticas de asistencia:', error);
    }

    return {
      total_categories: categories.total,
      active_categories: categories.total, // Asumiendo que solo obtenemos activas
      total_players: players.total,
      active_players: players.total, // Asumiendo que solo obtenemos activos
      todays_trainings: todaysTrainings.length,
      upcoming_trainings: upcomingTrainings.length,
      overall_attendance_rate: overallAttendanceRate
    };
  }

  /**
   * Obtener entrenamientos de hoy
   */
  private async getTodaysTrainings(): Promise<Training[]> {
    const today = new Date().toISOString().split('T')[0];
    const response = await this.getTrainings({
      date_from: today,
      date_to: today,
      per_page: 50
    });
    return response.data;
  }

  /**
   * Obtener tendencias de asistencia (últimos 30 días)
   */
  private async getAttendanceTrends(): Promise<SportsDashboardData['attendance_trends']> {
    // Implementación simplificada - en producción esto vendría del backend
    const trends = [];
    const today = new Date();
    
    for (let i = 29; i >= 0; i--) {
      const date = new Date(today);
      date.setDate(date.getDate() - i);
      
      trends.push({
        date: date.toISOString().split('T')[0],
        attendance_rate: Math.random() * 30 + 70, // Datos simulados
        total_trainings: Math.floor(Math.random() * 5) + 1
      });
    }
    
    return trends;
  }

  /**
   * Obtener mejores jugadores por asistencia
   */
  private async getTopPlayersByAttendance(): Promise<SportsDashboardData['top_players']> {
    try {
      const players = await this.getPlayers({ is_active: true, per_page: 10 });
      
      // Obtener estadísticas para cada jugador
      const playersWithStats = await Promise.all(
        players.data.slice(0, 5).map(async (player) => {
          try {
            const stats = await this.getPlayerAttendanceStats(player.id);
            return {
              player,
              attendance_rate: stats.attendance_rate,
              total_trainings: stats.total
            };
          } catch (error) {
            return {
              player,
              attendance_rate: player.attendance_rate || 0,
              total_trainings: player.total_trainings || 0
            };
          }
        })
      );
      
      return playersWithStats.sort((a, b) => b.attendance_rate - a.attendance_rate);
    } catch (error) {
      console.warn('No se pudieron obtener estadísticas de jugadores:', error);
      return [];
    }
  }

  /**
   * Obtener actividad por categoría
   */
  private async getCategoryActivity(): Promise<SportsDashboardData['category_activity']> {
    try {
      const categories = await this.getCategories({ is_active: true, per_page: 10 });
      
      const categoryActivity = categories.data.map(category => ({
        category,
        trainings_count: category.trainings_count || 0,
        avg_attendance_rate: Math.random() * 30 + 70, // Datos simulados
        active_players: category.active_players_count || 0
      }));
      
      return categoryActivity.sort((a, b) => b.trainings_count - a.trainings_count);
    } catch (error) {
      console.warn('No se pudo obtener actividad de categorías:', error);
      return [];
    }
  }
}

// Exportar instancia singleton
export const sportsService = new SportsService();
export default sportsService;

// Exportar la clase para testing
export { SportsService };