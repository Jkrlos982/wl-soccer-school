import React, { useEffect, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import {
  fetchCategories,
  deleteCategory,
  setCategoryFilters,
  clearError,
} from '../../../store/sportsSlice';
import { Category, CategoryFilters } from '../../../types';
// Using basic icons for now - can be replaced with react-icons later
const PlusIcon = () => <span>+</span>;
const EditIcon = () => <span>✏️</span>;
const TrashIcon = () => <span>🗑️</span>;
const SearchIcon = () => <span>🔍</span>;
const FilterIcon = () => <span>🔽</span>;

interface CategoriesListProps {
  onEdit?: (category: Category) => void;
  onAdd?: () => void;
  selectable?: boolean;
  onSelect?: (category: Category) => void;
  selectedId?: number;
}

const CategoriesList: React.FC<CategoriesListProps> = ({
  onEdit,
  onAdd,
  selectable = false,
  onSelect,
  selectedId,
}) => {
  const dispatch = useAppDispatch();
  const {
    categories,
    categoryFilters,
    isLoading,
    error,
  } = useAppSelector((state) => state.sports);

  const [showFilters, setShowFilters] = useState(false);
  const [localFilters, setLocalFilters] = useState<CategoryFilters>(categoryFilters);

  useEffect(() => {
    dispatch(fetchCategories(categoryFilters));
  }, [dispatch, categoryFilters]);

  useEffect(() => {
    if (error) {
      console.error('Sports error:', error);
      // toast.error(error.message); // TODO: Add toast notification
      dispatch(clearError());
    }
  }, [error, dispatch]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    dispatch(setCategoryFilters(localFilters));
  };

  const handleDelete = async (id: number) => {
    if (window.confirm('¿Estás seguro de que deseas eliminar esta categoría?')) {
      try {
        await dispatch(deleteCategory(id)).unwrap();
        console.log('Categoría eliminada exitosamente'); // TODO: Add toast notification
        dispatch(fetchCategories(categoryFilters));
      } catch (error: any) {
        console.error('Error al eliminar la categoría:', error); // TODO: Add toast notification
      }
    }
  };

  const handlePageChange = (page: number) => {
    const newFilters = { ...categoryFilters, page };
    dispatch(setCategoryFilters(newFilters));
  };

  const handleFilterChange = (key: keyof CategoryFilters, value: any) => {
    setLocalFilters(prev => ({ ...prev, [key]: value }));
  };

  const resetFilters = () => {
    const defaultFilters: CategoryFilters = {
      search: '',
      is_active: true,
      page: 1,
      per_page: 10,
    };
    setLocalFilters(defaultFilters);
    dispatch(setCategoryFilters(defaultFilters));
  };

  return (
    <div className="bg-white rounded-lg shadow-sm">
      {/* Header */}
      <div className="p-6 border-b border-gray-200">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-semibold text-gray-900">
            Categorías Deportivas
          </h2>
          {onAdd && (
            <button
              onClick={onAdd}
              className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              <PlusIcon />
              Nueva Categoría
            </button>
          )}
        </div>

        {/* Search and Filters */}
        <form onSubmit={handleSearch} className="space-y-4">
          <div className="flex gap-4">
            <div className="flex-1">
              <div className="relative">
                <SearchIcon />
                <input
                  type="text"
                  placeholder="Buscar categorías..."
                  value={localFilters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
            <button
              type="button"
              onClick={() => setShowFilters(!showFilters)}
              className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <FilterIcon />
              Filtros
            </button>
            <button
              type="submit"
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Buscar
            </button>
          </div>

          {/* Advanced Filters */}
          {showFilters && (
            <div className="p-4 bg-gray-50 rounded-lg space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Estado
                  </label>
                  <select
                    value={localFilters.is_active !== undefined ? localFilters.is_active.toString() : ''}
                    onChange={(e) => handleFilterChange('is_active', e.target.value === 'true')}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">Todos</option>
                    <option value="true">Activos</option>
                    <option value="false">Inactivos</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Por página
                  </label>
                  <select
                    value={localFilters.per_page}
                    onChange={(e) => handleFilterChange('per_page', parseInt(e.target.value))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value={10}>10</option>
                    <option value={25}>25</option>
                    <option value={50}>50</option>
                  </select>
                </div>
              </div>
              <div className="flex justify-end">
                <button
                  type="button"
                  onClick={resetFilters}
                  className="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                >
                  Limpiar filtros
                </button>
              </div>
            </div>
          )}
        </form>
      </div>

      {/* Content */}
      <div className="p-6">
        {isLoading ? (
          <div className="flex justify-center items-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>
        ) : categories.data.length === 0 ? (
          <div className="text-center py-8">
            <p className="text-gray-500">No se encontraron categorías</p>
          </div>
        ) : (
          <>
            {/* Categories Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
              {categories.data.map((category) => (
                <div
                  key={category.id}
                  className={`p-4 border rounded-lg transition-all cursor-pointer ${
                    selectable && selectedId === category.id
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300 hover:shadow-sm'
                  }`}
                  onClick={() => selectable && onSelect?.(category)}
                >
                  <div className="flex justify-between items-start mb-2">
                    <h3 className="font-medium text-gray-900">{category.name}</h3>
                    <div className="flex items-center space-x-2">
                      <span
                        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                          category.is_active
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800'
                        }`}
                      >
                        {category.is_active ? 'Activo' : 'Inactivo'}
                      </span>
                    </div>
                  </div>
                  
                  {category.description && (
                    <p className="text-sm text-gray-600 mb-3">{category.description}</p>
                  )}
                  
                  <div className="flex justify-between items-center text-sm text-gray-500">
                    <span>Jugadores: {category.players_count || 0}</span>
                    <div className="flex space-x-2">
                      {onEdit && (
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            onEdit(category);
                          }}
                          className="p-1 text-blue-600 hover:text-blue-800 transition-colors"
                          title="Editar"
                        >
                          <EditIcon />
                        </button>
                      )}
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDelete(category.id);
                        }}
                        className="p-1 text-red-600 hover:text-red-800 transition-colors"
                        title="Eliminar"
                      >
                        <TrashIcon />
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Pagination */}
            {categories.last_page > 1 && (
              <div className="flex justify-between items-center">
                <div className="text-sm text-gray-700">
                  Mostrando {categories.from} a {categories.to} de {categories.total} resultados
                </div>
                <div className="flex space-x-2">
                  <button
                    onClick={() => handlePageChange(categories.current_page - 1)}
                    disabled={categories.current_page === 1}
                    className="px-3 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                  >
                    Anterior
                  </button>
                  
                  {Array.from({ length: categories.last_page }, (_, i) => i + 1)
                    .filter(page => 
                      page === 1 || 
                      page === categories.last_page || 
                      Math.abs(page - categories.current_page) <= 2
                    )
                    .map((page, index, array) => (
                      <React.Fragment key={page}>
                        {index > 0 && array[index - 1] !== page - 1 && (
                          <span className="px-3 py-2 text-gray-500">...</span>
                        )}
                        <button
                          onClick={() => handlePageChange(page)}
                          className={`px-3 py-2 border rounded-lg transition-colors ${
                            page === categories.current_page
                              ? 'bg-blue-600 text-white border-blue-600'
                              : 'border-gray-300 hover:bg-gray-50'
                          }`}
                        >
                          {page}
                        </button>
                      </React.Fragment>
                    ))}
                  
                  <button
                    onClick={() => handlePageChange(categories.current_page + 1)}
                    disabled={categories.current_page === categories.last_page}
                    className="px-3 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                  >
                    Siguiente
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default CategoriesList;