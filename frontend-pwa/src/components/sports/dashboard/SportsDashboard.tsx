import React, { useState, useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchDashboardData, fetchUpcomingTrainings } from '../../../store/sportsSlice';
import { Link } from 'react-router-dom';

const SportsDashboard: React.FC = () => {
  const dispatch = useAppDispatch();
  const { 
    dashboardData, 
    upcomingTrainings, 
    categories, 
    players, 
    trainings, 
    attendances,
    isLoading 
  } = useAppSelector((state: any) => ({
    dashboardData: state.sports.dashboardData,
    upcomingTrainings: state.sports.upcomingTrainings,
    categories: state.sports.categories,
    players: state.sports.players,
    trainings: state.sports.trainings,
    attendances: state.sports.attendances,
    isLoading: state.sports.isLoading
  }));

  const [selectedPeriod, setSelectedPeriod] = useState<'week' | 'month' | 'quarter'>('month');

  useEffect(() => {
    dispatch(fetchDashboardData());
    dispatch(fetchUpcomingTrainings());
  }, [dispatch]);

  const calculateQuickStats = () => {
    const now = new Date();
    const startOfPeriod = new Date();
    
    switch (selectedPeriod) {
      case 'week':
        startOfPeriod.setDate(now.getDate() - 7);
        break;
      case 'month':
        startOfPeriod.setMonth(now.getMonth() - 1);
        break;
      case 'quarter':
        startOfPeriod.setMonth(now.getMonth() - 3);
        break;
    }

    const recentTrainings = trainings.filter((training: any) => 
      new Date(training.date) >= startOfPeriod
    );

    const recentAttendances = attendances.filter((attendance: any) => 
      new Date(attendance.training?.date || attendance.created_at) >= startOfPeriod
    );

    const attendanceStats = {
      total: recentAttendances.length,
      present: recentAttendances.filter((a: any) => a.status === 'present').length,
      late: recentAttendances.filter((a: any) => a.status === 'late').length,
      absent: recentAttendances.filter((a: any) => a.status === 'absent').length
    };

    const attendanceRate = attendanceStats.total > 0 
      ? Math.round(((attendanceStats.present + attendanceStats.late) / attendanceStats.total) * 100)
      : 0;

    return {
      totalCategories: categories.length,
      totalPlayers: players.length,
      activePlayers: players.filter((p: any) => p.status === 'active').length,
      recentTrainings: recentTrainings.length,
      attendanceRate,
      upcomingCount: upcomingTrainings.length
    };
  };

  const getUpcomingTrainings = () => {
    const today = new Date();
    const nextWeek = new Date();
    nextWeek.setDate(today.getDate() + 7);

    return upcomingTrainings
      .filter((training: any) => {
        const trainingDate = new Date(training.date);
        return trainingDate >= today && trainingDate <= nextWeek;
      })
      .sort((a: any, b: any) => new Date(a.date).getTime() - new Date(b.date).getTime())
      .slice(0, 5);
  };

  const getRecentActivity = () => {
    const recentItems: any[] = [];

    // Add recent trainings
    trainings.slice(0, 3).forEach((training: any) => {
      recentItems.push({
        type: 'training',
        title: `Entrenamiento: ${training.type}`,
        subtitle: `${training.location} - ${new Date(training.date).toLocaleDateString('es-ES')}`,
        time: training.created_at,
        icon: '🏃‍♂️',
        color: 'bg-blue-100 text-blue-600'
      });
    });

    // Add recent player registrations
    players.slice(0, 2).forEach((player: any) => {
      recentItems.push({
        type: 'player',
        title: `Nuevo jugador: ${player.full_name}`,
        subtitle: player.position || 'Sin posición asignada',
        time: player.created_at,
        icon: '👤',
        color: 'bg-green-100 text-green-600'
      });
    });

    return recentItems
      .sort((a, b) => new Date(b.time).getTime() - new Date(a.time).getTime())
      .slice(0, 5);
  };

  const getCategoryStats = () => {
    return categories.map((category: any) => {
      const categoryPlayers = players.filter((p: any) => p.category_id === category.id);
      const categoryTrainings = trainings.filter((t: any) => t.category_id === category.id);
      
      return {
        ...category,
        playerCount: categoryPlayers.length,
        trainingCount: categoryTrainings.length,
        activePlayers: categoryPlayers.filter((p: any) => p.status === 'active').length
      };
    }).slice(0, 6);
  };

  const getAttendanceTrend = () => {
    const last7Days = [];
    const today = new Date();
    
    for (let i = 6; i >= 0; i--) {
      const date = new Date(today);
      date.setDate(today.getDate() - i);
      
      const dayAttendances = attendances.filter((attendance: any) => {
        const attendanceDate = new Date(attendance.training?.date || attendance.created_at);
        return attendanceDate.toDateString() === date.toDateString();
      });
      
      const dayStats = {
        date: date.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' }),
        total: dayAttendances.length,
        present: dayAttendances.filter((a: any) => a.status === 'present').length,
        rate: dayAttendances.length > 0 
          ? Math.round((dayAttendances.filter((a: any) => a.status === 'present' || a.status === 'late').length / dayAttendances.length) * 100)
          : 0
      };
      
      last7Days.push(dayStats);
    }
    
    return last7Days;
  };

  const stats = calculateQuickStats();
  const upcoming = getUpcomingTrainings();
  const recentActivity = getRecentActivity();
  const categoryStats = getCategoryStats();
  const attendanceTrend = getAttendanceTrend();

  const getPeriodLabel = (period: string): string => {
    switch (period) {
      case 'week': return 'Última semana';
      case 'month': return 'Último mes';
      case 'quarter': return 'Último trimestre';
      default: return period;
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
      {/* Header */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Dashboard Deportivo</h1>
            <p className="text-gray-600 mt-1">
              Resumen general de actividades deportivas
            </p>
          </div>
          <div className="flex items-center space-x-3">
            <select
              value={selectedPeriod}
              onChange={(e) => setSelectedPeriod(e.target.value as any)}
              className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="week">Última semana</option>
              <option value="month">Último mes</option>
              <option value="quarter">Último trimestre</option>
            </select>
            <Link
              to="/sports/trainings/new"
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Nuevo Entrenamiento
            </Link>
          </div>
        </div>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-gray-900">{stats.totalCategories}</div>
              <div className="text-sm text-gray-500">Categorías Activas</div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
              </div>
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-gray-900">
                {stats.activePlayers}
                <span className="text-sm text-gray-500">/{stats.totalPlayers}</span>
              </div>
              <div className="text-sm text-gray-500">Jugadores Activos</div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-gray-900">{stats.recentTrainings}</div>
              <div className="text-sm text-gray-500">Entrenamientos ({getPeriodLabel(selectedPeriod)})</div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg className="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
            </div>
            <div className="ml-4">
              <div className="text-2xl font-bold text-gray-900">{stats.attendanceRate}%</div>
              <div className="text-sm text-gray-500">Asistencia ({getPeriodLabel(selectedPeriod)})</div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Upcoming Trainings */}
        <div className="lg:col-span-2">
          <div className="bg-white rounded-lg shadow-sm border p-6">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold text-gray-900">Próximos Entrenamientos</h2>
              <Link 
                to="/sports/trainings" 
                className="text-sm text-blue-600 hover:text-blue-800"
              >
                Ver todos
              </Link>
            </div>
            
            {upcoming.length > 0 ? (
              <div className="space-y-3">
                {upcoming.map((training: any) => (
                  <div key={training.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div className="flex items-center space-x-3">
                      <div className="flex-shrink-0">
                        <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                          <span className="text-xs font-semibold text-blue-800">
                            {new Date(training.date).getDate()}
                          </span>
                        </div>
                      </div>
                      <div>
                        <div className="text-sm font-medium text-gray-900">
                          {training.type} - {training.location}
                        </div>
                        <div className="text-xs text-gray-500">
                          {new Date(training.date).toLocaleDateString('es-ES', { 
                            weekday: 'long', 
                            day: 'numeric', 
                            month: 'long' 
                          })} • {training.start_time} - {training.end_time}
                        </div>
                      </div>
                    </div>
                    <div className="flex items-center space-x-2">
                      <span className="text-xs text-gray-500">
                        {training.attendance_stats?.total_players || 0} jugadores
                      </span>
                      <Link
                        to={`/sports/trainings/${training.id}`}
                        className="text-xs text-blue-600 hover:text-blue-800"
                      >
                        Ver
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 112 0v1m-6 0h12l-1 9a2 2 0 01-2 2H7a2 2 0 01-2-2L4 7z" />
                </svg>
                <p className="mt-2">No hay entrenamientos programados</p>
                <Link
                  to="/sports/trainings/new"
                  className="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800"
                >
                  Programar entrenamiento
                </Link>
              </div>
            )}
          </div>
        </div>

        {/* Recent Activity */}
        <div>
          <div className="bg-white rounded-lg shadow-sm border p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Actividad Reciente</h2>
            
            {recentActivity.length > 0 ? (
              <div className="space-y-3">
                {recentActivity.map((activity, index) => (
                  <div key={index} className="flex items-start space-x-3">
                    <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${activity.color}`}>
                      <span className="text-sm">{activity.icon}</span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="text-sm font-medium text-gray-900">
                        {activity.title}
                      </div>
                      <div className="text-xs text-gray-500">
                        {activity.subtitle}
                      </div>
                      <div className="text-xs text-gray-400 mt-1">
                        {new Date(activity.time).toLocaleDateString('es-ES')}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-4 text-gray-500">
                <p className="text-sm">No hay actividad reciente</p>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Categories Overview */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-lg font-semibold text-gray-900">Categorías</h2>
          <Link 
            to="/sports/categories" 
            className="text-sm text-blue-600 hover:text-blue-800"
          >
            Gestionar categorías
          </Link>
        </div>
        
        {categoryStats.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {categoryStats.map((category: any) => (
              <div key={category.id} className="border border-gray-200 rounded-lg p-4">
                <div className="flex justify-between items-start mb-2">
                  <h3 className="font-medium text-gray-900">{category.name}</h3>
                  <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                    category.status === 'active' 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-gray-100 text-gray-800'
                  }`}>
                    {category.status === 'active' ? 'Activa' : 'Inactiva'}
                  </span>
                </div>
                <div className="text-sm text-gray-600 mb-3">
                  {category.description || 'Sin descripción'}
                </div>
                <div className="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <div className="font-medium text-gray-900">{category.activePlayers}</div>
                    <div className="text-gray-500">Jugadores activos</div>
                  </div>
                  <div>
                    <div className="font-medium text-gray-900">{category.trainingCount}</div>
                    <div className="text-gray-500">Entrenamientos</div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-8 text-gray-500">
            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <p className="mt-2">No hay categorías configuradas</p>
            <Link
              to="/sports/categories/new"
              className="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800"
            >
              Crear primera categoría
            </Link>
          </div>
        )}
      </div>

      {/* Attendance Trend */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-lg font-semibold text-gray-900">Tendencia de Asistencia (Últimos 7 días)</h2>
          <Link 
            to="/sports/attendances/stats" 
            className="text-sm text-blue-600 hover:text-blue-800"
          >
            Ver estadísticas completas
          </Link>
        </div>
        
        <div className="grid grid-cols-7 gap-2">
          {attendanceTrend.map((day, index) => (
            <div key={index} className="text-center">
              <div className="text-xs text-gray-500 mb-2">{day.date}</div>
              <div className="h-20 bg-gray-100 rounded flex items-end justify-center p-1">
                {day.total > 0 && (
                  <div 
                    className="bg-blue-500 rounded-sm w-full transition-all duration-300"
                    style={{ height: `${Math.max(day.rate, 5)}%` }}
                    title={`${day.rate}% asistencia (${day.present}/${day.total})`}
                  ></div>
                )}
              </div>
              <div className="text-xs text-gray-600 mt-1">
                {day.rate}%
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default SportsDashboard;