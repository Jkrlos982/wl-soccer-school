import React, { useState, useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import {
  createCategory,
  updateCategory,
  clearError,
} from '../../../store/sportsSlice';
import { Category, CreateCategoryData, UpdateCategoryData } from '../../../types';

interface CategoryFormProps {
  category?: Category;
  onSuccess?: (category: Category) => void;
  onCancel?: () => void;
  isModal?: boolean;
}

const CategoryForm: React.FC<CategoryFormProps> = ({
  category,
  onSuccess,
  onCancel,
  isModal = false,
}) => {
  const dispatch = useAppDispatch();
  const { isLoading, error } = useAppSelector((state) => state.sports);

  const [formData, setFormData] = useState<CreateCategoryData>({
    name: '',
    description: '',
    age_group: '',
    min_age: 5,
    max_age: 18,
    gender: 'mixed',
    max_players: 25,
    season_start: '',
    season_end: '',
    training_days: [],
    training_time: '',
    coach_id: '',
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (category) {
      setFormData({
        name: category.name,
        description: category.description || '',
        age_group: category.age_group,
        min_age: category.min_age,
        max_age: category.max_age,
        gender: category.gender,
        max_players: category.max_players,
        season_start: category.season_start || '',
        season_end: category.season_end || '',
        training_days: category.training_days || [],
        training_time: category.training_time || '',
        coach_id: category.coach_id || '',
      });
    }
  }, [category]);

  useEffect(() => {
    if (error) {
      console.error('Form error:', error);
      dispatch(clearError());
    }
  }, [error, dispatch]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value, type } = e.target;
    const checked = (e.target as HTMLInputElement).checked;
    
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));

    // Clear field error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.name.trim()) {
      newErrors.name = 'El nombre es requerido';
    } else if (formData.name.length < 2) {
      newErrors.name = 'El nombre debe tener al menos 2 caracteres';
    } else if (formData.name.length > 100) {
      newErrors.name = 'El nombre no puede exceder 100 caracteres';
    }

    if (formData.description && formData.description.length > 500) {
      newErrors.description = 'La descripción no puede exceder 500 caracteres';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    try {
      let result: Category;
      
      if (category) {
        // Update existing category
        const updateData: UpdateCategoryData = {
          ...formData,
          is_active: category.is_active, // Keep current active status
        };
        result = await dispatch(updateCategory({ id: category.id, data: updateData })).unwrap();
      } else {
        // Create new category
        result = await dispatch(createCategory(formData)).unwrap();
      }

      console.log(`Categoría ${category ? 'actualizada' : 'creada'} exitosamente`);
      onSuccess?.(result);
    } catch (error: any) {
      console.error('Error al guardar la categoría:', error);
    }
  };

  const handleReset = () => {
    if (category) {
      setFormData({
        name: category.name,
        description: category.description || '',
        age_group: category.age_group,
        min_age: category.min_age,
        max_age: category.max_age,
        gender: category.gender,
        max_players: category.max_players,
        season_start: category.season_start || '',
        season_end: category.season_end || '',
        training_days: category.training_days || [],
        training_time: category.training_time || '',
        coach_id: category.coach_id || '',
      });
    } else {
      setFormData({
        name: '',
        description: '',
        age_group: '',
        min_age: 5,
        max_age: 18,
        gender: 'mixed',
        max_players: 25,
        season_start: '',
        season_end: '',
        training_days: [],
        training_time: '',
        coach_id: '',
      });
    }
    setErrors({});
  };

  const formContent = (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Name Field */}
      <div>
        <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
          Nombre *
        </label>
        <input
          type="text"
          id="name"
          name="name"
          value={formData.name}
          onChange={handleChange}
          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
            errors.name ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="Ingresa el nombre de la categoría"
          maxLength={100}
        />
        {errors.name && (
          <p className="mt-1 text-sm text-red-600">{errors.name}</p>
        )}
      </div>

      {/* Description Field */}
      <div>
        <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
          Descripción
        </label>
        <textarea
          id="description"
          name="description"
          value={formData.description}
          onChange={handleChange}
          rows={3}
          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
            errors.description ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="Descripción opcional de la categoría"
          maxLength={500}
        />
        {errors.description && (
          <p className="mt-1 text-sm text-red-600">{errors.description}</p>
        )}
        <p className="mt-1 text-sm text-gray-500">
          {(formData.description || '').length}/500 caracteres
        </p>
      </div>

      {/* Age Group */}
      <div>
        <label htmlFor="age_group" className="block text-sm font-medium text-gray-700 mb-1">
          Grupo de Edad *
        </label>
        <input
          type="text"
          id="age_group"
          name="age_group"
          value={formData.age_group}
          onChange={handleChange}
          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
            errors.age_group ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="Ej: Sub-12, Juvenil, Senior"
        />
        {errors.age_group && (
          <p className="mt-1 text-sm text-red-600">{errors.age_group}</p>
        )}
      </div>

      {/* Age Range */}
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label htmlFor="min_age" className="block text-sm font-medium text-gray-700 mb-1">
            Edad Mínima *
          </label>
          <input
            type="number"
            id="min_age"
            name="min_age"
            value={formData.min_age}
            onChange={handleChange}
            min="3"
            max="50"
            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
              errors.min_age ? 'border-red-500' : 'border-gray-300'
            }`}
          />
          {errors.min_age && (
            <p className="mt-1 text-sm text-red-600">{errors.min_age}</p>
          )}
        </div>
        <div>
          <label htmlFor="max_age" className="block text-sm font-medium text-gray-700 mb-1">
            Edad Máxima *
          </label>
          <input
            type="number"
            id="max_age"
            name="max_age"
            value={formData.max_age}
            onChange={handleChange}
            min="3"
            max="50"
            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
              errors.max_age ? 'border-red-500' : 'border-gray-300'
            }`}
          />
          {errors.max_age && (
            <p className="mt-1 text-sm text-red-600">{errors.max_age}</p>
          )}
        </div>
      </div>

      {/* Gender */}
      <div>
        <label htmlFor="gender" className="block text-sm font-medium text-gray-700 mb-1">
          Género *
        </label>
        <select
          id="gender"
          name="gender"
          value={formData.gender}
          onChange={handleChange}
          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
            errors.gender ? 'border-red-500' : 'border-gray-300'
          }`}
        >
          <option value="mixed">Mixto</option>
          <option value="male">Masculino</option>
          <option value="female">Femenino</option>
        </select>
        {errors.gender && (
          <p className="mt-1 text-sm text-red-600">{errors.gender}</p>
        )}
      </div>

      {/* Max Players */}
      <div>
        <label htmlFor="max_players" className="block text-sm font-medium text-gray-700 mb-1">
          Máximo de Jugadores *
        </label>
        <input
          type="number"
          id="max_players"
          name="max_players"
          value={formData.max_players}
          onChange={handleChange}
          min="5"
          max="50"
          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
            errors.max_players ? 'border-red-500' : 'border-gray-300'
          }`}
        />
        {errors.max_players && (
          <p className="mt-1 text-sm text-red-600">{errors.max_players}</p>
        )}
      </div>

      {/* Season Dates */}
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label htmlFor="season_start" className="block text-sm font-medium text-gray-700 mb-1">
            Inicio de Temporada
          </label>
          <input
            type="date"
            id="season_start"
            name="season_start"
            value={formData.season_start}
            onChange={handleChange}
            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
              errors.season_start ? 'border-red-500' : 'border-gray-300'
            }`}
          />
          {errors.season_start && (
            <p className="mt-1 text-sm text-red-600">{errors.season_start}</p>
          )}
        </div>
        <div>
          <label htmlFor="season_end" className="block text-sm font-medium text-gray-700 mb-1">
            Fin de Temporada
          </label>
          <input
            type="date"
            id="season_end"
            name="season_end"
            value={formData.season_end}
            onChange={handleChange}
            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
              errors.season_end ? 'border-red-500' : 'border-gray-300'
            }`}
          />
          {errors.season_end && (
            <p className="mt-1 text-sm text-red-600">{errors.season_end}</p>
          )}
        </div>
      </div>

      {/* Training Days */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Días de Entrenamiento *
        </label>
        <div className="grid grid-cols-7 gap-2">
          {[
            { value: 'monday', label: 'L' },
            { value: 'tuesday', label: 'M' },
            { value: 'wednesday', label: 'X' },
            { value: 'thursday', label: 'J' },
            { value: 'friday', label: 'V' },
            { value: 'saturday', label: 'S' },
            { value: 'sunday', label: 'D' },
          ].map((day) => (
            <label key={day.value} className="flex flex-col items-center">
              <input
                type="checkbox"
                name="training_days"
                value={day.value}
                checked={formData.training_days.includes(day.value)}
                onChange={(e) => {
                  const { value, checked } = e.target;
                  setFormData(prev => ({
                    ...prev,
                    training_days: checked
                      ? [...prev.training_days, value]
                      : prev.training_days.filter(d => d !== value)
                  }));
                }}
                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span className="text-xs mt-1">{day.label}</span>
            </label>
          ))}
        </div>
        {errors.training_days && (
          <p className="mt-1 text-sm text-red-600">{errors.training_days}</p>
        )}
      </div>

      {/* Training Time */}
      <div>
        <label htmlFor="training_time" className="block text-sm font-medium text-gray-700 mb-1">
          Horario de Entrenamiento
        </label>
        <input
          type="time"
          id="training_time"
          name="training_time"
          value={formData.training_time}
          onChange={handleChange}
          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
            errors.training_time ? 'border-red-500' : 'border-gray-300'
          }`}
        />
        {errors.training_time && (
          <p className="mt-1 text-sm text-red-600">{errors.training_time}</p>
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
          onChange={handleChange}
          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
            errors.coach_id ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="ID del entrenador asignado"
        />
        {errors.coach_id && (
          <p className="mt-1 text-sm text-red-600">{errors.coach_id}</p>
        )}
      </div>

      {/* Form Actions */}
      <div className="flex justify-end space-x-3 pt-4">
        <button
          type="button"
          onClick={handleReset}
          className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          disabled={isLoading}
        >
          Restablecer
        </button>
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            disabled={isLoading}
          >
            Cancelar
          </button>
        )}
        <button
          type="submit"
          disabled={isLoading}
          className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center"
        >
          {isLoading && (
            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
          )}
          {category ? 'Actualizar' : 'Crear'} Categoría
        </button>
      </div>
    </form>
  );

  if (isModal) {
    return (
      <div className="bg-white rounded-lg p-6">
        <div className="mb-6">
          <h2 className="text-xl font-semibold text-gray-900">
            {category ? 'Editar' : 'Nueva'} Categoría
          </h2>
          <p className="mt-1 text-sm text-gray-600">
            {category 
              ? 'Modifica los datos de la categoría deportiva'
              : 'Completa la información para crear una nueva categoría deportiva'
            }
          </p>
        </div>
        {formContent}
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-sm">
      <div className="p-6 border-b border-gray-200">
        <h2 className="text-xl font-semibold text-gray-900">
          {category ? 'Editar' : 'Nueva'} Categoría
        </h2>
        <p className="mt-1 text-sm text-gray-600">
          {category 
            ? 'Modifica los datos de la categoría deportiva'
            : 'Completa la información para crear una nueva categoría deportiva'
          }
        </p>
      </div>
      <div className="p-6">
        {formContent}
      </div>
    </div>
  );
};

export default CategoryForm;