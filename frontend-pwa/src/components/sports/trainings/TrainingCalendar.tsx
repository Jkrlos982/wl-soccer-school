import React, { useState, useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchTrainings } from '../../../store/sportsSlice';
import { Training } from '../../../types/sports';

interface TrainingCalendarProps {
  onTrainingClick?: (training: Training) => void;
  categoryId?: string;
}

const TrainingCalendar: React.FC<TrainingCalendarProps> = ({
  onTrainingClick,
  categoryId
}) => {
  const dispatch = useAppDispatch();
  const { trainings, isLoading } = useAppSelector(
    (state: any) => state.sports
  );

  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);

  useEffect(() => {
    // Fetch trainings for the current month
    const startDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const endDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    
    const filters = {
      date_from: startDate.toISOString().split('T')[0],
      date_to: endDate.toISOString().split('T')[0],
      ...(categoryId && { category_id: categoryId })
    };
    
    dispatch(fetchTrainings(filters));
  }, [dispatch, currentDate, categoryId]);

  const getDaysInMonth = (date: Date) => {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
  };

  const getFirstDayOfMonth = (date: Date) => {
    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    return firstDay === 0 ? 6 : firstDay - 1; // Convert Sunday (0) to be last (6)
  };

  const getTrainingsForDate = (date: Date) => {
    const dateString = date.toISOString().split('T')[0];
    return trainings?.data?.filter((training: Training) => training.date === dateString) || [];
  };

  const navigateMonth = (direction: 'prev' | 'next') => {
    setCurrentDate(prev => {
      const newDate = new Date(prev);
      if (direction === 'prev') {
        newDate.setMonth(prev.getMonth() - 1);
      } else {
        newDate.setMonth(prev.getMonth() + 1);
      }
      return newDate;
    });
  };

  const goToToday = () => {
    setCurrentDate(new Date());
    setSelectedDate(new Date());
  };

  const formatTime = (timeString: string) => {
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'practice':
        return 'bg-blue-500';
      case 'match':
        return 'bg-red-500';
      case 'friendly':
        return 'bg-green-500';
      case 'tournament':
        return 'bg-purple-500';
      case 'physical':
        return 'bg-orange-500';
      case 'tactical':
        return 'bg-indigo-500';
      case 'technical':
        return 'bg-yellow-500';
      default:
        return 'bg-gray-500';
    }
  };

  const renderCalendarDays = () => {
    const daysInMonth = getDaysInMonth(currentDate);
    const firstDay = getFirstDayOfMonth(currentDate);
    const days = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Empty cells for days before the first day of the month
    for (let i = 0; i < firstDay; i++) {
      days.push(
        <div key={`empty-${i}`} className="h-32 bg-gray-50"></div>
      );
    }

    // Days of the month
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
      const dayTrainings = getTrainingsForDate(date);
      const isToday = date.getTime() === today.getTime();
      const isSelected = selectedDate && date.getTime() === selectedDate.getTime();

      days.push(
        <div
          key={day}
          className={`h-32 border border-gray-200 p-1 cursor-pointer hover:bg-gray-50 ${
            isToday ? 'bg-blue-50 border-blue-300' : ''
          } ${
            isSelected ? 'bg-blue-100 border-blue-400' : ''
          }`}
          onClick={() => setSelectedDate(date)}
        >
          <div className={`text-sm font-medium mb-1 ${
            isToday ? 'text-blue-600' : 'text-gray-900'
          }`}>
            {day}
          </div>
          <div className="space-y-1 overflow-hidden">
            {dayTrainings.slice(0, 3).map((training: Training) => (
              <div
                key={training.id}
                className={`text-xs text-white px-1 py-0.5 rounded cursor-pointer hover:opacity-80 ${getTypeColor(training.type)}`}
                onClick={(e) => {
                  e.stopPropagation();
                  if (onTrainingClick) {
                    onTrainingClick(training);
                  }
                }}
                title={`${training.type} - ${formatTime(training.start_time)}`}
              >
                <div className="truncate">
                  {formatTime(training.start_time)}
                </div>
              </div>
            ))}
            {dayTrainings.length > 3 && (
              <div className="text-xs text-gray-500 px-1">
                +{dayTrainings.length - 3} más
              </div>
            )}
          </div>
        </div>
      );
    }

    return days;
  };

  const selectedDateTrainings = selectedDate ? getTrainingsForDate(selectedDate) : [];

  const monthNames = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];

  const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

  if (isLoading) {
    return (
      <div className="bg-white shadow rounded-lg p-6">
        <div className="animate-pulse">
          <div className="h-8 bg-gray-200 rounded w-1/3 mb-4"></div>
          <div className="grid grid-cols-7 gap-1">
            {Array.from({ length: 35 }).map((_, i) => (
              <div key={i} className="h-32 bg-gray-200 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white shadow rounded-lg">
      {/* Header */}
      <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div className="flex justify-between items-center">
          <h3 className="text-lg leading-6 font-medium text-gray-900">
            Calendario de Entrenamientos
          </h3>
          <div className="flex items-center space-x-2">
            <button
              onClick={goToToday}
              className="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Hoy
            </button>
          </div>
        </div>
      </div>

      <div className="p-4">
        {/* Month Navigation */}
        <div className="flex justify-between items-center mb-4">
          <button
            onClick={() => navigateMonth('prev')}
            className="p-2 hover:bg-gray-100 rounded-md"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <h2 className="text-xl font-semibold text-gray-900">
            {monthNames[currentDate.getMonth()]} {currentDate.getFullYear()}
          </h2>
          
          <button
            onClick={() => navigateMonth('next')}
            className="p-2 hover:bg-gray-100 rounded-md"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        {/* Calendar Grid */}
        <div className="grid grid-cols-7 gap-1">
          {/* Day Headers */}
          {dayNames.map(day => (
            <div key={day} className="h-8 flex items-center justify-center text-sm font-medium text-gray-500 bg-gray-50">
              {day}
            </div>
          ))}
          
          {/* Calendar Days */}
          {renderCalendarDays()}
        </div>

        {/* Legend */}
        <div className="mt-4 flex flex-wrap gap-4 text-xs">
          <div className="flex items-center">
            <div className="w-3 h-3 bg-blue-500 rounded mr-1"></div>
            <span>Práctica</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-red-500 rounded mr-1"></div>
            <span>Partido</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-green-500 rounded mr-1"></div>
            <span>Amistoso</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-purple-500 rounded mr-1"></div>
            <span>Torneo</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-orange-500 rounded mr-1"></div>
            <span>Físico</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-indigo-500 rounded mr-1"></div>
            <span>Táctico</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-yellow-500 rounded mr-1"></div>
            <span>Técnico</span>
          </div>
        </div>
      </div>

      {/* Selected Date Details */}
      {selectedDate && selectedDateTrainings.length > 0 && (
        <div className="border-t border-gray-200 p-4">
          <h4 className="text-sm font-medium text-gray-900 mb-3">
            Entrenamientos del {selectedDate.toLocaleDateString('es-ES', {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            })}
          </h4>
          <div className="space-y-2">
            {selectedDateTrainings.map((training: Training) => (
              <div
                key={training.id}
                className="flex items-center justify-between p-2 bg-gray-50 rounded-md cursor-pointer hover:bg-gray-100"
                onClick={() => onTrainingClick && onTrainingClick(training)}
              >
                <div className="flex items-center">
                  <div className={`w-3 h-3 rounded mr-3 ${getTypeColor(training.type)}`}></div>
                  <div>
                    <div className="text-sm font-medium text-gray-900">
                      {formatTime(training.start_time)} - {formatTime(training.end_time)}
                    </div>
                    <div className="text-xs text-gray-500">
                      {training.location} • {training.category?.name}
                    </div>
                  </div>
                </div>
                <div className="text-xs text-gray-400">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default TrainingCalendar;