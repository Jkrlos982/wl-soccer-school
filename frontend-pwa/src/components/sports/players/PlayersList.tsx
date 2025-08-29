import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { AppDispatch } from '../../../store';
import { fetchPlayers, deletePlayer } from '../../../store/sportsSlice';
import { Player, PlayerFilters } from '../../../types/sports';
// import { formatDate } from '../../../utils/dateUtils';

// Temporary formatDate function
const formatDate = (dateString: string): string => {
  if (!dateString) return 'No disponible';
  try {
    return new Date(dateString).toLocaleDateString('es-ES');
  } catch {
    return 'Fecha inválida';
  }
};

interface PlayersListProps {
  onEdit?: (player: Player) => void;
  onView?: (player: Player) => void;
  onAdd?: () => void;
}

const PlayersList: React.FC<PlayersListProps> = ({ onEdit, onView, onAdd }) => {
  const dispatch = useDispatch<AppDispatch>();
  const { 
    playersData, 
    playersLoading: loading, 
    playersError: error 
  } = useSelector((state: any) => state.sports);

  const [filters, setFilters] = useState<PlayerFilters>({
    search: '',
    category_id: '',
    is_active: undefined,
    position: '',
    page: 1,
    per_page: 10
  });

  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    dispatch(fetchPlayers(filters));
  }, [dispatch, filters]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setFilters(prev => ({ ...prev, search: searchTerm, page: 1 }));
  };

  const handleFilterChange = (key: keyof PlayerFilters, value: any) => {
    setFilters(prev => ({ ...prev, [key]: value, page: 1 }));
  };

  const handlePageChange = (page: number) => {
    setFilters(prev => ({ ...prev, page }));
  };

  const handleDelete = async (id: string) => {
    if (window.confirm('¿Estás seguro de que quieres eliminar este jugador?')) {
      try {
        await dispatch(deletePlayer(id)).unwrap();
        console.log('Jugador eliminado exitosamente');
        dispatch(fetchPlayers(filters));
      } catch (error) {
        console.error('Error al eliminar jugador:', error);
      }
    }
  };

  const renderPagination = () => {
    if (!playersData?.meta) return null;

    const { current_page, last_page, per_page, total } = playersData.meta;
    const pages = [];
    
    for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) {
      pages.push(i);
    }

    return (
      <div className="flex items-center justify-between mt-6">
        <div className="text-sm text-gray-700">
          Mostrando {((current_page - 1) * per_page) + 1} a {Math.min(current_page * per_page, total)} de {total} jugadores
        </div>
        <div className="flex space-x-1">
          <button
            onClick={() => handlePageChange(current_page - 1)}
            disabled={current_page === 1}
            className="px-3 py-1 text-sm border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            Anterior
          </button>
          {pages.map(page => (
            <button
              key={page}
              onClick={() => handlePageChange(page)}
              className={`px-3 py-1 text-sm border rounded ${
                page === current_page 
                  ? 'bg-blue-500 text-white border-blue-500' 
                  : 'hover:bg-gray-50'
              }`}
            >
              {page}
            </button>
          ))}
          <button
            onClick={() => handlePageChange(current_page + 1)}
            disabled={current_page === last_page}
            className="px-3 py-1 text-sm border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            Siguiente
          </button>
        </div>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-gray-900">Jugadores</h2>
        {onAdd && (
          <button
            onClick={onAdd}
            className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
          >
            <span>+</span>
            <span>Nuevo Jugador</span>
          </button>
        )}
      </div>

      {/* Search and Filters */}
      <div className="bg-white p-6 rounded-lg shadow">
        <form onSubmit={handleSearch} className="mb-4">
          <div className="flex space-x-4">
            <div className="flex-1">
              <input
                type="text"
                placeholder="Buscar jugadores..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              type="submit"
              className="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md"
            >
              Buscar
            </button>
          </div>
        </form>

        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Estado
            </label>
            <select
              value={filters.is_active === undefined ? '' : filters.is_active ? 'true' : 'false'}
              onChange={(e) => handleFilterChange('is_active', e.target.value === '' ? undefined : e.target.value === 'true')}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todos</option>
              <option value="true">Activos</option>
              <option value="false">Inactivos</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Posición
            </label>
            <select
              value={filters.position || ''}
              onChange={(e) => handleFilterChange('position', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todas</option>
              <option value="goalkeeper">Portero</option>
              <option value="defender">Defensa</option>
              <option value="midfielder">Centrocampista</option>
              <option value="forward">Delantero</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Por página
            </label>
            <select
              value={filters.per_page}
              onChange={(e) => handleFilterChange('per_page', parseInt(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value={10}>10</option>
              <option value={25}>25</option>
              <option value={50}>50</option>
            </select>
          </div>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      {/* Players Table */}
      <div className="bg-white shadow rounded-lg overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Jugador
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Categoría
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Posición
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Edad
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estado
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fecha Registro
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {playersData?.data?.map((player: Player) => (
              <tr key={player.id} className="hover:bg-gray-50">
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 h-10 w-10">
                      {player.photo_url ? (
                        <img
                          className="h-10 w-10 rounded-full object-cover"
                          src={player.photo_url}
                          alt={`${player.first_name} ${player.last_name}`}
                        />
                      ) : (
                        <div className="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                          <span className="text-sm font-medium text-gray-700">
                            {player.first_name.charAt(0)}{player.last_name.charAt(0)}
                          </span>
                        </div>
                      )}
                    </div>
                    <div className="ml-4">
                      <div className="text-sm font-medium text-gray-900">
                        {player.first_name} {player.last_name}
                      </div>
                      <div className="text-sm text-gray-500">
                        {player.email}
                      </div>
                    </div>
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {player.category?.name || 'Sin categoría'}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {player.position || 'No especificada'}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {player.age || 'No especificada'}
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                    player.is_active 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-red-100 text-red-800'
                  }`}>
                    {player.is_active ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {formatDate(player.created_at)}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div className="flex justify-end space-x-2">
                    {onView && (
                      <button
                        onClick={() => onView(player)}
                        className="text-blue-600 hover:text-blue-900"
                      >
                        Ver
                      </button>
                    )}
                    {onEdit && (
                      <button
                        onClick={() => onEdit(player)}
                        className="text-indigo-600 hover:text-indigo-900"
                      >
                        Editar
                      </button>
                    )}
                    <button
                      onClick={() => handleDelete(player.id)}
                      className="text-red-600 hover:text-red-900"
                    >
                      Eliminar
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {playersData?.data?.length === 0 && (
          <div className="text-center py-12">
            <div className="text-gray-500">
              <span className="text-4xl mb-4 block">👥</span>
              <h3 className="text-lg font-medium mb-2">No hay jugadores</h3>
              <p className="text-sm">No se encontraron jugadores con los filtros aplicados.</p>
            </div>
          </div>
        )}
      </div>

      {/* Pagination */}
      {renderPagination()}
    </div>
  );
};

export default PlayersList;