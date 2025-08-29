import React, { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchCategories, deleteCategory, clearError } from '../../../store/sportsSlice';
import { Category } from '../../../types/sports';

interface CategoryDetailProps {
  category?: Category;
  onEdit?: (category: Category) => void;
  onDelete?: (categoryId: string) => void;
  showActions?: boolean;
}

const CategoryDetail: React.FC<CategoryDetailProps> = ({
  category: propCategory,
  onEdit,
  onDelete,
  showActions = true,
}) => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const dispatch = useAppDispatch();
  
  const { categories, categoriesLoading: loading, error } = useAppSelector(state => state.sports);
  
  // Use prop category or find from store
  const category = propCategory || categories.data.find(cat => cat.id === id);
  
  useEffect(() => {
    if (!propCategory && id && categories.data.length === 0) {
      dispatch(fetchCategories({ page: 1, per_page: 100 }));
    }
  }, [dispatch, id, propCategory, categories.data.length]);
  
  useEffect(() => {
    if (error) {
      console.error('Category detail error:', error);
      dispatch(clearError());
    }
  }, [error, dispatch]);
  
  const handleEdit = () => {
    if (category && onEdit) {
      onEdit(category);
    } else if (category) {
      navigate(`/sports/categories/${category.id}/edit`);
    }
  };
  
  const handleDelete = async () => {
    if (!category) return;
    
    const confirmed = window.confirm(
      `¿Estás seguro de que deseas eliminar la categoría "${category.name}"?`
    );
    
    if (confirmed) {
      try {
        if (onDelete) {
          onDelete(category.id);
        } else {
          await dispatch(deleteCategory(category.id)).unwrap();
          navigate('/sports/categories');
        }
      } catch (error) {
        console.error('Error deleting category:', error);
      }
    }
  };
  
  const formatTrainingDays = (days: string[]) => {
    const dayNames: { [key: string]: string } = {
      monday: 'Lunes',
      tuesday: 'Martes',
      wednesday: 'Miércoles',
      thursday: 'Jueves',
      friday: 'Viernes',
      saturday: 'Sábado',
      sunday: 'Domingo',
    };
    
    return days.map(day => dayNames[day] || day).join(', ');
  };
  
  const formatDate = (dateString: string | null) => {
    if (!dateString) return 'No especificada';
    return new Date(dateString).toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };
  
  const formatTime = (timeString: string | null) => {
    if (!timeString) return 'No especificado';
    return timeString;
  };
  
  if (loading) {
    return (
      <div className="flex justify-center items-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }
  
  if (!category) {
    return (
      <div className="text-center py-8">
        <h3 className="text-lg font-medium text-gray-900 mb-2">Categoría no encontrada</h3>
        <p className="text-gray-500 mb-4">La categoría que buscas no existe o ha sido eliminada.</p>
        <button
          onClick={() => navigate('/sports/categories')}
          className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
        >
          Volver a Categorías
        </button>
      </div>
    );
  }
  
  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200">
      {/* Header */}
      <div className="px-6 py-4 border-b border-gray-200">
        <div className="flex justify-between items-start">
          <div>
            <h2 className="text-xl font-semibold text-gray-900">{category.name}</h2>
            <div className="flex items-center mt-1">
              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                category.is_active
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
              }`}>
                {category.is_active ? 'Activa' : 'Inactiva'}
              </span>
              <span className="ml-2 text-sm text-gray-500">
                ID: {category.id}
              </span>
            </div>
          </div>
          
          {showActions && (
            <div className="flex space-x-2">
              <button
                onClick={handleEdit}
                className="bg-blue-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-blue-700 transition-colors"
              >
                Editar
              </button>
              <button
                onClick={handleDelete}
                className="bg-red-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-red-700 transition-colors"
              >
                Eliminar
              </button>
            </div>
          )}
        </div>
      </div>
      
      {/* Content */}
      <div className="px-6 py-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Basic Information */}
          <div className="space-y-4">
            <h3 className="text-lg font-medium text-gray-900 mb-3">Información Básica</h3>
            
            <div>
              <label className="block text-sm font-medium text-gray-700">Descripción</label>
              <p className="mt-1 text-sm text-gray-900">
                {category.description || 'Sin descripción'}
              </p>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700">Grupo de Edad</label>
              <p className="mt-1 text-sm text-gray-900">{category.age_group}</p>
            </div>
            
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">Edad Mínima</label>
                <p className="mt-1 text-sm text-gray-900">{category.min_age} años</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Edad Máxima</label>
                <p className="mt-1 text-sm text-gray-900">{category.max_age} años</p>
              </div>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700">Género</label>
              <p className="mt-1 text-sm text-gray-900">
                {category.gender === 'mixed' ? 'Mixto' : 
                 category.gender === 'male' ? 'Masculino' : 'Femenino'}
              </p>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700">Máximo de Jugadores</label>
              <p className="mt-1 text-sm text-gray-900">{category.max_players} jugadores</p>
            </div>
          </div>
          
          {/* Training Information */}
          <div className="space-y-4">
            <h3 className="text-lg font-medium text-gray-900 mb-3">Información de Entrenamiento</h3>
            
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">Inicio de Temporada</label>
                <p className="mt-1 text-sm text-gray-900">{formatDate(category.season_start || null)}</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Fin de Temporada</label>
                <p className="mt-1 text-sm text-gray-900">{formatDate(category.season_end || null)}</p>
              </div>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700">Días de Entrenamiento</label>
              <p className="mt-1 text-sm text-gray-900">
                {category.training_days && category.training_days.length > 0
                  ? formatTrainingDays(category.training_days)
                  : 'No especificados'
                }
              </p>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700">Horario de Entrenamiento</label>
              <p className="mt-1 text-sm text-gray-900">{formatTime(category.training_time || null)}</p>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700">Entrenador</label>
              <p className="mt-1 text-sm text-gray-900">
                {category.coach_id || 'No asignado'}
              </p>
            </div>
          </div>
        </div>
        
        {/* Statistics */}
        <div className="mt-6 pt-6 border-t border-gray-200">
          <h3 className="text-lg font-medium text-gray-900 mb-3">Estadísticas</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-blue-50 p-4 rounded-lg">
              <div className="text-2xl font-bold text-blue-600">{category.players_count || 0}</div>
              <div className="text-sm text-blue-600">Jugadores Registrados</div>
            </div>
            <div className="bg-green-50 p-4 rounded-lg">
              <div className="text-2xl font-bold text-green-600">{category.trainings_count || 0}</div>
              <div className="text-sm text-green-600">Entrenamientos Programados</div>
            </div>
            <div className="bg-purple-50 p-4 rounded-lg">
              <div className="text-2xl font-bold text-purple-600">
                {category.max_players - (category.players_count || 0)}
              </div>
              <div className="text-sm text-purple-600">Cupos Disponibles</div>
            </div>
          </div>
        </div>
        
        {/* Timestamps */}
        <div className="mt-6 pt-6 border-t border-gray-200">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
            <div>
              <span className="font-medium">Creado:</span> {formatDate(category.created_at)}
            </div>
            <div>
              <span className="font-medium">Actualizado:</span> {formatDate(category.updated_at)}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CategoryDetail;