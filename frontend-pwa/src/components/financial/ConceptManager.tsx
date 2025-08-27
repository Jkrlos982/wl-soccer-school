import React, { useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  Grid,
  TextField,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  IconButton,
  Chip,
  Alert,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  FormHelperText,
  Stack,
  Tooltip,
  TablePagination,
} from '@mui/material';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Search as SearchIcon,
  Refresh as RefreshIcon,
} from '@mui/icons-material';
import { useFormik } from 'formik';
import * as Yup from 'yup';
import { useAppDispatch, useAppSelector } from '../../store';
import {
  selectConcepts,
  selectFinancialLoading,
  selectFinancialErrors,
} from '../../store';
import {
  fetchConcepts,
  createConcept,
  updateConcept,
  deleteConcept,
} from '../../store/financialSlice';
import {
  FinancialConcept,
  CreateFinancialConceptData,
  UpdateFinancialConceptData,
} from '../../types';

interface ConceptFormData {
  name: string;
  description: string;
  type: 'income' | 'expense' | 'transfer';
  category: string;
  is_active: boolean;
}

// Validation schema with Spanish messages (based on centralized schema structure)
const validationSchema = Yup.object({
  name: Yup.string()
    .required('El nombre es requerido')
    .min(2, 'El nombre debe tener al menos 2 caracteres')
    .max(100, 'El nombre no puede exceder 100 caracteres'),
  description: Yup.string()
    .max(500, 'La descripción no puede exceder 500 caracteres'),
  type: Yup.string()
    .oneOf(['income', 'expense', 'transfer'])
    .required('El tipo es requerido'),
  category: Yup.string()
    .required('La categoría es requerida')
    .max(50, 'La categoría no puede exceder 50 caracteres'),
  is_active: Yup.boolean().required(),
});

const conceptTypes = [
  { value: 'income', label: 'Ingreso', color: 'success' as const },
  { value: 'expense', label: 'Gasto', color: 'error' as const },
  { value: 'transfer', label: 'Transferencia', color: 'info' as const },
];

const categories = [
  'Matrícula',
  'Pensión',
  'Alimentación',
  'Transporte',
  'Materiales',
  'Uniformes',
  'Actividades',
  'Servicios',
  'Mantenimiento',
  'Administrativo',
  'Otros',
];

