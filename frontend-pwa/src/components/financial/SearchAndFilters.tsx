import React, { useState, useCallback, useEffect } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Stack,
  Chip,
  Button,
  IconButton,
  Collapse,
  Divider,
  Alert,
} from '@mui/material';
import {
  FilterList as FilterIcon,
  Clear as ClearIcon,
  Save as SaveIcon,
  Bookmark as BookmarkIcon,
  ExpandMore as ExpandMoreIcon,
  ExpandLess as ExpandLessIcon,
} from '@mui/icons-material';
import { useAppDispatch, useAppSelector } from '../../store';
import {
  selectARFilters,
  selectARLoading,
} from '../../store';
import {
  setFilters,
  resetFilters,
  fetchAccountsReceivable,
} from '../../store/accountsReceivableSlice';
import { ARFilters } from '../../types/financial';
import AdvancedFilters from './AdvancedFilters';
import EnhancedSearch from './EnhancedSearch';

interface SearchAndFiltersProps {
  onFiltersApplied?: (filters: ARFilters) => void;
  students?: Array<{ id: string; name: string; email: string }>;
  concepts?: Array<{ id: string; name: string; description: string }>;
  savedFilters?: Array<{ id: string; name: string; filters: ARFilters }>;
  onSaveFilters?: (name: string, filters: ARFilters) => void;
}

