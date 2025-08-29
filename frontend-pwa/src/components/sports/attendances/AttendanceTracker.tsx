import React, { useState, useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchAttendances, bulkUpdateAttendance } from '../../../store/sportsSlice';
import { Training, Attendance, AttendanceStatus, BulkAttendanceData } from '../../../types/sports';

interface AttendanceTrackerProps {
  training: Training;
  onComplete?: () => void;
  onCancel?: () => void;
}

const AttendanceTracker: React.FC<AttendanceTrackerProps> = ({
  training,
  onComplete,
  onCancel
}) => {
  const dispatch = useAppDispatch();
  const { attendances, isLoading } = useAppSelector((state: any) => ({
    attendances: state.sports.attendances,
    isLoading: state.sports.isLoading
  }));

  const [attendanceData, setAttendanceData] = useState<{ [key: string]: {
    status: AttendanceStatus;
    arrival_time?: string;
    notes?: string;
  } }>({});

  const [currentTime, setCurrentTime] = useState(new Date());
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<AttendanceStatus | ''>('');

  useEffect(() => {
    // Fetch attendances for this training
    dispatch(fetchAttendances({ training_id: training.id }));
    
    // Update current time every minute
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 60000);

    return () => clearInterval(timer);
  }, [dispatch, training.id]);

  useEffect(() => {
    // Initialize attendance data from fetched attendances
    const initialData: { [key: string]: {
      status: AttendanceStatus;
      arrival_time?: string;
      notes?: string;
    } } = {};
    
    attendances.forEach((attendance: any) => {
      initialData[attendance.id] = {
        status: attendance.status,
        arrival_time: attendance.arrival_time || '',
        notes: attendance.notes || ''
      };
    });
    
    setAttendanceData(initialData);
  }, [attendances]);

  const formatTime = (date: Date): string => {
    return date.toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getTrainingStartTime = (): Date => {
    return new Date(`${training.date}T${training.start_time}`);
  };

  const isLate = (arrivalTime: string): boolean => {
    const arrival = new Date(`${training.date}T${arrivalTime}`);
    const start = getTrainingStartTime();
    return arrival > start;
  };

  const calculateLateDuration = (arrivalTime: string): number => {
    const arrival = new Date(`${training.date}T${arrivalTime}`);
    const start = getTrainingStartTime();
    return Math.max(0, Math.floor((arrival.getTime() - start.getTime()) / (1000 * 60)));
  };

  const updateAttendanceStatus = (attendanceId: string, status: AttendanceStatus, arrivalTime?: string) => {
    const currentTimeStr = formatTime(currentTime);
    
    setAttendanceData(prev => ({
      ...prev,
      [attendanceId]: {
        ...prev[attendanceId],
        status,
        arrival_time: status === 'present' || status === 'late' 
          ? (arrivalTime || currentTimeStr)
          : prev[attendanceId]?.arrival_time || '',
        notes: prev[attendanceId]?.notes || ''
      }
    }));
  };

  const updateAttendanceNotes = (attendanceId: string, notes: string) => {
    setAttendanceData(prev => ({
      ...prev,
      [attendanceId]: {
        ...prev[attendanceId],
        notes
      }
    }));
  };

  const markAsPresent = (attendanceId: string) => {
    const currentTimeStr = formatTime(currentTime);
    const status: AttendanceStatus = isLate(currentTimeStr) ? 'late' : 'present';
    updateAttendanceStatus(attendanceId, status, currentTimeStr);
  };

  const getStatusColor = (status: AttendanceStatus): string => {
    switch (status) {
      case 'present':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'absent':
        return 'bg-red-100 text-red-800 border-red-200';
      case 'late':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'excused':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'pending':
        return 'bg-gray-100 text-gray-800 border-gray-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
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

  const filteredAttendances = attendances.filter((attendance: any) => {
    const matchesSearch = !searchTerm || 
      attendance.player?.full_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      attendance.player?.jersey_number?.toString().includes(searchTerm);
    
    const matchesStatus = !statusFilter || 
      (attendanceData[attendance.id]?.status || attendance.status) === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  const getAttendanceStats = () => {
    const stats = {
      total: attendances.length,
      present: 0,
      absent: 0,
      late: 0,
      excused: 0,
      pending: 0
    };

    attendances.forEach((attendance: any) => {
      const status = attendanceData[attendance.id]?.status || attendance.status;
      stats[status as keyof typeof stats]++;
    });

    return stats;
  };

  const handleSaveAll = async () => {
    try {
      const bulkData: BulkAttendanceData = {
        attendances: Object.entries(attendanceData).map(([id, data]) => ({
          id,
          status: data.status,
          arrival_time: data.arrival_time,
          notes: data.notes
        }))
      };

      await dispatch(bulkUpdateAttendance(bulkData));
      
      if (onComplete) {
        onComplete();
      }
    } catch (error) {
      console.error('Error saving attendance:', error);
    }
  };

  const stats = getAttendanceStats();
  const attendanceRate = stats.total > 0 ? Math.round(((stats.present + stats.late) / stats.total) * 100) : 0;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <div className="flex justify-between items-start mb-4">
          <div>
            <h2 className="text-xl font-semibold text-gray-900">
              Control de Asistencia
            </h2>
            <div className="mt-2 text-sm text-gray-600">
              <p><strong>Entrenamiento:</strong> {training.type} - {training.location}</p>
              <p><strong>Fecha:</strong> {new Date(training.date).toLocaleDateString('es-ES')}</p>
              <p><strong>Horario:</strong> {training.start_time} - {training.end_time}</p>
              <p><strong>Hora actual:</strong> {formatTime(currentTime)}</p>
            </div>
          </div>
          <div className="text-right">
            <div className="text-2xl font-bold text-blue-600">{attendanceRate}%</div>
            <div className="text-sm text-gray-500">Asistencia</div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 md:grid-cols-6 gap-4">
          <div className="text-center">
            <div className="text-lg font-semibold text-gray-900">{stats.total}</div>
            <div className="text-xs text-gray-500">Total</div>
          </div>
          <div className="text-center">
            <div className="text-lg font-semibold text-green-600">{stats.present}</div>
            <div className="text-xs text-gray-500">Presentes</div>
          </div>
          <div className="text-center">
            <div className="text-lg font-semibold text-yellow-600">{stats.late}</div>
            <div className="text-xs text-gray-500">Tardanzas</div>
          </div>
          <div className="text-center">
            <div className="text-lg font-semibold text-red-600">{stats.absent}</div>
            <div className="text-xs text-gray-500">Ausentes</div>
          </div>
          <div className="text-center">
            <div className="text-lg font-semibold text-blue-600">{stats.excused}</div>
            <div className="text-xs text-gray-500">Justificados</div>
          </div>
          <div className="text-center">
            <div className="text-lg font-semibold text-gray-600">{stats.pending}</div>
            <div className="text-xs text-gray-500">Pendientes</div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border p-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Buscar jugador
            </label>
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Nombre o número de camiseta..."
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Filtrar por estado
            </label>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value as AttendanceStatus | '')}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todos</option>
              <option value="pending">Pendientes</option>
              <option value="present">Presentes</option>
              <option value="late">Tardanzas</option>
              <option value="absent">Ausentes</option>
              <option value="excused">Justificados</option>
            </select>
          </div>
        </div>
      </div>

      {/* Attendance List */}
      <div className="bg-white rounded-lg shadow-sm border">
        <div className="p-4 border-b">
          <h3 className="text-lg font-medium text-gray-900">
            Lista de Jugadores ({filteredAttendances.length})
          </h3>
        </div>
        
        <div className="divide-y divide-gray-200">
          {filteredAttendances.map((attendance: any) => {
            const currentStatus = attendanceData[attendance.id]?.status || attendance.status;
            const currentArrivalTime = attendanceData[attendance.id]?.arrival_time || attendance.arrival_time;
            const currentNotes = attendanceData[attendance.id]?.notes || attendance.notes;
            
            return (
              <div key={attendance.id} className="p-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <div className="flex-shrink-0">
                      {attendance.player?.jersey_number && (
                        <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                          <span className="text-sm font-semibold text-blue-800">
                            #{attendance.player.jersey_number}
                          </span>
                        </div>
                      )}
                    </div>
                    <div>
                      <div className="text-sm font-medium text-gray-900">
                        {attendance.player?.full_name}
                      </div>
                      <div className="text-sm text-gray-500">
                        {attendance.player?.position || 'Sin posición'}
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex items-center space-x-3">
                    {/* Status Badge */}
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full border ${getStatusColor(currentStatus)}`}>
                      {getStatusLabel(currentStatus)}
                    </span>
                    
                    {/* Arrival Time */}
                    {(currentStatus === 'present' || currentStatus === 'late') && currentArrivalTime && (
                      <span className="text-sm text-gray-600">
                        {currentArrivalTime}
                        {currentStatus === 'late' && (
                          <span className="text-yellow-600 ml-1">
                            (+{calculateLateDuration(currentArrivalTime)}min)
                          </span>
                        )}
                      </span>
                    )}
                    
                    {/* Quick Actions */}
                    <div className="flex space-x-1">
                      <button
                        onClick={() => markAsPresent(attendance.id)}
                        className="px-2 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors"
                      >
                        Presente
                      </button>
                      <button
                        onClick={() => updateAttendanceStatus(attendance.id, 'absent')}
                        className="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors"
                      >
                        Ausente
                      </button>
                      <button
                        onClick={() => updateAttendanceStatus(attendance.id, 'excused')}
                        className="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                      >
                        Justificar
                      </button>
                    </div>
                  </div>
                </div>
                
                {/* Notes */}
                {(currentStatus === 'absent' || currentStatus === 'excused' || currentNotes) && (
                  <div className="mt-3">
                    <textarea
                      value={currentNotes}
                      onChange={(e) => updateAttendanceNotes(attendance.id, e.target.value)}
                      placeholder="Agregar notas..."
                      rows={2}
                      className="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Actions */}
      <div className="flex justify-end space-x-3">
        {onCancel && (
          <button
            onClick={onCancel}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancelar
          </button>
        )}
        <button
          onClick={handleSaveAll}
          disabled={isLoading}
          className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isLoading ? 'Guardando...' : 'Guardar Asistencia'}
        </button>
      </div>
    </div>
  );
};

export default AttendanceTracker;