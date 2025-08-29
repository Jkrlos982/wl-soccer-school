import React, { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store';
import { fetchPlayer as fetchPlayerById, deletePlayer } from '../../../store/sportsSlice';
import { Player } from '../../../types/sports';

interface PlayerDetailProps {
  playerId?: string;
  onEdit?: (player: Player) => void;
  onDelete?: (playerId: string) => void;
}

const PlayerDetail: React.FC<PlayerDetailProps> = ({ 
  playerId: propPlayerId, 
  onEdit, 
  onDelete 
}) => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const dispatch = useAppDispatch();
  
  const playerId = propPlayerId || id;
  const { currentPlayer, playersLoading: loading, playersError: error } = useAppSelector(
    (state: any) => state.sports
  );

  useEffect(() => {
    if (playerId) {
      dispatch(fetchPlayerById(playerId));
    }
  }, [dispatch, playerId]);

  const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) return 'No especificado';
    return new Date(dateString).toLocaleDateString('es-ES');
  };

  const formatGender = (gender: string): string => {
    return gender === 'male' ? 'Masculino' : 'Femenino';
  };

  const formatDocumentType = (type: string): string => {
    switch (type) {
      case 'dni': return 'DNI';
      case 'passport': return 'Pasaporte';
      case 'other': return 'Otro';
      default: return type;
    }
  };

  const formatDominantFoot = (foot: string): string => {
    switch (foot) {
      case 'left': return 'Izquierdo';
      case 'right': return 'Derecho';
      case 'both': return 'Ambos';
      default: return foot;
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

  const handleEdit = () => {
    if (currentPlayer && onEdit) {
      onEdit(currentPlayer);
    } else if (currentPlayer) {
      navigate(`/sports/players/${currentPlayer.id}/edit`);
    }
  };

  const handleDelete = async () => {
    if (!currentPlayer) return;
    
    if (window.confirm('¿Estás seguro de que deseas eliminar este jugador?')) {
      try {
        await dispatch(deletePlayer(currentPlayer.id)).unwrap();
        if (onDelete) {
          onDelete(currentPlayer.id);
        } else {
          navigate('/sports/players');
        }
        console.log('Jugador eliminado exitosamente');
      } catch (error) {
        console.error('Error al eliminar jugador:', error);
      }
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-md p-4">
        <div className="flex">
          <span className="text-red-400 mr-2">⚠</span>
          <div>
            <h3 className="text-sm font-medium text-red-800">Error</h3>
            <p className="mt-1 text-sm text-red-700">
              {typeof error === 'string' ? error : 'Error al cargar el jugador'}
            </p>
          </div>
        </div>
      </div>
    );
  }

  if (!currentPlayer) {
    return (
      <div className="text-center py-8">
        <p className="text-gray-500">Jugador no encontrado</p>
      </div>
    );
  }

  return (
    <div className="bg-white shadow-lg rounded-lg overflow-hidden">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
        <div className="flex justify-between items-start">
          <div>
            <h1 className="text-2xl font-bold text-white">
              {currentPlayer.first_name} {currentPlayer.last_name}
            </h1>
            <p className="text-blue-100 mt-1">
              {currentPlayer.position || 'Sin posición'} • {formatGender(currentPlayer.gender)}
            </p>
          </div>
          <div className="flex space-x-2">
            <button
              onClick={handleEdit}
              className="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-3 py-1 rounded-md text-sm font-medium transition-colors"
            >
              Editar
            </button>
            <button
              onClick={handleDelete}
              className="bg-red-500 bg-opacity-80 hover:bg-opacity-100 text-white px-3 py-1 rounded-md text-sm font-medium transition-colors"
            >
              Eliminar
            </button>
          </div>
        </div>
      </div>

      <div className="p-6">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Información Personal */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Información Personal</h2>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-gray-600">Fecha de Nacimiento:</span>
                <span className="font-medium">
                  {formatDate(currentPlayer.date_of_birth)} 
                  ({calculateAge(currentPlayer.date_of_birth)} años)
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Documento:</span>
                <span className="font-medium">
                  {formatDocumentType(currentPlayer.document_type)} - {currentPlayer.document_number}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Teléfono:</span>
                <span className="font-medium">{currentPlayer.phone || 'No especificado'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Email:</span>
                <span className="font-medium">{currentPlayer.email || 'No especificado'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Dirección:</span>
                <span className="font-medium">{currentPlayer.address || 'No especificada'}</span>
              </div>
            </div>
          </div>

          {/* Información Deportiva */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Información Deportiva</h2>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-gray-600">Posición:</span>
                <span className="font-medium">{currentPlayer.position || 'No especificada'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Número de Camiseta:</span>
                <span className="font-medium">{currentPlayer.jersey_number || 'No asignado'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Pie Dominante:</span>
                <span className="font-medium">{formatDominantFoot(currentPlayer.dominant_foot || 'right')}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Altura:</span>
                <span className="font-medium">
                  {currentPlayer.height ? `${currentPlayer.height} cm` : 'No especificada'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Peso:</span>
                <span className="font-medium">
                  {currentPlayer.weight ? `${currentPlayer.weight} kg` : 'No especificado'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Estado:</span>
                <span className={`font-medium ${
                  currentPlayer.is_active ? 'text-green-600' : 'text-red-600'
                }`}>
                  {currentPlayer.is_active ? 'Activo' : 'Inactivo'}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Contacto de Emergencia */}
        {(currentPlayer.emergency_contact_name || currentPlayer.emergency_contact_phone) && (
          <div className="mt-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Contacto de Emergencia</h2>
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="flex justify-between">
                  <span className="text-gray-600">Nombre:</span>
                  <span className="font-medium">{currentPlayer.emergency_contact_name || 'No especificado'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Teléfono:</span>
                  <span className="font-medium">{currentPlayer.emergency_contact_phone || 'No especificado'}</span>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Condiciones Médicas */}
        {currentPlayer.medical_conditions && (
          <div className="mt-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Condiciones Médicas</h2>
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <p className="text-gray-700">{currentPlayer.medical_conditions}</p>
            </div>
          </div>
        )}

        {/* Timestamps */}
        <div className="mt-8 pt-6 border-t border-gray-200">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
            <div>
              <span className="font-medium">Creado:</span> {formatDate(currentPlayer.created_at)}
            </div>
            <div>
              <span className="font-medium">Actualizado:</span> {formatDate(currentPlayer.updated_at)}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PlayerDetail;