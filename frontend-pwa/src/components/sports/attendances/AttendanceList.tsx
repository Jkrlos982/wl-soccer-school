import React, { useEffect, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchAttendances } from '../../../store/sportsSlice';
import { Attendance, AttendanceFilters, AttendanceStatus } from '../../../types/sports';

interface AttendanceListProps {
  trainingId?: string;
  playerId?: string;
  onEdit?: (attendance: Attendance) => void;
  onView?: (attendance: Attendance) => void;
  showFilters?: boolean;
}

const AttendanceList: React.FC<AttendanceListProps> = ({
  trainingId,
  playerId,
  onEdit,
  onView,
  showFilters = true
}) => {
  const dispatch = useAppDispatch();
  const { attendances, isLoading } = useAppSelector((state: any) => ({
    attendances: state.sports.attendances,
    isLoading: state.sports.isLoading
  }));

  const [filters, setFilters] = useState<AttendanceFilters>({
    ...(trainingId && { training_id: trainingId }),
    ...(playerId && { player_id: playerId }),
    page: 1,
    per_page: 20
  });

  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<AttendanceStatus | ''>('');
  const [dateFromFilter, setDateFromFilter] = useState('');
  const [dateToFilter, setDateToFilter] = useState('');

  useEffect(() => {
    const updatedFilters = {
      ...filters,
      ...(searchTerm && { search: searchTerm }),
      ...(statusFilter && { status: statusFilter }),
      ...(dateFromFilter && { date_from: dateFromFilter }),
      ...(dateToFilter && { date_to: dateToFilter })
    };
    
    dispatch(fetchAttendances(updatedFilters));
  }, [dispatch, filters, searchTerm, statusFilter, dateFromFilter, dateToFilter]);

  const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('es-ES');
  };

  const formatTime = (timeString: string): string => {
    if (!timeString) return '-';
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusColor = (status: AttendanceStatus): string => {
    switch (status) {
      case 'present':
        return 'text-green-600 bg-green-100';
      case 'absent':
        return 'text-red-600 bg-red-100';
      case 'late':
        return 'text-yellow-600 bg-yellow-100';
      case 'excused':
        return 'text-blue-600 bg-blue-100';
      case 'pending':
        return 'text-gray-600 bg-gray-100';
      default:
        return 'text-gray-600 bg-gray-100';
    }
  };

  const getStatusLabel = (status: AttendanceStatus): string => {
    switch (status) {
      case 'present':
        return 'Presente';
      case 'absent':
        return 'Ausente';
      case 'late':
        return 'Tardanza';
      case 'excused':
        return 'Justificado';
      case 'pending':
        return 'Pendiente';
      default:
        return status;
    }
  };

  const handlePageChange = (newPage: number) => {
    setFilters(prev => ({ ...prev, page: newPage }));
  };

  const clearFilters = () => {
    setSearchTerm('');
    setStatusFilter('');
    setDateFromFilter('');
    setDateToFilter('');
    setFilters({
      ...(trainingId && { training_id: trainingId }),
      ...(playerId && { player_id: playerId }),
      page: 1,
      per_page: 20
    });
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Filtros */}
      {showFilters && (
        <div className="bg-white p-4 rounded-lg shadow-sm border">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Buscar
              </label>
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Buscar por jugador..."
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Estado
              </label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value as AttendanceStatus | '')}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Todos los estados</option>
                <option value="present">Presente</option>
                <option value="absent">Ausente</option>
                <option value="late">Tardanza</option>
                <option value="excused">Justificado</option>
                <option value="pending">Pendiente</option>
              </select>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Fecha desde
              </label>
              <input
                type="date"
                value={dateFromFilter}
                onChange={(e) => setDateFromFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Fecha hasta
              </label>
              <input
                type="date"
                value={dateToFilter}
                onChange={(e) => setDateToFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
          
          <div className="mt-4 flex justify-end">
            <button
              onClick={clearFilters}
              className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
            >
              Limpiar filtros
            </button>
          </div>
        </div>
      )}

      {/* Lista de asistencias */}
      <div className="bg-white rounded-lg shadow-sm border overflow-hidden">
        {attendances.length === 0 ? (
          <div className="text-center py-8 text-gray-500">
            No se encontraron registros de asistencia
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Jugador
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Entrenamiento
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fecha
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Hora llegada
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Notas
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {attendances.map((attendance: any) => (
                  <tr key={attendance.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {attendance.player?.full_name || 'N/A'}
                          </div>
                          {attendance.player?.jersey_number && (
                            <div className="text-sm text-gray-500">
                              #{attendance.player.jersey_number}
                            </div>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900">
                        {attendance.training?.type || 'N/A'}
                      </div>
                      <div className="text-sm text-gray-500">
                        {attendance.training?.location || 'N/A'}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {formatDate(attendance.date)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(attendance.status)}`}>
                        {getStatusLabel(attendance.status)}
                      </span>
                      {attendance.is_late && attendance.late_duration_minutes && (
                        <div className="text-xs text-yellow-600 mt-1">
                          {attendance.late_duration_minutes} min tarde
                        </div>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {formatTime(attendance.arrival_time || '')}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      <div className="max-w-xs truncate" title={attendance.notes || ''}>
                        {attendance.notes || '-'}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex justify-end space-x-2">
                        {onView && (
                          <button
                            onClick={() => onView(attendance)}
                            className="text-blue-600 hover:text-blue-900 transition-colors"
                          >
                            Ver
                          </button>
                        )}
                        {onEdit && (
                          <button
                            onClick={() => onEdit(attendance)}
                            className="text-indigo-600 hover:text-indigo-900 transition-colors"
                          >
                            Editar
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Paginación */}
      {attendances.length > 0 && (
        <div className="flex items-center justify-between">
          <div className="text-sm text-gray-700">
            Mostrando {((filters.page || 1) - 1) * (filters.per_page || 20) + 1} a{' '}
            {Math.min((filters.page || 1) * (filters.per_page || 20), attendances.length)} de{' '}
            {attendances.length} resultados
          </div>
          <div className="flex space-x-2">
            <button
              onClick={() => handlePageChange((filters.page || 1) - 1)}
              disabled={(filters.page || 1) <= 1}
              className="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Anterior
            </button>
            <span className="px-3 py-2 text-sm border border-gray-300 rounded-md bg-blue-50">
              {filters.page || 1}
            </span>
            <button
              onClick={() => handlePageChange((filters.page || 1) + 1)}
              disabled={attendances.length < (filters.per_page || 20)}
              className="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default AttendanceList;