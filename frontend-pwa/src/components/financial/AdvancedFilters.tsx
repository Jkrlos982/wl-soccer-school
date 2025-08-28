import React, { useState, useCallback } from 'react';
import {
  Box,
  Card,
  CardContent,
  CardHeader,
  Typography,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Button,
  Stack,
  Chip,
  Autocomplete,
  Switch,
  FormControlLabel,
  Slider,
  Collapse,
  IconButton,
  Divider,

} from '@mui/material';
import {
  FilterList as FilterIcon,
  Clear as ClearIcon,
  ExpandMore as ExpandMoreIcon,
  ExpandLess as ExpandLessIcon,
  Search as SearchIcon,
} from '@mui/icons-material';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { es } from 'date-fns/locale';
import { format } from 'date-fns';
import { ARFilters, AccountReceivable } from '../../types/financial';

interface AdvancedFiltersProps {
  filters: Partial<ARFilters>;
  onFiltersChange: (filters: Partial<ARFilters>) => void;
  onApplyFilters: () => void;
  onClearFilters: () => void;
  students?: Array<{ id: string; name: string; email: string }>;
  concepts?: Array<{ id: string; name: string; description: string }>;
  loading?: boolean;
}

const statusOptions: Array<{ value: AccountReceivable['status']; label: string; color: 'default' | 'primary' | 'secondary' | 'error' | 'info' | 'success' | 'warning' }> = [
  { value: 'pending', label: 'Pendiente', color: 'warning' },
  { value: 'partial', label: 'Parcial', color: 'info' },
  { value: 'paid', label: 'Pagado', color: 'success' },
  { value: 'overdue', label: 'Vencido', color: 'error' },
  { value: 'cancelled', label: 'Cancelado', color: 'default' },
];

const sortOptions = [
  { value: 'created_at', label: 'Fecha de creación' },
  { value: 'due_date', label: 'Fecha de vencimiento' },
  { value: 'amount', label: 'Monto' },
  { value: 'balance', label: 'Saldo' },
];

