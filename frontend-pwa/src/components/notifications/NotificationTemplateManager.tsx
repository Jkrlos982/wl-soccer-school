import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControl,
  FormControlLabel,
  Grid,
  IconButton,
  InputLabel,
  MenuItem,
  Paper,
  Select,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TextField,
  Typography,
  Alert,
  Snackbar,
  Tooltip,
} from '@mui/material';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  ContentCopy as CopyIcon,
  Preview as PreviewIcon,
  Send as SendIcon
} from '@mui/icons-material';
import { RootState, AppDispatch } from '../../store';
import {
  fetchNotificationTemplates,
  createNotificationTemplate,
  updateNotificationTemplate,
  deleteNotificationTemplate,
  duplicateNotificationTemplate
} from '../../store/notificationSlice';
import {
  NotificationTemplate,
  NotificationType,
  NotificationTemplateForm
} from '../../types/notification';

interface NotificationTemplateManagerProps {
  onTemplateSelect?: (template: NotificationTemplate) => void;
}

const NotificationTemplateManager: React.FC<NotificationTemplateManagerProps> = ({
  onTemplateSelect
}) => {
  const dispatch = useDispatch<AppDispatch>();
  const { data: templates, loading, error, total } = useSelector(
    (state: RootState) => state.notification.templates
  );

  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [openDialog, setOpenDialog] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState<NotificationTemplate | null>(null);
  const [previewDialog, setPreviewDialog] = useState(false);
  const [previewContent, setPreviewContent] = useState('');
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' as 'success' | 'error' });

  const [formData, setFormData] = useState<NotificationTemplateForm>({
    name: '',
    type: NotificationType.PAYMENT_REMINDER,
    subject: '',
    content: '',
    isActive: true
  });

  useEffect(() => {
    dispatch(fetchNotificationTemplates({ page: page + 1, limit: rowsPerPage }));
  }, [dispatch, page, rowsPerPage]);

  const handleChangePage = (event: unknown, newPage: number) => {
    setPage(newPage);
  };

  const handleChangeRowsPerPage = (event: React.ChangeEvent<HTMLInputElement>) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  };

  const handleOpenDialog = (template?: NotificationTemplate) => {
    if (template) {
      setEditingTemplate(template);
      setFormData({
        name: template.name,
        type: template.type,
        subject: template.subject,
        content: template.content,
        isActive: template.isActive
      });
    } else {
      setEditingTemplate(null);
      setFormData({
        name: '',
        type: NotificationType.PAYMENT_REMINDER,
        subject: '',
        content: '',
        isActive: true
      });
    }
    setOpenDialog(true);
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setEditingTemplate(null);
  };

  const handleSubmit = async () => {
    try {
      if (editingTemplate) {
        await dispatch(updateNotificationTemplate({
          templateId: editingTemplate.id,
          data: formData
        })).unwrap();
        setSnackbar({ open: true, message: 'Plantilla actualizada exitosamente', severity: 'success' });
      } else {
        await dispatch(createNotificationTemplate(formData)).unwrap();
        setSnackbar({ open: true, message: 'Plantilla creada exitosamente', severity: 'success' });
      }
      handleCloseDialog();
      dispatch(fetchNotificationTemplates({ page: page + 1, limit: rowsPerPage }));
    } catch (error) {
      setSnackbar({ open: true, message: 'Error al guardar la plantilla', severity: 'error' });
    }
  };

  const handleDelete = async (id: string) => {
    if (window.confirm('¿Está seguro de que desea eliminar esta plantilla?')) {
      try {
        await dispatch(deleteNotificationTemplate(id)).unwrap();
        setSnackbar({ open: true, message: 'Plantilla eliminada exitosamente', severity: 'success' });
        dispatch(fetchNotificationTemplates({ page: page + 1, limit: rowsPerPage }));
      } catch (error) {
        setSnackbar({ open: true, message: 'Error al eliminar la plantilla', severity: 'error' });
      }
    }
  };

  const handleDuplicate = async (id: string) => {
    try {
      await dispatch(duplicateNotificationTemplate({ templateId: id, name: `Copia de ${templates.find(t => t.id === id)?.name || 'Plantilla'}` })).unwrap();
      setSnackbar({ open: true, message: 'Plantilla duplicada exitosamente', severity: 'success' });
      dispatch(fetchNotificationTemplates({ page: page + 1, limit: rowsPerPage }));
    } catch (error) {
      setSnackbar({ open: true, message: 'Error al duplicar la plantilla', severity: 'error' });
    }
  };

  const handlePreview = async (template: NotificationTemplate) => {
    // Simulamos una vista previa con datos de ejemplo
    const mockContent = template.content
      .replace(/{{student\.name}}/g, 'Juan Pérez')
      .replace(/{{student\.email}}/g, 'juan.perez@email.com')
      .replace(/{{accountReceivable\.amount}}/g, '$150,000')
      .replace(/{{accountReceivable\.dueDate}}/g, '15/02/2024')
      .replace(/{{accountReceivable\.concept}}/g, 'Mensualidad Febrero')
      .replace(/{{school\.name}}/g, 'Colegio Ejemplo');
    
    setPreviewContent(mockContent);
    setPreviewDialog(true);
  };

  const getTypeLabel = (type: NotificationType) => {
    const labels = {
      [NotificationType.PAYMENT_REMINDER]: 'Recordatorio de Pago',
      [NotificationType.PAYMENT_CONFIRMATION]: 'Confirmación de Pago',
      [NotificationType.OVERDUE_NOTIFICATION]: 'Notificación de Vencimiento',
      [NotificationType.PAYMENT_RECEIVED]: 'Pago Recibido',
      [NotificationType.PAYMENT_PLAN_REMINDER]: 'Recordatorio Plan de Pago',
      [NotificationType.LATE_FEE_NOTIFICATION]: 'Notificación de Mora'
    };
    return labels[type] || type;
  };

  const getTypeColor = (type: NotificationType): 'primary' | 'secondary' | 'error' | 'warning' | 'info' | 'success' | 'default' => {
    const colors: Record<NotificationType, 'primary' | 'secondary' | 'error' | 'warning' | 'info' | 'success' | 'default'> = {
      [NotificationType.PAYMENT_REMINDER]: 'primary',
      [NotificationType.PAYMENT_CONFIRMATION]: 'success',
      [NotificationType.OVERDUE_NOTIFICATION]: 'error',
      [NotificationType.PAYMENT_RECEIVED]: 'success',
      [NotificationType.PAYMENT_PLAN_REMINDER]: 'info',
      [NotificationType.LATE_FEE_NOTIFICATION]: 'warning'
    };
    return colors[type] || 'default';
  };

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h5" component="h2">
          Gestión de Plantillas de Notificación
        </Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={() => handleOpenDialog()}
        >
          Nueva Plantilla
        </Button>
      </Box>

      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <Card>
        <CardContent>
          <TableContainer component={Paper}>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Nombre</TableCell>
                  <TableCell>Tipo</TableCell>
                  <TableCell>Asunto</TableCell>
                  <TableCell>Estado</TableCell>
                  <TableCell>Fecha Creación</TableCell>
                  <TableCell align="center">Acciones</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {templates.map((template) => (
                  <TableRow key={template.id}>
                    <TableCell>
                      <Typography variant="body2" fontWeight="medium">
                        {template.name}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={getTypeLabel(template.type)}
                        color={getTypeColor(template.type) as any}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2" noWrap sx={{ maxWidth: 200 }}>
                        {template.subject}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={template.isActive ? 'Activa' : 'Inactiva'}
                        color={template.isActive ? 'success' : 'default'}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      {new Date(template.createdAt).toLocaleDateString()}
                    </TableCell>
                    <TableCell align="center">
                      <Box display="flex" gap={1}>
                        <Tooltip title="Vista Previa">
                          <IconButton
                            size="small"
                            onClick={() => handlePreview(template)}
                          >
                            <PreviewIcon />
                          </IconButton>
                        </Tooltip>
                        <Tooltip title="Editar">
                          <IconButton
                            size="small"
                            onClick={() => handleOpenDialog(template)}
                          >
                            <EditIcon />
                          </IconButton>
                        </Tooltip>
                        <Tooltip title="Duplicar">
                          <IconButton
                            size="small"
                            onClick={() => handleDuplicate(template.id)}
                          >
                            <CopyIcon />
                          </IconButton>
                        </Tooltip>
                        <Tooltip title="Eliminar">
                          <IconButton
                            size="small"
                            color="error"
                            onClick={() => handleDelete(template.id)}
                          >
                            <DeleteIcon />
                          </IconButton>
                        </Tooltip>
                        {onTemplateSelect && (
                          <Tooltip title="Seleccionar">
                            <IconButton
                              size="small"
                              color="primary"
                              onClick={() => onTemplateSelect(template)}
                            >
                              <SendIcon />
                            </IconButton>
                          </Tooltip>
                        )}
                      </Box>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>

          <TablePagination
            rowsPerPageOptions={[5, 10, 25]}
            component="div"
            count={total}
            rowsPerPage={rowsPerPage}
            page={page}
            onPageChange={handleChangePage}
            onRowsPerPageChange={handleChangeRowsPerPage}
          />
        </CardContent>
      </Card>

      {/* Dialog para crear/editar plantilla */}
      <Dialog open={openDialog} onClose={handleCloseDialog} maxWidth="md" fullWidth>
        <DialogTitle>
          {editingTemplate ? 'Editar Plantilla' : 'Nueva Plantilla'}
        </DialogTitle>
        <DialogContent>
          <Box sx={{ mt: 2, display: 'flex', flexDirection: 'column', gap: 2 }}>
            <Box sx={{ display: 'flex', gap: 2 }}>
              <TextField
                fullWidth
                label="Nombre"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
              />
              <FormControl fullWidth required>
                <InputLabel>Tipo</InputLabel>
                <Select
                  value={formData.type}
                  label="Tipo"
                  onChange={(e) => setFormData({ ...formData, type: e.target.value as NotificationType })}
                >
                  {Object.values(NotificationType).map((type) => (
                    <MenuItem key={type} value={type}>
                      {getTypeLabel(type)}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Box>
            <TextField
              fullWidth
              label="Asunto"
              value={formData.subject}
              onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
              required
            />
            <TextField
              fullWidth
              label="Contenido"
              value={formData.content}
              onChange={(e) => setFormData({ ...formData, content: e.target.value })}
              multiline
              rows={8}
              required
              helperText="Puede usar variables como {{student.name}}, {{accountReceivable.amount}}, etc."
            />
            <FormControlLabel
              control={
                <Switch
                  checked={formData.isActive}
                  onChange={(e) => setFormData({ ...formData, isActive: e.target.checked })}
                />
              }
              label="Plantilla activa"
            />
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseDialog}>Cancelar</Button>
          <Button onClick={handleSubmit} variant="contained">
            {editingTemplate ? 'Actualizar' : 'Crear'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Dialog para vista previa */}
      <Dialog open={previewDialog} onClose={() => setPreviewDialog(false)} maxWidth="md" fullWidth>
        <DialogTitle>Vista Previa de Plantilla</DialogTitle>
        <DialogContent>
          <Box sx={{ mt: 2 }}>
            <Typography variant="body1" component="div" sx={{ whiteSpace: 'pre-wrap' }}>
              {previewContent}
            </Typography>
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setPreviewDialog(false)}>Cerrar</Button>
        </DialogActions>
      </Dialog>

      {/* Snackbar para notificaciones */}
      <Snackbar
        open={snackbar.open}
        autoHideDuration={6000}
        onClose={() => setSnackbar({ ...snackbar, open: false })}
      >
        <Alert
          onClose={() => setSnackbar({ ...snackbar, open: false })}
          severity={snackbar.severity}
          sx={{ width: '100%' }}
        >
          {snackbar.message}
        </Alert>
      </Snackbar>
    </Box>
  );
};

export default NotificationTemplateManager;