const SearchAndFilters: React.FC<SearchAndFiltersProps> = ({
  onFiltersApplied,
  students = [],
  concepts = [],
  savedFilters = [],
  onSaveFilters,
}) => {
  const dispatch = useAppDispatch();
  const currentFilters = useAppSelector(selectARFilters);
  const loading = useAppSelector(selectARLoading);

  const [localFilters, setLocalFilters] = useState<Partial<ARFilters>>(currentFilters);
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);
  const [showSavedFilters, setShowSavedFilters] = useState(false);
  const [saveFilterName, setSaveFilterName] = useState('');
  const [showSaveDialog, setShowSaveDialog] = useState(false);

  // Sync local filters with Redux state
  useEffect(() => {
    setLocalFilters(currentFilters);
  }, [currentFilters]);

  // Handle search change
  const handleSearchChange = useCallback((value: string) => {
    setLocalFilters(prev => ({ ...prev, search: value }));
  }, []);

  // Handle filters change
  const handleFiltersChange = useCallback((newFilters: Partial<ARFilters>) => {
    setLocalFilters(newFilters);
  }, []);

  // Apply filters
  const handleApplyFilters = useCallback(() => {
    const filtersToApply = {
      ...localFilters,
      page: 1, // Reset to first page
    } as ARFilters;
    
    dispatch(setFilters(filtersToApply));
    dispatch(fetchAccountsReceivable(filtersToApply));
    
    if (onFiltersApplied) {
      onFiltersApplied(filtersToApply);
    }
  }, [dispatch, localFilters, onFiltersApplied]);

  // Clear all filters
  const handleClearFilters = useCallback(() => {
    const clearedFilters = {
      page: 1,
      per_page: 10,
      sort_by: 'created_at',
      sort_order: 'desc',
    } as ARFilters;
    
    setLocalFilters(clearedFilters);
    dispatch(resetFilters());
    dispatch(fetchAccountsReceivable(clearedFilters));
    
    if (onFiltersApplied) {
      onFiltersApplied(clearedFilters);
    }
  }, [dispatch, onFiltersApplied]);

  // Apply saved filter
  const handleApplySavedFilter = useCallback((savedFilter: { id: string; name: string; filters: ARFilters }) => {
    setLocalFilters(savedFilter.filters);
    dispatch(setFilters(savedFilter.filters));
    dispatch(fetchAccountsReceivable(savedFilter.filters));
    
    if (onFiltersApplied) {
      onFiltersApplied(savedFilter.filters);
    }
  }, [dispatch, onFiltersApplied]);

  // Save current filters
  const handleSaveFilters = useCallback(() => {
    if (saveFilterName.trim() && onSaveFilters) {
      onSaveFilters(saveFilterName.trim(), localFilters as ARFilters);
      setSaveFilterName('');
      setShowSaveDialog(false);
    }
  }, [saveFilterName, localFilters, onSaveFilters]);

  // Get active filters count
  const getActiveFiltersCount = () => {
    let count = 0;
    if (localFilters.search) count++;
    if (localFilters.status) count++;
    if (localFilters.student_id) count++;
    if (localFilters.concept_id) count++;
    if (localFilters.due_date_from) count++;
    if (localFilters.due_date_to) count++;
    if (localFilters.amount_min && localFilters.amount_min > 0) count++;
    if (localFilters.amount_max && localFilters.amount_max < 100000) count++;
    if (localFilters.overdue_only) count++;
    return count;
  };

  const activeFiltersCount = getActiveFiltersCount();

  // Generate search suggestions
  const generateSearchSuggestions = () => {
    const suggestions = [];
    
    // Add student suggestions
    if (localFilters.search) {
      const matchingStudents = students.filter(student => 
        student.name.toLowerCase().includes(localFilters.search!.toLowerCase()) ||
        student.email.toLowerCase().includes(localFilters.search!.toLowerCase())
      ).slice(0, 3);
      
      suggestions.push(...matchingStudents.map(student => ({
        id: `student-${student.id}`,
        type: 'student' as const,
        label: student.name,
        subtitle: student.email,
        value: student.name,
      })));
    }
    
    // Add concept suggestions
    if (localFilters.search) {
      const matchingConcepts = concepts.filter(concept => 
        concept.name.toLowerCase().includes(localFilters.search!.toLowerCase())
      ).slice(0, 3);
      
      suggestions.push(...matchingConcepts.map(concept => ({
        id: `concept-${concept.id}`,
        type: 'concept' as const,
        label: concept.name,
        subtitle: concept.description,
        value: concept.name,
      })));
    }
    
    return suggestions;
  };

  const searchSuggestions = generateSearchSuggestions();
  const recentSearches = ['Matrícula 2024', 'Pensión Enero', 'Transporte'];
  const popularSearches = ['Matrícula', 'Pensión', 'Uniformes', 'Transporte'];

  return (
    <Stack spacing={2}>
      {/* Search Bar */}
      <Card>
        <CardContent>
          <Stack spacing={2}>
            <EnhancedSearch
              value={localFilters.search || ''}
              onChange={handleSearchChange}
              onSearch={handleApplyFilters}
              placeholder="Buscar por número, descripción, estudiante..."
              suggestions={searchSuggestions}
              loading={loading.accountsReceivable}
              showFilters={true}
              onFiltersClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
              recentSearches={recentSearches}
              popularSearches={popularSearches}
            />
            
            {/* Quick Actions */}
            <Stack direction="row" spacing={1} alignItems="center">
              <Button
                variant={showAdvancedFilters ? 'contained' : 'outlined'}
                size="small"
                startIcon={<FilterIcon />}
                onClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
                endIcon={showAdvancedFilters ? <ExpandLessIcon /> : <ExpandMoreIcon />}
              >
                Filtros Avanzados
                {activeFiltersCount > 0 && (
                  <Chip
                    label={activeFiltersCount}
                    size="small"
                    color="primary"
                    sx={{ ml: 1 }}
                  />
                )}
              </Button>
              
              {savedFilters.length > 0 && (
                <Button
                  variant="outlined"
                  size="small"
                  startIcon={<BookmarkIcon />}
                  onClick={() => setShowSavedFilters(!showSavedFilters)}
                >
                  Filtros Guardados ({savedFilters.length})
                </Button>
              )}
              
              {activeFiltersCount > 0 && (
                <Button
                  variant="outlined"
                  size="small"
                  startIcon={<ClearIcon />}
                  onClick={handleClearFilters}
                  color="error"
                >
                  Limpiar Todo
                </Button>
              )}
              
              {onSaveFilters && activeFiltersCount > 0 && (
                <Button
                  variant="outlined"
                  size="small"
                  startIcon={<SaveIcon />}
                  onClick={() => setShowSaveDialog(true)}
                >
                  Guardar Filtros
                </Button>
              )}
            </Stack>
          </Stack>
        </CardContent>
      </Card>

      {/* Saved Filters */}
      <Collapse in={showSavedFilters}>
        <Card>
          <CardContent>
            <Typography variant="h6" gutterBottom>
              Filtros Guardados
            </Typography>
            <Stack direction="row" spacing={1} flexWrap="wrap">
              {savedFilters.map((savedFilter) => (
                <Chip
                  key={savedFilter.id}
                  label={savedFilter.name}
                  onClick={() => handleApplySavedFilter(savedFilter)}
                  onDelete={() => {
                    // Handle delete saved filter
                  }}
                  variant="outlined"
                  color="primary"
                  sx={{ mb: 1 }}
                />
              ))}
            </Stack>
          </CardContent>
        </Card>
      </Collapse>

      {/* Advanced Filters */}
      <Collapse in={showAdvancedFilters}>
        <AdvancedFilters
          filters={localFilters}
          onFiltersChange={handleFiltersChange}
          onApplyFilters={handleApplyFilters}
          onClearFilters={handleClearFilters}
          students={students}
          concepts={concepts}
          loading={loading.accountsReceivable}
        />
      </Collapse>

      {/* Active Filters Summary */}
      {activeFiltersCount > 0 && (
        <Card>
          <CardContent>
            <Stack direction="row" alignItems="center" spacing={1} flexWrap="wrap">
              <Typography variant="body2" color="text.secondary">
                Filtros activos:
              </Typography>
              
              {localFilters.search && (
                <Chip
                  label={`Búsqueda: "${localFilters.search}"`}
                  size="small"
                  onDelete={() => handleFiltersChange({ ...localFilters, search: undefined })}
                />
              )}
              
              {localFilters.status && (
                <Chip
                  label={`Estado: ${localFilters.status}`}
                  size="small"
                  onDelete={() => handleFiltersChange({ ...localFilters, status: undefined })}
                />
              )}
              
              {localFilters.student_id && (
                <Chip
                  label={`Estudiante: ${students.find(s => s.id === localFilters.student_id)?.name || 'Seleccionado'}`}
                  size="small"
                  onDelete={() => handleFiltersChange({ ...localFilters, student_id: undefined })}
                />
              )}
              
              {localFilters.concept_id && (
                <Chip
                  label={`Concepto: ${concepts.find(c => c.id === localFilters.concept_id)?.name || 'Seleccionado'}`}
                  size="small"
                  onDelete={() => handleFiltersChange({ ...localFilters, concept_id: undefined })}
                />
              )}
              
              {localFilters.due_date_from && (
                <Chip
                  label={`Desde: ${localFilters.due_date_from}`}
                  size="small"
                  onDelete={() => handleFiltersChange({ ...localFilters, due_date_from: undefined })}
                />
              )}
              
              {localFilters.due_date_to && (
                <Chip
                  label={`Hasta: ${localFilters.due_date_to}`}
                  size="small"
                  onDelete={() => handleFiltersChange({ ...localFilters, due_date_to: undefined })}
                />
              )}
              
              {localFilters.overdue_only && (
                <Chip
                  label="Solo vencidas"
                  size="small"
                  onDelete={() => handleFiltersChange({ ...localFilters, overdue_only: false })}
                />
              )}
            </Stack>
          </CardContent>
        </Card>
      )}

      {/* Save Filter Dialog */}
      {showSaveDialog && (
        <Alert
          severity="info"
          action={
            <Stack direction="row" spacing={1}>
              <Button size="small" onClick={handleSaveFilters}>
                Guardar
              </Button>
              <Button size="small" onClick={() => setShowSaveDialog(false)}>
                Cancelar
              </Button>
            </Stack>
          }
        >
          <input
            type="text"
            placeholder="Nombre del filtro"
            value={saveFilterName}
            onChange={(e) => setSaveFilterName(e.target.value)}
            style={{ border: 'none', outline: 'none', background: 'transparent' }}
          />
        </Alert>
      )}
    </Stack>
  );
};

export default SearchAndFilters;