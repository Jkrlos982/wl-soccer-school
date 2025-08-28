import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Chip,
  IconButton,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Button,
  Pagination,
  Tooltip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Grid,
  Divider
} from '@mui/material';
import {
  Visibility as ViewIcon,
  Refresh as RefreshIcon,
  FilterList as FilterIcon,
  Search as SearchIcon,
  Email as EmailIcon,
  Sms as SmsIcon,
  WhatsApp as WhatsAppIcon
} from '@mui/icons-material';
import { RootState } from '../../store';
import {
  fetchNotificationHistory,
  setFilters,
  clearFilters,
  setSelectedHistoryItem
} from '../../store/notificationSlice';
import {
  NotificationHistory as NotificationHistoryType,
  NotificationChannel,
  NotificationStatus
} from '../../types/notification';

interface NotificationHistoryProps {
  schoolId?: string;
}

const NotificationHistory: React.FC<NotificationHistoryProps> = ({ schoolId }) => {
  const dispatch = useDispatch();
  const { data: history, total, loading, error } = useSelector(
    (state: RootState) => state.notification.history
  );
  const filters = useSelector((state: RootState) => state.notification.filters);
  const selectedItem = useSelector((state: RootState) => state.notification.selectedHistoryItem);
  
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<NotificationStatus | ''>('');
  const [channelFilter, setChannelFilter] = useState<NotificationChannel | ''>('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [showFilters, setShowFilters] = useState(false);
  const [detailDialog, setDetailDialog] = useState(false);
  
  const itemsPerPage = 10;

  useEffect(() => {
    loadHistory();
  }, [dispatch, currentPage, filters]);

  const loadHistory = () => {
    dispatch(fetchNotificationHistory({
      page: currentPage,
      limit: itemsPerPage,
      ...filters
    }) as any);
  };

  const handleSearch = () => {
    const newFilters = {
      search: searchTerm,
      status: statusFilter || undefined,
      channel: channelFilter || undefined,
      dateFrom: dateFrom || undefined,
      dateTo: dateTo || undefined
    };
    
    dispatch(setFilters(newFilters));
    setCurrentPage(1);
  };

  const handleClearFilters = () => {
    setSearchTerm('');
    setStatusFilter('');
    setChannelFilter('');
    setDateFrom('');
    setDateTo('');
    dispatch(clearFilters());
    setCurrentPage(1);
  };

  const handleViewDetail = (item: NotificationHistoryType) => {
    dispatch(setSelectedHistoryItem(item));
    setDetailDialog(true);
  };

  const getStatusColor = (status: NotificationStatus) => {
    switch (status) {
      case 'sent':
        return 'success';
      case 'failed':
        return 'error';
      case 'pending':
        return 'warning';
      case 'delivered':
        return 'info';
      default:
        return 'default';
    }
  };

  const getStatusLabel = (status: NotificationStatus) => {
    switch (status) {
      case 'sent':
        return 'Enviado';
      case 'failed':
        return 'Fallido';
      case 'pending':
        return 'Pendiente';
      case 'delivered':
        return 'Entregado';
      default:
        return status;
    }
  };

  const getChannelIcon = (channel: NotificationChannel) => {
    switch (channel) {
      case 'email':
        return <EmailIcon fontSize="small" />;
      case 'sms':
        return <SmsIcon fontSize="small" />;
      case 'whatsapp':
        return <WhatsAppIcon fontSize="small" />;
      default:
        return null;
    }
  };

  const getChannelLabel = (channel: NotificationChannel) => {
    switch (channel) {
      case 'email':
        return 'Email';
      case 'sms':
        return 'SMS';
      case 'whatsapp':
        return 'WhatsApp';
      default:
        return channel;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-ES', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const totalPages = Math.ceil(total / itemsPerPage);

  if (loading && history.length === 0) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <Typography>Cargando historial...</Typography>
      </Box>
    );
  }

  return (
    <Box>
      {/* Filtros */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Box display="flex" justifyContent="space-between" alignItems="center" mb={2}>
            <Typography variant="h6">
              Historial de Notificaciones
            </Typography>
            <Box display="flex" gap={1}>
              <Button
                variant="outlined"
                startIcon={<FilterIcon />}
                onClick={() => setShowFilters(!showFilters)}
              >
                Filtros
              </Button>
              <Button
                variant="outlined"
                startIcon={<RefreshIcon />}
                onClick={loadHistory}
                disabled={loading}
              >
                Actualizar
              </Button>
            </Box>
          </Box>
          
          {showFilters && (
            <Box>
              <Divider sx={{ mb: 2 }} />
              <Box display="flex" flexWrap="wrap" gap={2} alignItems="center">
                <Box sx={{ minWidth: 200, flex: 1 }}>
                  <TextField
                    fullWidth
                    label="Buscar"
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    placeholder="Buscar por destinatario o mensaje..."
                  />
                </Box>
                
                <Box sx={{ minWidth: 150 }}>
                  <FormControl fullWidth>
                    <InputLabel>Estado</InputLabel>
                    <Select
                      value={statusFilter}
                      onChange={(e) => setStatusFilter(e.target.value as NotificationStatus)}
                    >
                      <MenuItem value="">Todos</MenuItem>
                      <MenuItem value="sent">Enviado</MenuItem>
                      <MenuItem value="delivered">Entregado</MenuItem>
                      <MenuItem value="failed">Fallido</MenuItem>
                      <MenuItem value="pending">Pendiente</MenuItem>
                    </Select>
                  </FormControl>
                </Box>
                
                <Box sx={{ minWidth: 150 }}>
                  <FormControl fullWidth>
                    <InputLabel>Canal</InputLabel>
                    <Select
                      value={channelFilter}
                      onChange={(e) => setChannelFilter(e.target.value as NotificationChannel)}
                    >
                      <MenuItem value="">Todos</MenuItem>
                      <MenuItem value="email">Email</MenuItem>
                      <MenuItem value="sms">SMS</MenuItem>
                      <MenuItem value="whatsapp">WhatsApp</MenuItem>
                    </Select>
                  </FormControl>
                </Box>
                
                <Box sx={{ minWidth: 150 }}>
                  <TextField
                    fullWidth
                    label="Desde"
                    type="date"
                    value={dateFrom}
                    onChange={(e) => setDateFrom(e.target.value)}
                    InputLabelProps={{ shrink: true }}
                  />
                </Box>
                
                <Box sx={{ minWidth: 150 }}>
                  <TextField
                    fullWidth
                    label="Hasta"
                    type="date"
                    value={dateTo}
                    onChange={(e) => setDateTo(e.target.value)}
                    InputLabelProps={{ shrink: true }}
                  />
                </Box>
                
                <Box>
                  <Box display="flex" gap={1}>
                    <Button
                      variant="contained"
                      onClick={handleSearch}
                      startIcon={<SearchIcon />}
                      size="small"
                    >
                      Buscar
                    </Button>
                    <Button
                      variant="outlined"
                      onClick={handleClearFilters}
                      size="small"
                    >
                      Limpiar
                    </Button>
                  </Box>
                </Box>
              </Box>
            </Box>
          )}
        </CardContent>
      </Card>

      {/* Tabla de Historial */}
      <Card>
        <CardContent>
          <TableContainer component={Paper}>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Fecha/Hora</TableCell>
                  <TableCell>Destinatario</TableCell>
                  <TableCell>Canal</TableCell>
                  <TableCell>Tipo</TableCell>
                  <TableCell>Estado</TableCell>
                  <TableCell>Mensaje</TableCell>
                  <TableCell align="center">Acciones</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {history.map((item) => (
                  <TableRow key={item.id} hover>
                    <TableCell>
                      <Typography variant="body2">
                        {formatDate(item.sentAt)}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Box>
                        <Typography variant="body2" fontWeight="medium">
                          {item.recipient}
                        </Typography>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Box display="flex" alignItems="center" gap={1}>
                        {getChannelIcon(item.channel)}
                        <Typography variant="body2">
                          {getChannelLabel(item.channel)}
                        </Typography>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">
                        {item.type.replace('_', ' ').toUpperCase()}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Chip
                        label={getStatusLabel(item.status)}
                        color={getStatusColor(item.status) as any}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      <Typography
                        variant="body2"
                        sx={{
                          maxWidth: 200,
                          overflow: 'hidden',
                          textOverflow: 'ellipsis',
                          whiteSpace: 'nowrap'
                        }}
                      >
                        {item.content}
                      </Typography>
                    </TableCell>
                    <TableCell align="center">
                      <Tooltip title="Ver detalles">
                        <IconButton
                          size="small"
                          onClick={() => handleViewDetail(item)}
                        >
                          <ViewIcon />
                        </IconButton>
                      </Tooltip>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
          
          {history.length === 0 && !loading && (
            <Box textAlign="center" py={4}>
              <Typography color="text.secondary">
                No se encontraron notificaciones
              </Typography>
            </Box>
          )}
          
          {totalPages > 1 && (
            <Box display="flex" justifyContent="center" mt={3}>
              <Pagination
                count={totalPages}
                page={currentPage}
                onChange={(_, page) => setCurrentPage(page)}
                color="primary"
              />
            </Box>
          )}
        </CardContent>
      </Card>

      {/* Dialog de Detalles */}
      <Dialog
        open={detailDialog}
        onClose={() => setDetailDialog(false)}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>
          Detalles de Notificación
        </DialogTitle>
        <DialogContent>
          {selectedItem && (
            <Box>
              <Box display="flex" flexWrap="wrap" gap={2}>
                <Box sx={{ flex: 1, minWidth: 300 }}>
                  <Typography variant="subtitle2" gutterBottom>
                    Información General
                  </Typography>
                  <Box mb={2}>
                    <Typography variant="body2" color="text.secondary">
                      ID: {selectedItem.id}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Fecha: {formatDate(selectedItem.sentAt)}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Tipo: {selectedItem.type.replace('_', ' ').toUpperCase()}
                    </Typography>
                  </Box>
                </Box>
                
                <Box sx={{ flex: 1, minWidth: 300 }}>
                  <Typography variant="subtitle2" gutterBottom>
                    Destinatario
                  </Typography>
                  <Box mb={2}>
                    <Typography variant="body2">
                    {selectedItem.recipient}
                  </Typography>
                  </Box>
                </Box>
               </Box>
               
               <Box sx={{ mt: 2 }}>
                  <Typography variant="subtitle2" gutterBottom>
                    Canal y Estado
                  </Typography>
                  <Box display="flex" gap={2} mb={2}>
                    <Box display="flex" alignItems="center" gap={1}>
                      {getChannelIcon(selectedItem.channel)}
                      <Typography variant="body2">
                        {getChannelLabel(selectedItem.channel)}
                      </Typography>
                    </Box>
                    <Chip
                      label={getStatusLabel(selectedItem.status)}
                      color={getStatusColor(selectedItem.status) as any}
                      size="small"
                    />
                  </Box>
                
                <Box sx={{ mt: 2 }}>
                  <Typography variant="subtitle2" gutterBottom>
                    Mensaje
                  </Typography>
                  <Paper sx={{ p: 2, bgcolor: 'grey.50' }}>
                    <Typography variant="body2">
                      {selectedItem.content}
                    </Typography>
                  </Paper>
                </Box>
                
                {selectedItem.errorMessage && (
                  <Box sx={{ mt: 2 }}>
                    <Typography variant="subtitle2" gutterBottom color="error">
                      Error
                    </Typography>
                    <Paper sx={{ p: 2, bgcolor: 'error.50' }}>
                      <Typography variant="body2" color="error">
                        {selectedItem.errorMessage}
                      </Typography>
                    </Paper>
                  </Box>
                )}</Box>
            </Box>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDetailDialog(false)}>
            Cerrar
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default NotificationHistory;