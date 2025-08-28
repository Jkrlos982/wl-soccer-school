import React, { useState, useCallback, useEffect } from 'react';
import {
  Box,
  TextField,
  InputAdornment,
  IconButton,
  Popover,
  Paper,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  Typography,
  Chip,
  Stack,
  Divider,
  Button,
  CircularProgress,
} from '@mui/material';
import {
  Search as SearchIcon,
  Clear as ClearIcon,
  FilterList as FilterIcon,
  History as HistoryIcon,
  TrendingUp as TrendingIcon,
  Person as PersonIcon,
  Description as DescriptionIcon,
  Numbers as NumbersIcon,
} from '@mui/icons-material';
import { useDebounce } from '../../hooks/useDebounce';

interface SearchSuggestion {
  id: string;
  type: 'student' | 'concept' | 'number' | 'description' | 'recent' | 'popular';
  label: string;
  subtitle?: string;
  value: string;
}

interface EnhancedSearchProps {
  value: string;
  onChange: (value: string) => void;
  onSearch?: (query: string) => void;
  placeholder?: string;
  suggestions?: SearchSuggestion[];
  loading?: boolean;
  disabled?: boolean;
  showFilters?: boolean;
  onFiltersClick?: () => void;
  recentSearches?: string[];
  popularSearches?: string[];
}

