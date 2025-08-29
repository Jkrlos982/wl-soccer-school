import React from 'react';
import { Category } from '../../../types/sports';

interface CategoryCardProps {
  category: Category;
  onEdit?: (category: Category) => void;
  onDelete?: (categoryId: string) => void;
  onView?: (category: Category) => void;
  showActions?: boolean;
  className?: string;
}

const CategoryCard: React.FC<CategoryCardProps> = ({
  category,
  onEdit,
  onDelete,
  onView,
  showActions = true,
  className = '',
}) => {
  const handleEdit = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onEdit) {
      onEdit(category);
    }
  };
  
  const handleDelete = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onDelete) {
      const confirmed = window.confirm(
        `¿Estás seguro de que deseas eliminar la categoría "${category.name}"?`
      );
      if (confirmed) {
        onDelete(category.id.toString());
      }
    }
  };
  
  const handleView = () => {
    if (onView) {
      onView(category);
    }
  };
  
  const formatTrainingDays = (days: string[]) => {
    const dayAbbr: { [key: string]: string } = {
      monday: 'L',
      tuesday: 'M',
      wednesday: 'X',
      thursday: 'J',
      friday: 'V',
      saturday: 'S',
      sunday: 'D',
    };
    
    return days.map(day => dayAbbr[day] || day.charAt(0).toUpperCase()).join(', ');
  };
  
  const getGenderLabel = (gender: string) => {
    switch (gender) {
      case 'male': return 'M';
      case 'female': return 'F';
      case 'mixed': return 'Mixto';
      default: return gender;
    }
  };
  
  const getAvailableSpots = () => {
    const playersCount = category.players_count || 0;
    return category.max_players - playersCount;
  };
  
  const getSpotColor = () => {
    const available = getAvailableSpots();
    const percentage = (available / category.max_players) * 100;
    
    if (percentage > 50) return 'text-green-600';
    if (percentage > 20) return 'text-yellow-600';
    return 'text-red-600';
  };
  
  return (
    <div 
      className={`bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer ${className}`}
      onClick={handleView}
    >
      {/* Header */}
      <div className="p-4 border-b border-gray-100">
        <div className="flex justify-between items-start">
          <div className="flex-1">
            <h3 className="text-lg font-semibold text-gray-900 mb-1">
              {category.name}
            </h3>
            <p className="text-sm text-gray-600 line-clamp-2">
              {category.description || 'Sin descripción'}
            </p>
          </div>
          
          <div className="flex items-center space-x-2 ml-4">
            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
              category.is_active
                ? 'bg-green-100 text-green-800'
                : 'bg-red-100 text-red-800'
            }`}>
              {category.is_active ? 'Activa' : 'Inactiva'}
            </span>
            
            {showActions && (
              <div className="flex space-x-1">
                <button
                  onClick={handleEdit}
                  className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                  title="Editar categoría"
                >
                  <span className="text-sm">✏️</span>
                </button>
                <button
                  onClick={handleDelete}
                  className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                  title="Eliminar categoría"
                >
                  <span className="text-sm">🗑️</span>
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
      
      {/* Content */}
      <div className="p-4">
        {/* Age and Gender Info */}
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center space-x-4">
            <div className="text-sm">
              <span className="font-medium text-gray-700">Edad:</span>
              <span className="ml-1 text-gray-900">
                {category.min_age}-{category.max_age} años
              </span>
            </div>
            <div className="text-sm">
              <span className="font-medium text-gray-700">Género:</span>
              <span className="ml-1 text-gray-900">
                {getGenderLabel(category.gender)}
              </span>
            </div>
          </div>
          
          <div className="text-sm text-gray-600">
            {category.age_group}
          </div>
        </div>
        
        {/* Training Info */}
        <div className="mb-3">
          <div className="flex items-center justify-between text-sm">
            <div>
              <span className="font-medium text-gray-700">Entrenamientos:</span>
              <span className="ml-1 text-gray-900">
                {category.training_days && category.training_days.length > 0
                  ? formatTrainingDays(category.training_days)
                  : 'No definidos'
                }
              </span>
            </div>
            {category.training_time && (
              <div className="text-gray-600">
                {category.training_time}
              </div>
            )}
          </div>
        </div>
        
        {/* Players Info */}
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <div className="text-sm">
              <span className="font-medium text-gray-700">Jugadores:</span>
              <span className="ml-1 text-gray-900">
                {category.players_count || 0}/{category.max_players}
              </span>
            </div>
            <div className={`text-sm font-medium ${getSpotColor()}`}>
              {getAvailableSpots()} cupos disponibles
            </div>
          </div>
          
          {category.trainings_count !== undefined && (
            <div className="text-sm text-gray-600">
              {category.trainings_count} entrenamientos
            </div>
          )}
        </div>
        
        {/* Progress Bar */}
        <div className="mt-3">
          <div className="flex justify-between text-xs text-gray-500 mb-1">
            <span>Ocupación</span>
            <span>
              {Math.round(((category.players_count || 0) / category.max_players) * 100)}%
            </span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div 
              className={`h-2 rounded-full transition-all duration-300 ${
                ((category.players_count || 0) / category.max_players) > 0.8
                  ? 'bg-red-500'
                  : ((category.players_count || 0) / category.max_players) > 0.6
                  ? 'bg-yellow-500'
                  : 'bg-green-500'
              }`}
              style={{ 
                width: `${Math.min(((category.players_count || 0) / category.max_players) * 100, 100)}%` 
              }}
            ></div>
          </div>
        </div>
        
        {/* Coach Info */}
        {category.coach_id && (
          <div className="mt-3 pt-3 border-t border-gray-100">
            <div className="text-sm">
              <span className="font-medium text-gray-700">Entrenador:</span>
              <span className="ml-1 text-gray-900">{category.coach_id}</span>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default CategoryCard;