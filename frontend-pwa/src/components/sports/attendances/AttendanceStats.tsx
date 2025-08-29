import React, { useState, useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchAttendances } from '../../../store/sportsSlice';
import { AttendanceFilters, AttendanceStatus } from '../../../types/sports';

interface AttendanceStatsProps {
  playerId?: string;
  categoryId?: string;
  dateRange?: {
    start: string;
    end: string;
  };
}

const AttendanceStats: React.FC<AttendanceStatsProps> = ({
  playerId,
  categoryId,
  dateRange
}) => {
  const dispatch = useAppDispatch();
  const { attendances, isLoading } = useAppSelector((state: any) => ({
    attendances: state.sports.attendances,
    isLoading: state.sports.isLoading
  }));

  const [timeRange, setTimeRange] = useState<'week' | 'month' | '3months' | 'year'>('month');
  const [selectedPlayer, setSelectedPlayer] = useState<string>(playerId || '');

  useEffect(() => {
    const filters: AttendanceFilters = {
      page: 1,
      per_page: 1000 // Get all for stats
    };

    if (selectedPlayer) {
      filters.player_id = selectedPlayer;
    }

    if (categoryId) {
      filters.category_id = categoryId;
    }

    if (dateRange) {
      filters.date_from = dateRange.start;
      filters.date_to = dateRange.end;
    } else {
      // Set date range based on timeRange
      const endDate = new Date();
      const startDate = new Date();
      
      switch (timeRange) {
        case 'week':
          startDate.setDate(endDate.getDate() - 7);
          break;
        case 'month':
          startDate.setMonth(endDate.getMonth() - 1);
          break;
        case '3months':
          startDate.setMonth(endDate.getMonth() - 3);
          break;
        case 'year':
          startDate.setFullYear(endDate.getFullYear() - 1);
          break;
      }
      
      filters.date_from = startDate.toISOString().split('T')[0];
      filters.date_to = endDate.toISOString().split('T')[0];
    }

    dispatch(fetchAttendances(filters));
  }, [dispatch, selectedPlayer, categoryId, dateRange, timeRange]);

  const calculateStats = () => {
    const stats = {
      total: attendances.length,
      present: 0,
      absent: 0,
      late: 0,
      excused: 0,
      pending: 0,
      attendanceRate: 0,
      punctualityRate: 0,
      totalLateMinutes: 0,
      averageLateMinutes: 0
    };

    let lateCount = 0;
    let totalLateMinutes = 0;

    attendances.forEach((attendance: any) => {
      stats[attendance.status as keyof typeof stats]++;
      
      if (attendance.status === 'late' && attendance.late_minutes) {
        lateCount++;
        totalLateMinutes += attendance.late_minutes;
      }
    });

    if (stats.total > 0) {
      stats.attendanceRate = Math.round(((stats.present + stats.late) / stats.total) * 100);
      stats.punctualityRate = Math.round((stats.present / (stats.present + stats.late)) * 100);
    }

    if (lateCount > 0) {
      stats.totalLateMinutes = totalLateMinutes;
      stats.averageLateMinutes = Math.round(totalLateMinutes / lateCount);
    }

    return stats;
  };

  const getAttendanceByMonth = () => {
    const monthlyData: { [key: string]: {
      present: number;
      absent: number;
      late: number;
      excused: number;
      total: number;
    } } = {};

    attendances.forEach((attendance: any) => {
      const date = new Date(attendance.training?.date || attendance.created_at);
      const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
      
      if (!monthlyData[monthKey]) {
        monthlyData[monthKey] = {
          present: 0,
          absent: 0,
          late: 0,
          excused: 0,
          total: 0
        };
      }
      
      monthlyData[monthKey][attendance.status as keyof typeof monthlyData[typeof monthKey]]++;
      monthlyData[monthKey].total++;
    });

    return Object.entries(monthlyData)
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([month, data]) => ({
        month,
        ...data,
        attendanceRate: data.total > 0 ? Math.round(((data.present + data.late) / data.total) * 100) : 0
      }));
  };

  const getTopPlayers = () => {
    if (playerId) return []; // Don't show top players if viewing individual stats
    
    const playerStats: { [key: string]: {
      name: string;
      present: number;
      absent: number;
      late: number;
      excused: number;
      total: number;
      attendanceRate: number;
    } } = {};

    attendances.forEach((attendance: any) => {
      const playerId = attendance.player?.id;
      const playerName = attendance.player?.full_name || 'Jugador desconocido';
      
      if (!playerId) return;
      
      if (!playerStats[playerId]) {
        playerStats[playerId] = {
          name: playerName,
          present: 0,
          absent: 0,
          late: 0,
          excused: 0,
          total: 0,
          attendanceRate: 0
        };
      }
      
      playerStats[playerId][attendance.status as keyof typeof playerStats[typeof playerId]]++;
      playerStats[playerId].total++;
    });

    return Object.values(playerStats)
      .map(player => ({
        ...player,
        attendanceRate: player.total > 0 ? Math.round(((player.present + player.late) / player.total) * 100) : 0
      }))
      .sort((a, b) => b.attendanceRate - a.attendanceRate)
      .slice(0, 5);
  };

  const stats = calculateStats();
  const monthlyData = getAttendanceByMonth();
  const topPlayers = getTopPlayers();

  const getTimeRangeLabel = (range: string): string => {
    switch (range) {
      case 'week': return 'Última semana';
      case 'month': return 'Último mes';
      case '3months': return 'Últimos 3 meses';
      case 'year': return 'Último año';
      default: return range;
    }
  };

  const getStatusColor = (status: string): string => {
    switch (status) {
      case 'present': return 'text-green-600';
      case 'absent': return 'text-red-600';
      case 'late': return 'text-yellow-600';
      case 'excused': return 'text-blue-600';
      default: return 'text-gray-600';
    }
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header and Controls */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-semibold text-gray-900">
            Estadísticas de Asistencia
          </h2>
          
          {!dateRange && (
            <select
              value={timeRange}
              onChange={(e) => setTimeRange(e.target.value as any)}
              className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="week">Última semana</option>
              <option value="month">Último mes</option>
              <option value="3months">Últimos 3 meses</option>
              <option value="year">Último año</option>
            </select>
          )}
        </div>
        
        <div className="text-sm text-gray-600">
          Período: {dateRange ? 
            `${new Date(dateRange.start).toLocaleDateString('es-ES')} - ${new Date(dateRange.end).toLocaleDateString('es-ES')}` :
            getTimeRangeLabel(timeRange)
          }
        </div>
      </div>

      {/* Main Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-gray-900">{stats.total}</div>
              <div className="text-sm text-gray-500">Total Entrenamientos</div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-green-600">{stats.attendanceRate}%</div>
              <div className="text-sm text-gray-500">Tasa de Asistencia</div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-blue-600">{stats.punctualityRate}%</div>
              <div className="text-sm text-gray-500">Puntualidad</div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg className="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-yellow-600">{stats.averageLateMinutes}</div>
              <div className="text-sm text-gray-500">Min. Tardanza Promedio</div>
            </div>
          </div>
        </div>
      </div>

      {/* Detailed Stats */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Status Breakdown */}
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Desglose por Estado</h3>
          <div className="space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-sm text-gray-600">Presentes</span>
              <div className="flex items-center space-x-2">
                <div className="w-20 bg-gray-200 rounded-full h-2">
                  <div 
                    className="bg-green-500 h-2 rounded-full" 
                    style={{ width: `${stats.total > 0 ? (stats.present / stats.total) * 100 : 0}%` }}
                  ></div>
                </div>
                <span className="text-sm font-medium text-green-600">{stats.present}</span>
              </div>
            </div>
            
            <div className="flex justify-between items-center">
              <span className="text-sm text-gray-600">Tardanzas</span>
              <div className="flex items-center space-x-2">
                <div className="w-20 bg-gray-200 rounded-full h-2">
                  <div 
                    className="bg-yellow-500 h-2 rounded-full" 
                    style={{ width: `${stats.total > 0 ? (stats.late / stats.total) * 100 : 0}%` }}
                  ></div>
                </div>
                <span className="text-sm font-medium text-yellow-600">{stats.late}</span>
              </div>
            </div>
            
            <div className="flex justify-between items-center">
              <span className="text-sm text-gray-600">Ausentes</span>
              <div className="flex items-center space-x-2">
                <div className="w-20 bg-gray-200 rounded-full h-2">
                  <div 
                    className="bg-red-500 h-2 rounded-full" 
                    style={{ width: `${stats.total > 0 ? (stats.absent / stats.total) * 100 : 0}%` }}
                  ></div>
                </div>
                <span className="text-sm font-medium text-red-600">{stats.absent}</span>
              </div>
            </div>
            
            <div className="flex justify-between items-center">
              <span className="text-sm text-gray-600">Justificados</span>
              <div className="flex items-center space-x-2">
                <div className="w-20 bg-gray-200 rounded-full h-2">
                  <div 
                    className="bg-blue-500 h-2 rounded-full" 
                    style={{ width: `${stats.total > 0 ? (stats.excused / stats.total) * 100 : 0}%` }}
                  ></div>
                </div>
                <span className="text-sm font-medium text-blue-600">{stats.excused}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Top Players (if not viewing individual stats) */}
        {topPlayers.length > 0 && (
          <div className="bg-white rounded-lg shadow-sm border p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Mejores Asistencias</h3>
            <div className="space-y-3">
              {topPlayers.map((player, index) => (
                <div key={index} className="flex justify-between items-center">
                  <div className="flex items-center space-x-3">
                    <div className="flex-shrink-0">
                      <div className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold ${
                        index === 0 ? 'bg-yellow-100 text-yellow-800' :
                        index === 1 ? 'bg-gray-100 text-gray-800' :
                        index === 2 ? 'bg-orange-100 text-orange-800' :
                        'bg-blue-100 text-blue-800'
                      }`}>
                        {index + 1}
                      </div>
                    </div>
                    <span className="text-sm text-gray-900">{player.name}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className="text-sm font-medium text-green-600">{player.attendanceRate}%</span>
                    <span className="text-xs text-gray-500">({player.total})</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Monthly Trend */}
      {monthlyData.length > 0 && (
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Tendencia Mensual</h3>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Mes
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Total
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Presentes
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tardanzas
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Ausentes
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Asistencia
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {monthlyData.map((data) => (
                  <tr key={data.month}>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {new Date(data.month + '-01').toLocaleDateString('es-ES', { year: 'numeric', month: 'long' })}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {data.total}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                      {data.present}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                      {data.late}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                      {data.absent}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {data.attendanceRate}%
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
};

export default AttendanceStats;