const EnhancedSearch: React.FC<EnhancedSearchProps> = ({
  value,
  onChange,
  onSearch,
  placeholder = 'Buscar cuentas por cobrar...',
  suggestions = [],
  loading = false,
  disabled = false,
  showFilters = true,
  onFiltersClick,
  recentSearches = [],
  popularSearches = [],
}) => {
  const [anchorEl, setAnchorEl] = useState<HTMLElement | null>(null);
  const [focused, setFocused] = useState(false);
  const [localValue, setLocalValue] = useState(value);
  
  const debouncedValue = useDebounce(localValue, 300);

  // Update local value when prop changes
  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  // Handle debounced search
  useEffect(() => {
    if (debouncedValue !== value) {
      onChange(debouncedValue);
      if (onSearch && debouncedValue.trim()) {
        onSearch(debouncedValue);
      }
    }
  }, [debouncedValue, value, onChange, onSearch]);

  const handleInputChange = useCallback((event: React.ChangeEvent<HTMLInputElement>) => {
    setLocalValue(event.target.value);
  }, []);

  const handleFocus = useCallback((event: React.FocusEvent<HTMLInputElement>) => {
    setFocused(true);
    setAnchorEl(event.currentTarget);
  }, []);

  const handleBlur = useCallback(() => {
    // Delay hiding to allow clicking on suggestions
    setTimeout(() => {
      setFocused(false);
      setAnchorEl(null);
    }, 200);
  }, []);

  const handleClear = useCallback(() => {
    setLocalValue('');
    onChange('');
  }, [onChange]);

  const handleSuggestionClick = useCallback((suggestion: SearchSuggestion) => {
    setLocalValue(suggestion.value);
    onChange(suggestion.value);
    if (onSearch) {
      onSearch(suggestion.value);
    }
    setFocused(false);
    setAnchorEl(null);
  }, [onChange, onSearch]);

  const handleKeyPress = useCallback((event: React.KeyboardEvent) => {
    if (event.key === 'Enter' && onSearch && localValue.trim()) {
      onSearch(localValue);
      setFocused(false);
      setAnchorEl(null);
    }
  }, [onSearch, localValue]);

  const getIconForType = (type: SearchSuggestion['type']) => {
    switch (type) {
      case 'student':
        return <PersonIcon fontSize="small" />;
      case 'concept':
        return <DescriptionIcon fontSize="small" />;
      case 'number':
        return <NumbersIcon fontSize="small" />;
      case 'recent':
        return <HistoryIcon fontSize="small" />;
      case 'popular':
        return <TrendingIcon fontSize="small" />;
      default:
        return <SearchIcon fontSize="small" />;
    }
  };

  const getColorForType = (type: SearchSuggestion['type']) => {
    switch (type) {
      case 'student':
        return 'primary';
      case 'concept':
        return 'secondary';
      case 'number':
        return 'info';
      case 'recent':
        return 'default';
      case 'popular':
        return 'success';
      default:
        return 'default';
    }
  };

  // Combine all suggestions
  const allSuggestions: SearchSuggestion[] = [
    ...suggestions,
    ...recentSearches.slice(0, 3).map((search, index) => ({
      id: `recent-${index}`,
      type: 'recent' as const,
      label: search,
      value: search,
    })),
    ...popularSearches.slice(0, 3).map((search, index) => ({
      id: `popular-${index}`,
      type: 'popular' as const,
      label: search,
      value: search,
    })),
  ];

  const showSuggestions = focused && (localValue.length > 0 || allSuggestions.length > 0);

  return (
    <Box sx={{ position: 'relative', width: '100%' }}>
      <TextField
        fullWidth
        value={localValue}
        onChange={handleInputChange}
        onFocus={handleFocus}
        onBlur={handleBlur}
        onKeyPress={handleKeyPress}
        placeholder={placeholder}
        disabled={disabled}
        InputProps={{
          startAdornment: (
            <InputAdornment position="start">
              {loading ? (
                <CircularProgress size={20} />
              ) : (
                <SearchIcon color="action" />
              )}
            </InputAdornment>
          ),
          endAdornment: (
            <InputAdornment position="end">
              <Stack direction="row" spacing={0.5}>
                {localValue && (
                  <IconButton
                    size="small"
                    onClick={handleClear}
                    disabled={disabled}
                  >
                    <ClearIcon fontSize="small" />
                  </IconButton>
                )}
                {showFilters && (
                  <IconButton
                    size="small"
                    onClick={onFiltersClick}
                    disabled={disabled}
                    color={onFiltersClick ? 'primary' : 'default'}
                  >
                    <FilterIcon fontSize="small" />
                  </IconButton>
                )}
              </Stack>
            </InputAdornment>
          ),
        }}
      />

      <Popover
        open={showSuggestions}
        anchorEl={anchorEl}
        onClose={() => setFocused(false)}
        anchorOrigin={{
          vertical: 'bottom',
          horizontal: 'left',
        }}
        transformOrigin={{
          vertical: 'top',
          horizontal: 'left',
        }}
        PaperProps={{
          sx: {
            width: anchorEl?.clientWidth || 'auto',
            maxHeight: 400,
            overflow: 'auto',
          },
        }}
      >
        <Paper>
          {localValue.length > 0 && (
            <>
              <Box sx={{ p: 2, pb: 1 }}>
                <Typography variant="body2" color="text.secondary">
                  Buscar: "{localValue}"
                </Typography>
              </Box>
              <Divider />
            </>
          )}
          
          {allSuggestions.length > 0 ? (
            <List dense>
              {allSuggestions.map((suggestion) => (
                <ListItem
                  key={suggestion.id}
                  component="div"
                  sx={{ cursor: 'pointer' }}
                  onClick={() => handleSuggestionClick(suggestion)}
                >
                  <ListItemIcon sx={{ minWidth: 36 }}>
                    {getIconForType(suggestion.type)}
                  </ListItemIcon>
                  <ListItemText
                    primary={
                      <Stack direction="row" alignItems="center" spacing={1}>
                        <Typography variant="body2">
                          {suggestion.label}
                        </Typography>
                        <Chip
                          label={suggestion.type}
                          size="small"
                          variant="outlined"
                          color={getColorForType(suggestion.type)}
                        />
                      </Stack>
                    }
                    secondary={suggestion.subtitle}
                  />
                </ListItem>
              ))}
            </List>
          ) : (
            <Box sx={{ p: 3, textAlign: 'center' }}>
              <Typography variant="body2" color="text.secondary">
                No hay sugerencias disponibles
              </Typography>
            </Box>
          )}
          
          {localValue.length > 2 && (
            <>
              <Divider />
              <Box sx={{ p: 1 }}>
                <Button
                  fullWidth
                  size="small"
                  startIcon={<SearchIcon />}
                  onClick={() => {
                    if (onSearch) {
                      onSearch(localValue);
                    }
                    setFocused(false);
                    setAnchorEl(null);
                  }}
                >
                  Buscar "{localValue}"
                </Button>
              </Box>
            </>
          )}
        </Paper>
      </Popover>
    </Box>
  );
};

export default EnhancedSearch;