const AdvancedFilters: React.FC<AdvancedFiltersProps> = ({
  filters,
  onFiltersChange,
  onApplyFilters,
  onClearFilters,
  students = [],
  concepts = [],
  loading = false,
}) => {
  const [expanded, setExpanded] = useState(false);
  const [amountRange, setAmountRange] = useState<[number, number]>([0, 100000]);

  // Handle filter changes
  const handleFilterChange = useCallback(
    (field: keyof ARFilters, value: any) => {
      onFiltersChange({ ...filters, [field]: value });
    },
    [filters, onFiltersChange]
  );

  // Handle amount range change
  const handleAmountRangeChange = useCallback(
    (event: Event, newValue: number | number[]) => {
      const range = newValue as [number, number];
      setAmountRange(range);
      onFiltersChange({
        ...filters,
        amount_min: range[0],
        amount_max: range[1],
      });
    },
    [filters, onFiltersChange]
  );

  // Get active filters count
  const getActiveFiltersCount = () => {
    let count = 0;
    if (filters.search) count++;
    if (filters.status) count++;
    if (filters.student_id) count++;
    if (filters.concept_id) count++;
    if (filters.due_date_from) count++;
    if (filters.due_date_to) count++;
    if (filters.amount_min && filters.amount_min > 0) count++;
    if (filters.amount_max && filters.amount_max < 100000) count++;
    if (filters.overdue_only) count++;
    return count;
  };

  const activeFiltersCount = getActiveFiltersCount();

  return (
    <LocalizationProvider dateAdapter={AdapterDateFns} adapterLocale={es}>
      <Card>
        <CardHeader
          title={
            <Stack direction="row" alignItems="center" spacing={1}>
              <FilterIcon />
              <Typography variant="h6">Filtros Avanzados</Typography>
              {activeFiltersCount > 0 && (
                <Chip
                  label={`${activeFiltersCount} activo${activeFiltersCount > 1 ? 's' : ''}`}
                  size="small"
                  color="primary"
                />
              )}
            </Stack>
          }
          action={
            <Stack direction="row" spacing={1}>
              <Button
                variant="outlined"
                size="small"
                startIcon={<ClearIcon />}
                onClick={onClearFilters}
                disabled={loading || activeFiltersCount === 0}
              >
                Limpiar
              </Button>
              <Button
                variant="contained"
                size="small"
                startIcon={<SearchIcon />}
                onClick={onApplyFilters}
                disabled={loading}
              >
                Aplicar
              </Button>
              <IconButton
                onClick={() => setExpanded(!expanded)}
                size="small"
              >
                {expanded ? <ExpandLessIcon /> : <ExpandMoreIcon />}
              </IconButton>
            </Stack>
          }
        />
        <Collapse in={expanded}>
          <CardContent>
            <Stack spacing={3}>
              {/* Basic Filters */}
              <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                <Box sx={{ flex: 1 }}>
                  <TextField
                    fullWidth
                    label="Buscar"
                    placeholder="Número, descripción, estudiante..."
                    value={filters.search || ''}
                    onChange={(e) => handleFilterChange('search', e.target.value)}
                    InputProps={{
                      startAdornment: <SearchIcon sx={{ mr: 1, color: 'text.secondary' }} />,
                    }}
                  />
                </Box>
                <Box sx={{ flex: 1 }}>
                  <FormControl fullWidth>
                    <InputLabel>Estado</InputLabel>
                    <Select
                      value={filters.status || ''}
                      onChange={(e) => handleFilterChange('status', e.target.value || undefined)}
                      label="Estado"
                    >
                      <MenuItem value="">
                        <em>Todos los estados</em>
                      </MenuItem>
                      {statusOptions.map((option) => (
                        <MenuItem key={option.value} value={option.value}>
                          <Stack direction="row" alignItems="center" spacing={1}>
                            <Chip
                              label={option.label}
                              size="small"
                              color={option.color}
                              variant="outlined"
                            />
                          </Stack>
                        </MenuItem>
                      ))}
                    </Select>
                  </FormControl>
                </Box>
              </Stack>

              {/* Student and Concept Filters */}
              <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                <Box sx={{ flex: 1 }}>
                  <Autocomplete
                    options={students}
                    getOptionLabel={(option) => `${option.name} (${option.email})`}
                    value={students.find(s => s.id === filters.student_id) || null}
                    onChange={(_, value) => handleFilterChange('student_id', value?.id || undefined)}
                    renderInput={(params) => (
                      <TextField
                        {...params}
                        label="Estudiante"
                        placeholder="Seleccionar estudiante"
                      />
                    )}
                    renderOption={(props, option) => (
                      <Box component="li" {...props}>
                        <Stack>
                          <Typography variant="body2">{option.name}</Typography>
                          <Typography variant="caption" color="text.secondary">
                            {option.email}
                          </Typography>
                        </Stack>
                      </Box>
                    )}
                  />
                </Box>
                <Box sx={{ flex: 1 }}>
                  <Autocomplete
                    options={concepts}
                    getOptionLabel={(option) => option.name}
                    value={concepts.find(c => c.id === filters.concept_id) || null}
                    onChange={(_, value) => handleFilterChange('concept_id', value?.id || undefined)}
                    renderInput={(params) => (
                      <TextField
                        {...params}
                        label="Concepto"
                        placeholder="Seleccionar concepto"
                      />
                    )}
                    renderOption={(props, option) => (
                      <Box component="li" {...props}>
                        <Stack>
                          <Typography variant="body2">{option.name}</Typography>
                          <Typography variant="caption" color="text.secondary">
                            {option.description}
                          </Typography>
                        </Stack>
                      </Box>
                    )}
                  />
                </Box>
              </Stack>

              <Divider />

              {/* Date Range Filters */}
              <Box>
                <Typography variant="subtitle2" gutterBottom>
                  Rango de Fechas de Vencimiento
                </Typography>
                <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                  <Box sx={{ flex: 1 }}>
                    <DatePicker
                      label="Fecha desde"
                      value={filters.due_date_from ? new Date(filters.due_date_from) : null}
                      onChange={(date) => handleFilterChange('due_date_from', date ? format(date, 'yyyy-MM-dd') : undefined)}
                      slotProps={{ textField: { fullWidth: true } }}
                    />
                  </Box>
                  <Box sx={{ flex: 1 }}>
                    <DatePicker
                      label="Fecha hasta"
                      value={filters.due_date_to ? new Date(filters.due_date_to) : null}
                      onChange={(date) => handleFilterChange('due_date_to', date ? format(date, 'yyyy-MM-dd') : undefined)}
                      slotProps={{ textField: { fullWidth: true } }}
                    />
                  </Box>
                </Stack>
              </Box>

              <Divider />

              {/* Amount Range Filter */}
              <Box>
                <Typography variant="subtitle2" gutterBottom>
                  Rango de Monto (${amountRange[0].toLocaleString()} - ${amountRange[1].toLocaleString()})
                </Typography>
                <Box sx={{ px: 2 }}>
                  <Slider
                    value={amountRange}
                    onChange={handleAmountRangeChange}
                    valueLabelDisplay="auto"
                    min={0}
                    max={100000}
                    step={1000}
                    marks={[
                      { value: 0, label: '$0' },
                      { value: 25000, label: '$25K' },
                      { value: 50000, label: '$50K' },
                      { value: 75000, label: '$75K' },
                      { value: 100000, label: '$100K' },
                    ]}
                    valueLabelFormat={(value) => `$${value.toLocaleString()}`}
                  />
                </Box>
              </Box>

              <Divider />

              {/* Additional Options */}
              <Stack spacing={2}>
                <Typography variant="subtitle2">
                  Opciones Adicionales
                </Typography>
                <FormControlLabel
                  control={
                    <Switch
                      checked={filters.overdue_only || false}
                      onChange={(e) => handleFilterChange('overdue_only', e.target.checked)}
                    />
                  }
                  label="Solo cuentas vencidas"
                />
              </Stack>

              <Divider />

              {/* Sorting Options */}
              <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                <Box sx={{ flex: 1 }}>
                  <FormControl fullWidth>
                    <InputLabel>Ordenar por</InputLabel>
                    <Select
                      value={filters.sort_by || 'created_at'}
                      onChange={(e) => handleFilterChange('sort_by', e.target.value)}
                      label="Ordenar por"
                    >
                      {sortOptions.map((option) => (
                        <MenuItem key={option.value} value={option.value}>
                          {option.label}
                        </MenuItem>
                      ))}
                    </Select>
                  </FormControl>
                </Box>
                <Box sx={{ flex: 1 }}>
                  <FormControl fullWidth>
                    <InputLabel>Orden</InputLabel>
                    <Select
                      value={filters.sort_order || 'desc'}
                      onChange={(e) => handleFilterChange('sort_order', e.target.value as 'asc' | 'desc')}
                      label="Orden"
                    >
                      <MenuItem value="desc">Descendente</MenuItem>
                      <MenuItem value="asc">Ascendente</MenuItem>
                    </Select>
                  </FormControl>
                </Box>
              </Stack>
            </Stack>
          </CardContent>
        </Collapse>
      </Card>
    </LocalizationProvider>
  );
};

export default AdvancedFilters;