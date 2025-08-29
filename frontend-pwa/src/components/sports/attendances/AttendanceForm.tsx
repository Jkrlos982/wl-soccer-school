import React, { useState, useEffect } from 'react';
import { useAppDispatch } from '../../../store';
import { updateAttendance } from '../../../store/sportsSlice';
import { Attendance, AttendanceStatus, UpdateAttendanceData } from '../../../types/sports';

interface AttendanceFormProps {
  attendance?: Attendance;
  onSubmit?: (data: UpdateAttendanceData) => void;
  onCancel?: () => void;
  isLoading?: boolean;
}

const AttendanceForm: React.FC<AttendanceFormProps> = ({
  attendance,
  onSubmit,
  onCancel,
  isLoading = false
}) => {
  const dispatch = useAppDispatch();
  
  const [formData, setFormData] = useState<UpdateAttendanceData>({
    status: attendance?.status || 'pending',
    arrival_time: attendance?.arrival_time || '',
    notes: attendance?.notes || ''
  });

  const [errors, setErrors] = useState<{ [key: string]: string }>({});

  useEffect(() => {
    if (attendance) {
      setFormData({
        status: attendance.status,
        arrival_time: attendance.arrival_time || '',
        notes: attendance.notes || ''
      });
    }
  }, [attendance]);

  const validateForm = (): boolean => {
    const newErrors: { [key: string]: string } = {};

    if (!formData.status) {
      newErrors.status = 'El estado es requerido';
    }

    if (formData.status === 'present' || formData.status === 'late') {
      if (!formData.arrival_time) {
        newErrors.arrival_time = 'La hora de llegada es requerida para este estado';
      }
    }

    if (formData.status === 'absent' && !formData.notes) {
      newErrors.notes = 'Se requiere una nota para justificar la ausencia';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleInputChange = (field: keyof UpdateAttendanceData, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    
    // Clear error when user starts typing
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    try {
      if (onSubmit) {
        onSubmit(formData);
      } else if (attendance?.id) {
        await dispatch(updateAttendance({ id: attendance.id, data: formData }));
      }
    } catch (error) {
      console.error('Error updating attendance:', error);
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

  const shouldShowArrivalTime = formData.status === 'present' || formData.status === 'late';
  const shouldRequireNotes = formData.status === 'absent' || formData.status === 'excused';

  return (
    <div className="bg-white rounded-lg shadow-sm border p-6">
      <div className="mb-6">
        <h3 className="text-lg font-semibold text-gray-900">
          {attendance ? 'Editar Asistencia' : 'Registrar Asistencia'}
        </h3>
        {attendance && (
          <div className="mt-2 text-sm text-gray-600">
            <p><strong>Jugador:</strong> {attendance.player?.full_name}</p>
            <p><strong>Entrenamiento:</strong> {attendance.training?.type} - {attendance.training?.location}</p>
            <p><strong>Fecha:</strong> {new Date(attendance.date).toLocaleDateString('es-ES')}</p>
          </div>
        )}
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Estado */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Estado de Asistencia *
          </label>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
            {(['pending', 'present', 'late', 'absent', 'excused'] as AttendanceStatus[]).map((status) => (
              <label key={status} className="flex items-center">
                <input
                  type="radio"
                  name="status"
                  value={status}
                  checked={formData.status === status}
                  onChange={(e) => handleInputChange('status', e.target.value as AttendanceStatus)}
                  className="mr-2 text-blue-600 focus:ring-blue-500"
                />
                <span className="text-sm text-gray-700">
                  {getStatusLabel(status)}
                </span>
              </label>
            ))}
          </div>
          {errors.status && (
            <p className="mt-1 text-sm text-red-600">{errors.status}</p>
          )}
        </div>

        {/* Hora de llegada */}
        {shouldShowArrivalTime && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Hora de Llegada {formData.status === 'present' || formData.status === 'late' ? '*' : ''}
            </label>
            <input
              type="time"
              value={formData.arrival_time}
              onChange={(e) => handleInputChange('arrival_time', e.target.value)}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.arrival_time ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.arrival_time && (
              <p className="mt-1 text-sm text-red-600">{errors.arrival_time}</p>
            )}
          </div>
        )}

        {/* Notas */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Notas {shouldRequireNotes ? '*' : ''}
            {shouldRequireNotes && (
              <span className="text-xs text-gray-500 ml-1">
                (Requerido para ausencias y justificaciones)
              </span>
            )}
          </label>
          <textarea
            value={formData.notes}
            onChange={(e) => handleInputChange('notes', e.target.value)}
            rows={3}
            placeholder="Agregar observaciones, motivo de ausencia, etc."
            className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.notes ? 'border-red-300' : 'border-gray-300'
            }`}
          />
          {errors.notes && (
            <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
          )}
        </div>

        {/* Información adicional */}
        {formData.status === 'late' && (
          <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-yellow-800">
                  Tardanza registrada
                </h3>
                <p className="mt-1 text-sm text-yellow-700">
                  Se calculará automáticamente el tiempo de retraso basado en la hora de inicio del entrenamiento.
                </p>
              </div>
            </div>
          </div>
        )}

        {formData.status === 'absent' && (
          <div className="bg-red-50 border border-red-200 rounded-md p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-red-800">
                  Ausencia registrada
                </h3>
                <p className="mt-1 text-sm text-red-700">
                  Por favor, proporciona una nota explicando el motivo de la ausencia.
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Botones */}
        <div className="flex justify-end space-x-3 pt-6 border-t">
          {onCancel && (
            <button
              type="button"
              onClick={onCancel}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancelar
            </button>
          )}
          <button
            type="submit"
            disabled={isLoading}
            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Guardando...' : 'Guardar Asistencia'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default AttendanceForm;