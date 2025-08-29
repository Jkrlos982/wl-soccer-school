import React, { useState, useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import { createTraining, updateTraining, fetchCategories } from '../../../store/sportsSlice';
import { Training, CreateTrainingData, UpdateTrainingData, TrainingType } from '../../../types/sports';

interface TrainingFormProps {
  training?: Training;
  onSubmit?: (training: Training) => void;
  onCancel?: () => void;
  isLoading?: boolean;
}

const TrainingForm: React.FC<TrainingFormProps> = ({
  training,
  onSubmit,
  onCancel,
  isLoading = false
}) => {
  const dispatch = useAppDispatch();
  const { categories, isLoading: categoriesLoading } = useAppSelector(
    (state: any) => state.sports
  );

  const [formData, setFormData] = useState({
    category_id: '',
    date: '',
    start_time: '',
    end_time: '',
    location: '',
    type: 'practice' as TrainingType,
    objectives: '',
    activities: '',
    coach_id: ''
  });

  const [errors, setErrors] = useState<{ [key: string]: string }>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    // Fetch categories if not loaded
    if (!categories?.data || categories.data.length === 0) {
      dispatch(fetchCategories());
    }
  }, [dispatch, categories]);

  useEffect(() => {
    if (training) {
      setFormData({
        category_id: training.category_id,
        date: training.date,
        start_time: training.start_time,
        end_time: training.end_time,
        location: training.location,
        type: training.type,
        objectives: training.objectives || '',
        activities: training.activities || '',
        coach_id: training.coach_id || ''
      });
    }
  }, [training]);

  const validateForm = (): boolean => {
    const newErrors: { [key: string]: string } = {};

    if (!formData.category_id) {
      newErrors.category_id = 'La categoría es requerida';
    }

    if (!formData.date) {
      newErrors.date = 'La fecha es requerida';
    }

    if (!formData.start_time) {
      newErrors.start_time = 'La hora de inicio es requerida';
    }

    if (!formData.end_time) {
      newErrors.end_time = 'La hora de fin es requerida';
    }

    if (formData.start_time && formData.end_time && formData.start_time >= formData.end_time) {
      newErrors.end_time = 'La hora de fin debe ser posterior a la hora de inicio';
    }

    if (!formData.location.trim()) {
      newErrors.location = 'La ubicación es requerida';
    }

    if (!formData.type) {
      newErrors.type = 'El tipo de entrenamiento es requerido';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);
    
    try {
      let result;
      
      if (training?.id) {
        // Update existing training
        const updateData: UpdateTrainingData = {
          category_id: formData.category_id,
          date: formData.date,
          start_time: formData.start_time,
          end_time: formData.end_time,
          location: formData.location,
          type: formData.type,
          objectives: formData.objectives || undefined,
          activities: formData.activities || undefined,
          coach_id: formData.coach_id || undefined
        };
        result = await dispatch(updateTraining({ id: training.id, data: updateData })).unwrap();
      } else {
        // Create new training
        const createData: CreateTrainingData = {
          category_id: formData.category_id,
          date: formData.date,
          start_time: formData.start_time,
          end_time: formData.end_time,
          location: formData.location,
          type: formData.type,
          objectives: formData.objectives || undefined,
          activities: formData.activities || undefined,
          coach_id: formData.coach_id || undefined
        };
        result = await dispatch(createTraining(createData)).unwrap();
      }
      
      if (onSubmit) {
        onSubmit(result);
      }
    } catch (error: any) {
      console.error('Error saving training:', error);
      if (error.errors) {
        setErrors(error.errors);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const trainingTypes: { value: TrainingType; label: string }[] = [
    { value: 'practice', label: 'Práctica' },
    { value: 'match', label: 'Partido' },
    { value: 'friendly', label: 'Amistoso' },
    { value: 'tournament', label: 'Torneo' },
    { value: 'physical', label: 'Físico' },
    { value: 'tactical', label: 'Táctico' },
    { value: 'technical', label: 'Técnico' }
  ];

  return (
    <div className="bg-white shadow rounded-lg">
      <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 className="text-lg leading-6 font-medium text-gray-900">
          {training ? 'Editar Entrenamiento' : 'Nuevo Entrenamiento'}
        </h3>
        <p className="mt-1 max-w-2xl text-sm text-gray-500">
          {training ? 'Modifica los datos del entrenamiento' : 'Completa la información para crear un nuevo entrenamiento'}
        </p>
      </div>

      <form onSubmit={handleSubmit} className="px-4 py-5 sm:px-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Categoría */}
          <div>
            <label htmlFor="category_id" className="block text-sm font-medium text-gray-700 mb-1">
              Categoría *
            </label>
            <select
              id="category_id"
              name="category_id"
              value={formData.category_id}
              onChange={handleInputChange}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.category_id ? 'border-red-300' : 'border-gray-300'
              }`}
              disabled={categoriesLoading}
            >
              <option value="">Selecciona una categoría</option>
              {categories?.data?.map((category: any) => (
                <option key={category.id} value={category.id}>
                  {category.name} ({category.age_group})
                </option>
              ))}
            </select>
            {errors.category_id && (
              <p className="mt-1 text-sm text-red-600">{errors.category_id}</p>
            )}
          </div>

          {/* Fecha */}
          <div>
            <label htmlFor="date" className="block text-sm font-medium text-gray-700 mb-1">
              Fecha *
            </label>
            <input
              type="date"
              id="date"
              name="date"
              value={formData.date}
              onChange={handleInputChange}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.date ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.date && (
              <p className="mt-1 text-sm text-red-600">{errors.date}</p>
            )}
          </div>

          {/* Hora de inicio */}
          <div>
            <label htmlFor="start_time" className="block text-sm font-medium text-gray-700 mb-1">
              Hora de Inicio *
            </label>
            <input
              type="time"
              id="start_time"
              name="start_time"
              value={formData.start_time}
              onChange={handleInputChange}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.start_time ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.start_time && (
              <p className="mt-1 text-sm text-red-600">{errors.start_time}</p>
            )}
          </div>

          {/* Hora de fin */}
          <div>
            <label htmlFor="end_time" className="block text-sm font-medium text-gray-700 mb-1">
              Hora de Fin *
            </label>
            <input
              type="time"
              id="end_time"
              name="end_time"
              value={formData.end_time}
              onChange={handleInputChange}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.end_time ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.end_time && (
              <p className="mt-1 text-sm text-red-600">{errors.end_time}</p>
            )}
          </div>

          {/* Ubicación */}
          <div>
            <label htmlFor="location" className="block text-sm font-medium text-gray-700 mb-1">
              Ubicación *
            </label>
            <input
              type="text"
              id="location"
              name="location"
              value={formData.location}
              onChange={handleInputChange}
              placeholder="Ej: Cancha Principal, Gimnasio, etc."
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.location ? 'border-red-300' : 'border-gray-300'
              }`}
            />
            {errors.location && (
              <p className="mt-1 text-sm text-red-600">{errors.location}</p>
            )}
          </div>

          {/* Tipo */}
          <div>
            <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-1">
              Tipo de Entrenamiento *
            </label>
            <select
              id="type"
              name="type"
              value={formData.type}
              onChange={handleInputChange}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.type ? 'border-red-300' : 'border-gray-300'
              }`}
            >
              {trainingTypes.map((type) => (
                <option key={type.value} value={type.value}>
                  {type.label}
                </option>
              ))}
            </select>
            {errors.type && (
              <p className="mt-1 text-sm text-red-600">{errors.type}</p>
            )}
          </div>

          {/* Coach ID */}
          <div>
            <label htmlFor="coach_id" className="block text-sm font-medium text-gray-700 mb-1">
              ID del Entrenador
            </label>
            <input
              type="text"
              id="coach_id"
              name="coach_id"
              value={formData.coach_id}
              onChange={handleInputChange}
              placeholder="ID del entrenador (opcional)"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        {/* Objetivos */}
        <div className="mt-6">
          <label htmlFor="objectives" className="block text-sm font-medium text-gray-700 mb-1">
            Objetivos del Entrenamiento
          </label>
          <textarea
            id="objectives"
            name="objectives"
            rows={3}
            value={formData.objectives}
            onChange={handleInputChange}
            placeholder="Describe los objetivos específicos de este entrenamiento..."
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        {/* Actividades */}
        <div className="mt-6">
          <label htmlFor="activities" className="block text-sm font-medium text-gray-700 mb-1">
            Actividades Planificadas
          </label>
          <textarea
            id="activities"
            name="activities"
            rows={4}
            value={formData.activities}
            onChange={handleInputChange}
            placeholder="Detalla las actividades y ejercicios planificados..."
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        {/* Buttons */}
        <div className="mt-8 flex justify-end space-x-3">
          {onCancel && (
            <button
              type="button"
              onClick={onCancel}
              className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              disabled={isSubmitting}
            >
              Cancelar
            </button>
          )}
          <button
            type="submit"
            disabled={isSubmitting || isLoading}
            className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSubmitting ? (
              <div className="flex items-center">
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                {training ? 'Actualizando...' : 'Creando...'}
              </div>
            ) : (
              training ? 'Actualizar Entrenamiento' : 'Crear Entrenamiento'
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default TrainingForm;