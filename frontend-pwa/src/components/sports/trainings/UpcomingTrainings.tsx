import React, { useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchTrainings } from '../../../store/sportsSlice';
import { Training } from '../../../types/sports';

interface UpcomingTrainingsProps {
  onTrainingClick?: (training: Training) => void;
  categoryId?: string;
  limit?: number;
  showHeader?: boolean;
}

const UpcomingTrainings: React.FC<UpcomingTrainingsProps> = ({
  onTrainingClick,
  categoryId,
  limit = 5,
  showHeader = true
}) => {
  const dispatch = useAppDispatch();
  const { trainings, isLoading } = useAppSelector(
    (state: any) => state.sports
  );

  useEffect(() => {
    // Fetch upcoming trainings
    const today = new Date();
    const nextWeek = new Date();
    nextWeek.setDate(today.getDate() + 7);
    
    const filters = {
      date_from: today.toISOString().split('T')[0],
      date_to: nextWeek.toISOString().split('T')[0],
      status: 'scheduled' as const,
      ...(categoryId && { category_id: categoryId }),
      per_page: limit
    };
    
    dispatch(fetchTrainings(filters));
  }, [dispatch, categoryId, limit]);

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const today = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(today.getDate() + 1);
    
    if (date.toDateString() === today.toDateString()) {
      return 'Hoy';
    } else if (date.toDateString() === tomorrow.toDateString()) {
      return 'Mañana';
    } else {
      return date.toLocaleDateString('es-ES', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
      });
    }
  };

  const formatTime = (timeString: string) => {
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getTypeLabel = (type: string) => {
    const types: { [key: string]: string } = {
      practice: 'Práctica',
      match: 'Partido',
      friendly: 'Amistoso',
      tournament: 'Torneo',
      physical: 'Físico',
      tactical: 'Táctico',
      technical: 'Técnico'
    };
    return types[type] || type;
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'practice':
        return 'bg-blue-100 text-blue-800';
      case 'match':
        return 'bg-red-100 text-red-800';
      case 'friendly':
        return 'bg-green-100 text-green-800';
      case 'tournament':
        return 'bg-purple-100 text-purple-800';
      case 'physical':
        return 'bg-orange-100 text-orange-800';
      case 'tactical':
        return 'bg-indigo-100 text-indigo-800';
      case 'technical':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getTimeUntil = (dateString: string, timeString: string) => {
    const trainingDateTime = new Date(`${dateString}T${timeString}`);
    const now = new Date();
    const diffMs = trainingDateTime.getTime() - now.getTime();
    
    if (diffMs < 0) {
      return 'Ya pasó';
    }
    
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    
    if (diffHours < 1) {
      return `En ${diffMinutes} min`;
    } else if (diffHours < 24) {
      return `En ${diffHours}h ${diffMinutes}min`;
    } else {
      const diffDays = Math.floor(diffHours / 24);
      return `En ${diffDays} día${diffDays > 1 ? 's' : ''}`;
    }
  };

  const upcomingTrainings = trainings?.data || [];

  if (isLoading) {
    return (
      <div className="bg-white shadow rounded-lg p-6">
        {showHeader && (
          <div className="mb-4">
            <div className="h-6 bg-gray-200 rounded w-1/3 animate-pulse"></div>
          </div>
        )}
        <div className="space-y-3">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="animate-pulse">
              <div className="flex items-center space-x-3">
                <div className="w-12 h-12 bg-gray-200 rounded"></div>
                <div className="flex-1">
                  <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (upcomingTrainings.length === 0) {
    return (
      <div className="bg-white shadow rounded-lg p-6">
        {showHeader && (
          <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
            Próximos Entrenamientos
          </h3>
        )}
        <div className="text-center py-8">
          <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">No hay entrenamientos próximos</h3>
          <p className="mt-1 text-sm text-gray-500">
            No se encontraron entrenamientos programados para los próximos días.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white shadow rounded-lg">
      {showHeader && (
        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h3 className="text-lg leading-6 font-medium text-gray-900">
              Próximos Entrenamientos
            </h3>
            <span className="text-sm text-gray-500">
              {upcomingTrainings.length} entrenamiento{upcomingTrainings.length !== 1 ? 's' : ''}
            </span>
          </div>
        </div>
      )}

      <div className="divide-y divide-gray-200">
        {upcomingTrainings.map((training: Training) => (
          <div
            key={training.id}
            className="p-4 hover:bg-gray-50 cursor-pointer transition-colors duration-150"
            onClick={() => onTrainingClick && onTrainingClick(training)}
          >
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                {/* Date and Time */}
                <div className="flex-shrink-0">
                  <div className="w-12 h-12 bg-blue-100 rounded-lg flex flex-col items-center justify-center">
                    <div className="text-xs font-medium text-blue-600 uppercase">
                      {formatDate(training.date).split(' ')[0]}
                    </div>
                    <div className="text-xs text-blue-500">
                      {formatDate(training.date).includes('Hoy') || formatDate(training.date).includes('Mañana') 
                        ? formatDate(training.date) 
                        : formatDate(training.date).split(' ')[1]
                      }
                    </div>
                  </div>
                </div>

                {/* Training Info */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-center space-x-2">
                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${getTypeColor(training.type)}`}>
                      {getTypeLabel(training.type)}
                    </span>
                    {training.category && (
                      <span className="text-xs text-gray-500">
                        {training.category.name}
                      </span>
                    )}
                  </div>
                  <p className="text-sm font-medium text-gray-900 truncate">
                    {formatTime(training.start_time)} - {formatTime(training.end_time)}
                  </p>
                  <p className="text-sm text-gray-500 truncate">
                    📍 {training.location}
                  </p>
                </div>
              </div>

              {/* Time Until */}
              <div className="flex-shrink-0 text-right">
                <div className="text-xs font-medium text-gray-900">
                  {getTimeUntil(training.date, training.start_time)}
                </div>
                <div className="text-xs text-gray-500">
                  <svg className="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            </div>

            {/* Additional Info */}
            {(training.objectives || training.coach_id) && (
              <div className="mt-2 pt-2 border-t border-gray-100">
                {training.objectives && (
                  <p className="text-xs text-gray-600 truncate">
                    🎯 {training.objectives}
                  </p>
                )}
                {training.coach_id && (
                  <p className="text-xs text-gray-500 mt-1">
                    👨‍🏫 Entrenador: {training.coach_id}
                  </p>
                )}
              </div>
            )}
          </div>
        ))}
      </div>

      {/* View All Link */}
      {upcomingTrainings.length >= limit && (
        <div className="px-4 py-3 bg-gray-50 border-t border-gray-200">
          <button className="text-sm text-blue-600 hover:text-blue-500 font-medium">
            Ver todos los entrenamientos →
          </button>
        </div>
      )}
    </div>
  );
};

export default UpcomingTrainings;