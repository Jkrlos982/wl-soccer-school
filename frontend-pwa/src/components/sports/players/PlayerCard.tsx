import React from 'react';
import { Player } from '../../../types/sports';

interface PlayerCardProps {
  player: Player;
  onView?: (player: Player) => void;
  onEdit?: (player: Player) => void;
  onDelete?: (playerId: string) => void;
}

const PlayerCard: React.FC<PlayerCardProps> = ({ 
  player, 
  onView, 
  onEdit, 
  onDelete 
}) => {
  const formatGender = (gender: string): string => {
    return gender === 'male' ? 'M' : 'F';
  };

  const formatDominantFoot = (foot: string): string => {
    switch (foot) {
      case 'left': return 'I';
      case 'right': return 'D';
      case 'both': return 'A';
      default: return '-';
    }
  };

  const calculateAge = (birthDate: string): number => {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    
    return age;
  };

  const getInitials = (firstName: string, lastName: string): string => {
    return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
  };

  const handleView = () => {
    if (onView) {
      onView(player);
    }
  };

  const handleEdit = () => {
    if (onEdit) {
      onEdit(player);
    }
  };

  const handleDelete = () => {
    if (onDelete && player.id && window.confirm('¿Estás seguro de que deseas eliminar este jugador?')) {
      onDelete(player.id);
    }
  };

  return (
    <div className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden">
      {/* Header with player photo/initials */}
      <div className="bg-gradient-to-r from-blue-500 to-blue-600 p-4">
        <div className="flex items-center space-x-3">
          <div className="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <span className="text-white font-bold text-lg">
              {getInitials(player.first_name, player.last_name)}
            </span>
          </div>
          <div className="flex-1">
            <h3 className="text-white font-semibold text-lg">
              {player.first_name} {player.last_name}
            </h3>
            <p className="text-blue-100 text-sm">
              {player.position || 'Sin posición'}
            </p>
          </div>
          {player.jersey_number && (
            <div className="bg-white bg-opacity-20 rounded-full w-10 h-10 flex items-center justify-center">
              <span className="text-white font-bold text-sm">
                #{player.jersey_number}
              </span>
            </div>
          )}
        </div>
      </div>

      {/* Player Info */}
      <div className="p-4">
        <div className="grid grid-cols-2 gap-4 mb-4">
          <div className="text-center">
            <p className="text-gray-500 text-xs uppercase tracking-wide">Edad</p>
            <p className="text-lg font-semibold text-gray-900">
              {calculateAge(player.date_of_birth)}
            </p>
          </div>
          <div className="text-center">
            <p className="text-gray-500 text-xs uppercase tracking-wide">Género</p>
            <p className="text-lg font-semibold text-gray-900">
              {formatGender(player.gender)}
            </p>
          </div>
        </div>

        {/* Additional Info */}
        <div className="space-y-2 mb-4">
          {player.height && (
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">Altura:</span>
              <span className="font-medium">{player.height} cm</span>
            </div>
          )}
          {player.weight && (
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">Peso:</span>
              <span className="font-medium">{player.weight} kg</span>
            </div>
          )}
          <div className="flex justify-between text-sm">
            <span className="text-gray-600">Pie dominante:</span>
            <span className="font-medium">{formatDominantFoot(player.dominant_foot || 'right')}</span>
          </div>
        </div>

        {/* Status */}
        <div className="flex items-center justify-between mb-4">
          <span className="text-sm text-gray-600">Estado:</span>
          <span className={`px-2 py-1 rounded-full text-xs font-medium ${
            player.is_active 
              ? 'bg-green-100 text-green-800' 
              : 'bg-red-100 text-red-800'
          }`}>
            {player.is_active ? 'Activo' : 'Inactivo'}
          </span>
        </div>

        {/* Contact Info */}
        {(player.phone || player.email) && (
          <div className="border-t pt-3 mb-4">
            {player.phone && (
              <div className="flex items-center text-sm text-gray-600 mb-1">
                <span className="mr-2">📞</span>
                <span>{player.phone}</span>
              </div>
            )}
            {player.email && (
              <div className="flex items-center text-sm text-gray-600">
                <span className="mr-2">✉️</span>
                <span className="truncate">{player.email}</span>
              </div>
            )}
          </div>
        )}

        {/* Medical Alert */}
        {player.medical_conditions && (
          <div className="bg-yellow-50 border border-yellow-200 rounded-md p-2 mb-4">
            <div className="flex items-center">
              <span className="text-yellow-600 mr-2">⚠️</span>
              <span className="text-xs text-yellow-800 font-medium">Condiciones médicas</span>
            </div>
          </div>
        )}

        {/* Actions */}
        <div className="flex space-x-2">
          {onView && (
            <button
              onClick={handleView}
              className="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 px-3 rounded-md text-sm font-medium transition-colors"
            >
              Ver
            </button>
          )}
          {onEdit && (
            <button
              onClick={handleEdit}
              className="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 py-2 px-3 rounded-md text-sm font-medium transition-colors"
            >
              Editar
            </button>
          )}
          {onDelete && (
            <button
              onClick={handleDelete}
              className="bg-red-50 hover:bg-red-100 text-red-700 py-2 px-3 rounded-md text-sm font-medium transition-colors"
            >
              🗑️
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default PlayerCard;