import React, { useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchTraining, deleteTraining } from '../../../store/sportsSlice';
import { Training } from '../../../types/sports';

interface TrainingDetailProps {
  trainingId: string;
  onEdit?: (training: Training) => void;
  onDelete?: (trainingId: string) => void;
  onBack?: () => void;
}

const TrainingDetail: React.FC<TrainingDetailProps> = ({
  trainingId,
  onEdit,
  onDelete,
  onBack
}) => {
  const dispatch = useAppDispatch();
  const { currentTraining, isLoading } = useAppSelector(
    (state: any) => state.sports
  );

  useEffect(() => {
    if (trainingId) {
      dispatch(fetchTraining(trainingId));
    }
  }, [dispatch, trainingId]);

  const handleEdit = () => {
    if (currentTraining && onEdit) {
      onEdit(currentTraining);
    }
  };

  const handleDelete = async () => {
    if (!currentTraining?.id) return;
    
    const confirmed = window.confirm(
      '¿Estás seguro de que deseas eliminar este entrenamiento? Esta acción no se puede deshacer.'
    );
    
    if (confirmed) {
      try {
        await dispatch(deleteTraining(currentTraining.id)).unwrap();
        if (onDelete) {
          onDelete(currentTraining.id);
        }
      } catch (error) {
        console.error('Error deleting training:', error);
        alert('Error al eliminar el entrenamiento. Por favor, inténtalo de nuevo.');
      }
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
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

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'scheduled':
        return 'bg-blue-100 text-blue-800';
      case 'in_progress':
        return 'bg-yellow-100 text-yellow-800';
      case 'completed':
        return 'bg-green-100 text-green-800';
      case 'cancelled':
        return 'bg-red-100 text-red-800';
      case 'postponed':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusLabel = (status: string) => {
    const statuses: { [key: string]: string } = {
      scheduled: 'Programado',
      in_progress: 'En Progreso',
      completed: 'Completado',
      cancelled: 'Cancelado',
      postponed: 'Pospuesto'
    };
    return statuses[status] || status;
  };

  if (isLoading) {
    return (
      <div className="bg-white shadow rounded-lg p-6">
        <div className="animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
          <div className="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
          <div className="h-4 bg-gray-200 rounded w-2/3 mb-2"></div>
          <div className="h-4 bg-gray-200 rounded w-1/3"></div>
        </div>
      </div>
    );
  }

  if (!currentTraining) {
    return (
      <div className="bg-white shadow rounded-lg p-6">
        <div className="text-center">
          <p className="text-gray-500">Entrenamiento no encontrado</p>
          {onBack && (
            <button
              onClick={onBack}
              className="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              Volver
            </button>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white shadow rounded-lg">
      {/* Header */}
      <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div className="flex justify-between items-start">
          <div>
            <h3 className="text-lg leading-6 font-medium text-gray-900">
              Entrenamiento - {getTypeLabel(currentTraining.type)}
            </h3>
            <p className="mt-1 max-w-2xl text-sm text-gray-500">
              {formatDate(currentTraining.date)} • {formatTime(currentTraining.start_time)} - {formatTime(currentTraining.end_time)}
            </p>
          </div>
          <div className="flex items-center space-x-2">
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(currentTraining.status)}`}>
              {getStatusLabel(currentTraining.status)}
            </span>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="px-4 py-5 sm:px-6">
        <dl className="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
          {/* Información básica */}
          <div>
            <dt className="text-sm font-medium text-gray-500">Categoría</dt>
            <dd className="mt-1 text-sm text-gray-900">
              {currentTraining.category?.name} ({currentTraining.category?.age_group})
            </dd>
          </div>

          <div>
            <dt className="text-sm font-medium text-gray-500">Ubicación</dt>
            <dd className="mt-1 text-sm text-gray-900">{currentTraining.location}</dd>
          </div>

          <div>
            <dt className="text-sm font-medium text-gray-500">Tipo</dt>
            <dd className="mt-1 text-sm text-gray-900">{getTypeLabel(currentTraining.type)}</dd>
          </div>

          <div>
            <dt className="text-sm font-medium text-gray-500">Duración</dt>
            <dd className="mt-1 text-sm text-gray-900">
              {formatTime(currentTraining.start_time)} - {formatTime(currentTraining.end_time)}
            </dd>
          </div>

          {currentTraining.coach_id && (
            <div>
              <dt className="text-sm font-medium text-gray-500">Entrenador</dt>
              <dd className="mt-1 text-sm text-gray-900">{currentTraining.coach_id}</dd>
            </div>
          )}

          {/* Estadísticas de asistencia */}
          {currentTraining.attendance_stats && (
            <div>
              <dt className="text-sm font-medium text-gray-500">Asistencia</dt>
              <dd className="mt-1 text-sm text-gray-900">
                <div className="flex space-x-4">
                  <span className="text-green-600">Presentes: {currentTraining.attendance_stats.present}</span>
                  <span className="text-red-600">Ausentes: {currentTraining.attendance_stats.absent}</span>
                  <span className="text-yellow-600">Tardíos: {currentTraining.attendance_stats.late}</span>
                  <span className="text-blue-600">Excusados: {currentTraining.attendance_stats.excused}</span>
                </div>
                <div className="mt-1 text-xs text-gray-500">
                  Total: {currentTraining.attendance_stats.total_players} jugadores
                </div>
              </dd>
            </div>
          )}
        </dl>

        {/* Objetivos */}
        {currentTraining.objectives && (
          <div className="mt-6">
            <dt className="text-sm font-medium text-gray-500 mb-2">Objetivos</dt>
            <dd className="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
              {currentTraining.objectives}
            </dd>
          </div>
        )}

        {/* Actividades */}
        {currentTraining.activities && (
          <div className="mt-6">
            <dt className="text-sm font-medium text-gray-500 mb-2">Actividades Planificadas</dt>
            <dd className="text-sm text-gray-900 bg-gray-50 p-3 rounded-md whitespace-pre-wrap">
              {currentTraining.activities}
            </dd>
          </div>
        )}

        {/* Timestamps */}
        <div className="mt-6 pt-6 border-t border-gray-200">
          <dl className="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2">
            <div>
              <dt className="text-xs font-medium text-gray-500">Creado</dt>
              <dd className="text-xs text-gray-900">
                {new Date(currentTraining.created_at).toLocaleString('es-ES')}
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium text-gray-500">Última actualización</dt>
              <dd className="text-xs text-gray-900">
                {new Date(currentTraining.updated_at).toLocaleString('es-ES')}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      {/* Actions */}
      <div className="px-4 py-4 sm:px-6 bg-gray-50 border-t border-gray-200 flex justify-between">
        <div>
          {onBack && (
            <button
              onClick={onBack}
              className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              ← Volver
            </button>
          )}
        </div>
        <div className="flex space-x-3">
          {onEdit && (
            <button
              onClick={handleEdit}
              className="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Editar
            </button>
          )}
          {onDelete && (
            <button
              onClick={handleDelete}
              className="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              Eliminar
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default TrainingDetail;