const ConceptManager: React.FC = () => {
  const dispatch = useAppDispatch();
  const concepts = useAppSelector(selectConcepts);
  const loading = useAppSelector(selectFinancialLoading);
  const errors = useAppSelector(selectFinancialErrors);

  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingConcept, setEditingConcept] = useState<FinancialConcept | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [typeFilter, setTypeFilter] = useState<string>('');
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [submitError, setSubmitError] = useState<string | null>(null);

  // Load concepts on component mount
  useEffect(() => {
    dispatch(fetchConcepts());
  }, [dispatch]);

  const formik = useFormik<ConceptFormData>({
    initialValues: {
      name: '',
      description: '',
      type: 'income',
      category: '',
      is_active: true,
    },
    validationSchema,
    onSubmit: async (values, { resetForm }) => {
      setSubmitError(null);
      
      try {
        if (editingConcept) {
          await dispatch(
            updateConcept({
              id: editingConcept.id,
              data: values as UpdateFinancialConceptData,
            })
          ).unwrap();
        } else {
          await dispatch(
            createConcept(values as CreateFinancialConceptData)
          ).unwrap();
        }
        
        resetForm();
        setDialogOpen(false);
        setEditingConcept(null);
      } catch (error: any) {
        setSubmitError(
          error.message || `Error al ${editingConcept ? 'actualizar' : 'crear'} el concepto`
        );
      }
    },
  });

  // Open dialog for creating new concept
  const handleCreate = () => {
    formik.resetForm();
    setEditingConcept(null);
    setSubmitError(null);
    setDialogOpen(true);
  };

  // Open dialog for editing concept
  const handleEdit = (concept: FinancialConcept) => {
    formik.setValues({
      name: concept.name,
      description: concept.description || '',
      type: concept.type,
      category: concept.category,
      is_active: concept.is_active,
    });
    setEditingConcept(concept);
    setSubmitError(null);
    setDialogOpen(true);
  };

  // Delete concept
  const handleDelete = async (concept: FinancialConcept) => {
    if (window.confirm(`¿Está seguro de eliminar el concepto "${concept.name}"?`)) {
      try {
        await dispatch(deleteConcept(concept.id)).unwrap();
      } catch (error: any) {
        alert(error.message || 'Error al eliminar el concepto');
      }
    }
  };

  // Close dialog
  const handleCloseDialog = () => {
    setDialogOpen(false);
    setEditingConcept(null);
    setSubmitError(null);
    formik.resetForm();
  };

  // Refresh concepts
  const handleRefresh = () => {
    dispatch(fetchConcepts());
  };

  // Filter concepts
  const filteredConcepts = concepts.filter((concept) => {
    const matchesSearch = concept.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         concept.description?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         concept.category.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesType = !typeFilter || concept.type === typeFilter;
    return matchesSearch && matchesType;
  });

  // Paginated concepts
  const paginatedConcepts = filteredConcepts.slice(
    page * rowsPerPage,
    page * rowsPerPage + rowsPerPage
  );

  const getTypeChip = (type: string) => {
    const typeConfig = conceptTypes.find(t => t.value === type);
    return (
      <Chip
        label={typeConfig?.label || type}
        color={typeConfig?.color || 'default'}
        size="small"
      />
    );
  };

  return (
    <Box>
      <Typography variant="h4" component="h1" sx={{ mb: 3 }}>
        Gestión de Conceptos Financieros
      </Typography>

      {errors.form && (
        <Alert severity="error" sx={{ mb: 3 }}>
          {errors.form}
        </Alert>
      )}

      {/* Filters and Actions */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Stack spacing={2}>
            <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems="center">
              <Box sx={{ flex: 1, minWidth: 200 }}>
                <TextField
                  fullWidth
                  label="Buscar conceptos"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  InputProps={{
                    startAdornment: <SearchIcon sx={{ mr: 1, color: 'text.secondary' }} />,
                  }}
                />
              </Box>
              <Box sx={{ minWidth: 200 }}>
                <FormControl fullWidth>
                  <InputLabel>Filtrar por tipo</InputLabel>
                  <Select
                    value={typeFilter}
                    onChange={(e) => setTypeFilter(e.target.value)}
                    label="Filtrar por tipo"
                  >
                    <MenuItem value="">Todos</MenuItem>
                    {conceptTypes.map((type) => (
                      <MenuItem key={type.value} value={type.value}>
                        {type.label}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>
              </Box>
              <Stack direction="row" spacing={2}>
                <Tooltip title="Actualizar">
                  <IconButton onClick={handleRefresh} disabled={loading.concepts}>
                    <RefreshIcon />
                  </IconButton>
                </Tooltip>
                <Button
                  variant="contained"
                  startIcon={<AddIcon />}
                  onClick={handleCreate}
                >
                  Nuevo Concepto
                </Button>
              </Stack>
            </Stack>
          </Stack>
        </CardContent>
      </Card>

      {/* Concepts Table */}
      <Card>
        <CardContent>
          <TableContainer component={Paper}>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Nombre</TableCell>
                  <TableCell>Tipo</TableCell>
                  <TableCell>Categoría</TableCell>
                  <TableCell>Descripción</TableCell>
                  <TableCell>Estado</TableCell>
                  <TableCell align="center">Acciones</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {loading.concepts ? (
                  <TableRow>
                    <TableCell colSpan={6} align="center">
                      Cargando conceptos...
                    </TableCell>
                  </TableRow>
                ) : paginatedConcepts.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} align="center">
                      No se encontraron conceptos
                    </TableCell>
                  </TableRow>
                ) : (
                  paginatedConcepts.map((concept) => (
                    <TableRow key={concept.id} hover>
                      <TableCell>
                        <Typography variant="body2" fontWeight="medium">
                          {concept.name}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        {getTypeChip(concept.type)}
                      </TableCell>
                      <TableCell>
                        <Chip
                          label={concept.category}
                          variant="outlined"
                          size="small"
                        />
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2" color="text.secondary">
                          {concept.description || '-'}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Chip
                          label={concept.is_active ? 'Activo' : 'Inactivo'}
                          color={concept.is_active ? 'success' : 'default'}
                          size="small"
                        />
                      </TableCell>
                      <TableCell align="center">
                        <Stack direction="row" spacing={1} justifyContent="center">
                          <Tooltip title="Editar">
                            <IconButton
                              size="small"
                              onClick={() => handleEdit(concept)}
                              disabled={loading.updating}
                            >
                              <EditIcon />
                            </IconButton>
                          </Tooltip>
                          <Tooltip title="Eliminar">
                            <IconButton
                              size="small"
                              color="error"
                              onClick={() => handleDelete(concept)}
                              disabled={loading.deleting}
                            >
                              <DeleteIcon />
                            </IconButton>
                          </Tooltip>
                        </Stack>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </TableContainer>

          <TablePagination
            component="div"
            count={filteredConcepts.length}
            page={page}
            onPageChange={(_, newPage) => setPage(newPage)}
            rowsPerPage={rowsPerPage}
            onRowsPerPageChange={(e) => {
              setRowsPerPage(parseInt(e.target.value, 10));
              setPage(0);
            }}
            rowsPerPageOptions={[5, 10, 25, 50]}
            labelRowsPerPage="Filas por página:"
            labelDisplayedRows={({ from, to, count }) =>
              `${from}-${to} de ${count !== -1 ? count : `más de ${to}`}`
            }
          />
        </CardContent>
      </Card>

      {/* Create/Edit Dialog */}
      <Dialog
        open={dialogOpen}
        onClose={handleCloseDialog}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>
          {editingConcept ? 'Editar Concepto' : 'Nuevo Concepto'}
        </DialogTitle>
        <form onSubmit={formik.handleSubmit}>
          <DialogContent>
            {submitError && (
              <Alert severity="error" sx={{ mb: 2 }}>
                {submitError}
              </Alert>
            )}
            
            <Stack spacing={2}>
              <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                <TextField
                  fullWidth
                  name="name"
                  label="Nombre"
                  value={formik.values.name}
                  onChange={formik.handleChange}
                  onBlur={formik.handleBlur}
                  error={formik.touched.name && Boolean(formik.errors.name)}
                  helperText={formik.touched.name && formik.errors.name}
                />
                <FormControl
                  fullWidth
                  error={formik.touched.type && Boolean(formik.errors.type)}
                >
                  <InputLabel>Tipo</InputLabel>
                  <Select
                    name="type"
                    value={formik.values.type}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    label="Tipo"
                  >
                    {conceptTypes.map((type) => (
                      <MenuItem key={type.value} value={type.value}>
                        {type.label}
                      </MenuItem>
                    ))}
                  </Select>
                  {formik.touched.type && formik.errors.type && (
                    <FormHelperText>{formik.errors.type}</FormHelperText>
                  )}
                </FormControl>
              </Stack>
              <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                <FormControl
                  fullWidth
                  error={formik.touched.category && Boolean(formik.errors.category)}
                >
                  <InputLabel>Categoría</InputLabel>
                  <Select
                    name="category"
                    value={formik.values.category}
                    onChange={formik.handleChange}
                    onBlur={formik.handleBlur}
                    label="Categoría"
                  >
                    {categories.map((category) => (
                      <MenuItem key={category} value={category}>
                        {category}
                      </MenuItem>
                    ))}
                  </Select>
                  {formik.touched.category && formik.errors.category && (
                    <FormHelperText>{formik.errors.category}</FormHelperText>
                  )}
                </FormControl>
                <FormControl fullWidth>
                  <InputLabel>Estado</InputLabel>
                  <Select
                    name="is_active"
                    value={formik.values.is_active}
                    onChange={formik.handleChange}
                    label="Estado"
                  >
                    <MenuItem value="true">Activo</MenuItem>
                    <MenuItem value="false">Inactivo</MenuItem>
                  </Select>
                </FormControl>
              </Stack>
              <TextField
                fullWidth
                name="description"
                label="Descripción"
                multiline
                rows={3}
                value={formik.values.description}
                onChange={formik.handleChange}
                onBlur={formik.handleBlur}
                error={formik.touched.description && Boolean(formik.errors.description)}
                helperText={formik.touched.description && formik.errors.description}
              />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={handleCloseDialog}>
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="contained"
              disabled={loading.creating || loading.updating || !formik.isValid}
            >
              {editingConcept ? 'Actualizar' : 'Crear'}
            </Button>
          </DialogActions>
        </form>
      </Dialog>
    </Box>
  );
};

export default ConceptManager;