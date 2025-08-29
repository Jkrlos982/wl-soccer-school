import React, { useEffect, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchTrainings } from '../../../store/sportsSlice';
import { Training, TrainingFilters } from '../../../types/sports';

interface TrainingsListProps {
  onView?: (training: Training) => void;
  onEdit?: (training: Training) => void;
  onDelete?: (trainingId: string) => void;
  onCreateNew?: () => void;
}

const TrainingsList: React.FC<TrainingsListProps> = ({
  onView,
  onEdit,
  onDelete,
  onCreateNew
}) => {
  const dispatch = useAppDispatch();
  const { trainings: trainingsData, isLoading: loading, error } = useAppSelector(
    (state: any) => state.sports
  );

  const [filters, setFilters] = useState<TrainingFilters>({
    category_id: '',
    date_from: '',
    date_to: '',
    status: undefined,
    page: 1,
    per_page: 10
  });

  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    dispatch(fetchTrainings(filters));
  }, [dispatch, filters]);

  const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('es-ES');
  };

  const formatTime = (timeString: string): string => {
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatStatus = (status: string): { label: string; color: string } => {
    switch (status) {
      case 'scheduled':
        return { label: 'Programado', color: 'bg-blue-100 text-blue-800' };
      case 'in_progress':
        return { label: 'En Progreso', color: 'bg-yellow-100 text-yellow-800' };
      case 'completed':
        return { label: 'Completado', color: 'bg-green-100 text-green-800' };
      case 'cancelled':
        return { label: 'Cancelado', color: 'bg-red-100 text-red-800' };
      default:
        return { label: status, color: 'bg-gray-100 text-gray-800' };
    }
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setFilters(prev => ({ ...prev, page: 1 }));
  };

  const handleFilterChange = (key: keyof TrainingFilters, value: string | number) => {
    setFilters(prev => ({ ...prev, [key]: value, page: 1 }));
  };

  const handlePageChange = (newPage: number) => {
    setFilters(prev => ({ ...prev, page: newPage }));
  };

  const handleView = (training: Training) => {
    if (onView) {
      onView(training);
    }
  };

  const handleEdit = (training: Training) => {
    if (onEdit) {
      onEdit(training);
    }
  };

  const handleDelete = async (trainingId: string) => {
    if (onDelete && window.confirm('¿Estás seguro de que deseas eliminar este entrenamiento?')) {
      onDelete(trainingId);
    }
  };

  const renderPagination = () => {
    if (!trainingsData?.pagination) return null;

    const { current_page, last_page, total } = trainingsData.pagination;
    const pages = [];
    const maxVisiblePages = 5;
    
    let startPage = Math.max(1, current_page - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(last_page, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
      pages.push(i);
    }

    return (
      <div className="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
        <div className="flex justify-between flex-1 sm:hidden">
          <button
            onClick={() => handlePageChange(current_page - 1)}
            disabled={current_page === 1}
            className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Anterior
          </button>
          <button
            onClick={() => handlePageChange(current_page + 1)}
            disabled={current_page === last_page}
            className="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Siguiente
          </button>
        </div>
        <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
          <div>
            <p className="text-sm text-gray-700">
              Mostrando <span className="font-medium">{(current_page - 1) * (filters.per_page || 10) + 1}</span> a{' '}
              <span className="font-medium">
                {Math.min(current_page * (filters.per_page || 10), total)}
              </span>{' '}
              de <span className="font-medium">{total}</span> entrenamientos
            </p>
          </div>
          <div>
            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                onClick={() => handlePageChange(current_page - 1)}
                disabled={current_page === 1}
                className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span>‹</span>
              </button>
              {pages.map((page) => (
                <button
                  key={page}
                  onClick={() => handlePageChange(page)}
                  className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                    page === current_page
                      ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                  }`}
                >
                  {page}
                </button>
              ))}
              <button
                onClick={() => handlePageChange(current_page + 1)}
                disabled={current_page === last_page}
                className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span>›</span>
              </button>
            </nav>
          </div>
        </div>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="bg-white shadow rounded-lg">
      {/* Header */}
      <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div className="flex justify-between items-center">
          <div>
            <h3 className="text-lg leading-6 font-medium text-gray-900">
              Entrenamientos
            </h3>
            <p className="mt-1 max-w-2xl text-sm text-gray-500">
              Gestiona los entrenamientos de las categorías
            </p>
          </div>
          {onCreateNew && (
            <button
              onClick={onCreateNew}
              className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <span className="mr-2">+</span>
              Nuevo Entrenamiento
            </button>
          )}
        </div>
      </div>

      {/* Filters */}
      <div className="px-4 py-4 border-b border-gray-200">
        <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Fecha Desde
            </label>
            <input
              type="date"
              value={filters.date_from}
              onChange={(e) => handleFilterChange('date_from', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Fecha Hasta
            </label>
            <input
              type="date"
              value={filters.date_to}
              onChange={(e) => handleFilterChange('date_to', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Estado
            </label>
            <select
              value={filters.status}
              onChange={(e) => handleFilterChange('status', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todos los estados</option>
              <option value="scheduled">Programado</option>
              <option value="in_progress">En Progreso</option>
              <option value="completed">Completado</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Por página
            </label>
            <select
              value={filters.per_page}
              onChange={(e) => handleFilterChange('per_page', Number(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value={10}>10</option>
              <option value={25}>25</option>
              <option value={50}>50</option>
            </select>
          </div>
        </form>
      </div>

      {/* Error State */}
      {error && (
        <div className="px-4 py-4">
          <div className="bg-red-50 border border-red-200 rounded-md p-4">
            <div className="flex">
              <span className="text-red-400 mr-2">⚠</span>
              <div>
                <h3 className="text-sm font-medium text-red-800">Error</h3>
                <p className="mt-1 text-sm text-red-700">
                  {typeof error === 'string' ? error : 'Error al cargar entrenamientos'}
                </p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Trainings List */}
      <div className="overflow-hidden">
        {trainingsData?.data && trainingsData.data.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fecha y Hora
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Categoría
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tipo
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Duración
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Asistencia
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {trainingsData.data.map((training: Training) => {
                  const statusInfo = formatStatus(training.status);
                  return (
                    <tr key={training.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {formatDate(training.date)}
                          </div>
                          <div className="text-sm text-gray-500">
                            {formatTime(training.start_time)} - {formatTime(training.end_time)}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          {training.category?.name || 'Sin categoría'}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">
                          {training.type || 'Regular'}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusInfo.color}`}>
                          {statusInfo.label}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {training.duration_minutes} min
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {training.attendance_stats?.present || 0} / {training.category?.max_players || 0}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div className="flex justify-end space-x-2">
                          {onView && (
                            <button
                              onClick={() => handleView(training)}
                              className="text-blue-600 hover:text-blue-900"
                            >
                              Ver
                            </button>
                          )}
                          {onEdit && (
                            <button
                              onClick={() => handleEdit(training)}
                              className="text-indigo-600 hover:text-indigo-900"
                            >
                              Editar
                            </button>
                          )}
                          {onDelete && (
                            <button
                              onClick={() => training.id && handleDelete(training.id)}
                              className="text-red-600 hover:text-red-900"
                            >
                              Eliminar
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center py-12">
            <span className="text-4xl mb-4 block">🏃‍♂️</span>
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              No hay entrenamientos
            </h3>
            <p className="text-gray-500 mb-4">
              No se encontraron entrenamientos con los filtros aplicados.
            </p>
            {onCreateNew && (
              <button
                onClick={onCreateNew}
                className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
              >
                <span className="mr-2">+</span>
                Crear Primer Entrenamiento
              </button>
            )}
          </div>
        )}
      </div>

      {/* Pagination */}
      {trainingsData?.data && trainingsData.data.length > 0 && renderPagination()}
    </div>
  );
};

export default TrainingsList;