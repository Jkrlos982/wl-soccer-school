import React, { useState } from 'react';
import {
  Card,
  CardContent,
  CardHeader,
  Button,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Typography,
  Box,
  Stack,
  Chip,
  CircularProgress,
  Alert,
  SelectChangeEvent
} from '@mui/material';
import {
  CalendarToday,
  Description,
  TrendingUp,
  BarChart,
  GetApp
} from '@mui/icons-material';
import { FinancialService } from '../../../services/financialService';

type ReportType = 'income-statement' | 'cash-flow' | 'balance-sheet' | 'summary';

interface ReportFilters {
  reportType: ReportType;
  startDate: string;
  endDate: string;
  accountId?: string;
}

interface ReportGeneratorProps {
  onReportGenerated?: (data: any, exportHandler: (format: 'excel' | 'pdf' | 'csv') => Promise<void>) => void;
}

const ReportGenerator: React.FC<ReportGeneratorProps> = ({ onReportGenerated }) => {
  const [filters, setFilters] = useState<ReportFilters>({
    reportType: 'income-statement',
    startDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    endDate: new Date().toISOString().split('T')[0],
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string>('');
  const [success, setSuccess] = useState<string>('');

  const reportTypes = [
    {
      value: 'income-statement' as ReportType,
      label: 'Estado de Resultados',
      description: 'Ingresos, gastos y utilidad neta',
      icon: TrendingUp,
    },
    {
      value: 'cash-flow' as ReportType,
      label: 'Flujo de Caja',
      description: 'Movimientos de efectivo por actividades',
      icon: BarChart,
    },
    {
      value: 'balance-sheet' as ReportType,
      label: 'Balance General',
      description: 'Activos, pasivos y patrimonio',
      icon: Description,
    },
    {
      value: 'summary' as ReportType,
      label: 'Resumen Ejecutivo',
      description: 'Métricas clave del período',
      icon: CalendarToday,
    },
  ];

  const handleFilterChange = (field: keyof ReportFilters, value: string) => {
    setFilters(prev => ({ ...prev, [field]: value }));
    setError('');
    setSuccess('');
  };

  const handleReportTypeChange = (event: SelectChangeEvent<ReportType>) => {
    handleFilterChange('reportType', event.target.value as ReportType);
  };

  const generateReport = async () => {
    if (!filters.startDate || !filters.endDate) {
      setError('Por favor selecciona las fechas de inicio y fin');
      return;
    }

    if (new Date(filters.startDate) > new Date(filters.endDate)) {
      setError('La fecha de inicio debe ser anterior a la fecha de fin');
      return;
    }

    setLoading(true);
    setError('');
    setSuccess('');

    try {
      // Simular llamada a la API del reporte financiero
      const reportData = {
        type: filters.reportType,
        period: {
          start: filters.startDate,
          end: filters.endDate
        },
        data: {
          // Datos simulados del reporte
          totalIncome: 150000,
          totalExpenses: 120000,
          netProfit: 30000,
          cashFlow: 25000
        }
      };

      // Simular delay de API
      await new Promise(resolve => setTimeout(resolve, 1500));

      setSuccess('Reporte generado exitosamente');
      onReportGenerated?.(reportData, exportReport);
    } catch (error) {
      console.error('Error generating report:', error);
      setError('Error al generar el reporte. Por favor intenta nuevamente.');
    } finally {
      setLoading(false);
    }
  };

  const exportReport = async (format: 'excel' | 'pdf' | 'csv') => {
    if (!filters.startDate || !filters.endDate) {
      setError('Por favor genera un reporte antes de exportar');
      return;
    }

    setLoading(true);
    setError('');
    
    try {
      let blob: Blob;
      const params = {
        start_date: filters.startDate,
        end_date: filters.endDate,
        format
      };

      // Call the appropriate export method based on report type
      switch (filters.reportType) {
        case 'income-statement':
          blob = await FinancialService.exportIncomeStatement(params);
          break;
        case 'cash-flow':
          blob = await FinancialService.exportCashFlow(params);
          break;
        case 'balance-sheet':
          blob = await FinancialService.exportBalanceSheet(params);
          break;
        case 'summary':
          blob = await FinancialService.exportSummary(params);
          break;
        default:
          throw new Error('Tipo de reporte no válido');
      }

      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      
      const reportTypeNames = {
        'income-statement': 'estado-resultados',
        'cash-flow': 'flujo-caja',
        'balance-sheet': 'balance-general',
        'summary': 'resumen-ejecutivo'
      };
      
      const fileName = `${reportTypeNames[filters.reportType]}_${filters.startDate}_${filters.endDate}.${format === 'excel' ? 'xlsx' : format}`;
      link.download = fileName;
      
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      setSuccess(`Reporte exportado exitosamente en formato ${format.toUpperCase()}`);
    } catch (error) {
      console.error('Error exporting report:', error);
      setError('Error al exportar el reporte. Por favor intenta nuevamente.');
    } finally {
      setLoading(false);
    }
  };

  const selectedReportType = reportTypes.find(type => type.value === filters.reportType);
  const IconComponent = selectedReportType?.icon || Description;

  return (
    <Card sx={{ maxWidth: 800, mx: 'auto', mt: 2 }}>
      <CardHeader
        title={
          <Box display="flex" alignItems="center" gap={1}>
            <IconComponent color="primary" />
            <Typography variant="h5" component="h2">
              Generador de Reportes Financieros
            </Typography>
          </Box>
        }
        subheader="Genera reportes financieros detallados para análisis y toma de decisiones"
      />
      <CardContent>
        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}
        {success && (
          <Alert severity="success" sx={{ mb: 2 }}>
            {success}
          </Alert>
        )}

        <Stack spacing={3}>
          {/* Tipo de Reporte */}
          <Box>
            <FormControl fullWidth>
              <InputLabel>Tipo de Reporte</InputLabel>
              <Select
                value={filters.reportType}
                label="Tipo de Reporte"
                onChange={handleReportTypeChange}
              >
                {reportTypes.map((type) => {
                  const TypeIcon = type.icon;
                  return (
                    <MenuItem key={type.value} value={type.value}>
                      <Box display="flex" alignItems="center" gap={1}>
                        <TypeIcon fontSize="small" />
                        <Box>
                          <Typography variant="body1">{type.label}</Typography>
                          <Typography variant="caption" color="text.secondary">
                            {type.description}
                          </Typography>
                        </Box>
                      </Box>
                    </MenuItem>
                  );
                })}
              </Select>
            </FormControl>
          </Box>

          {/* Filtros de Fecha */}
          <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
            <Box flex={1}>
            <TextField
              fullWidth
              type="date"
              label="Fecha de Inicio"
              value={filters.startDate}
              onChange={(e) => handleFilterChange('startDate', e.target.value)}
              InputLabelProps={{ shrink: true }}
            />
            </Box>
            <Box flex={1}>
            <TextField
              fullWidth
              type="date"
              label="Fecha de Fin"
              value={filters.endDate}
              onChange={(e) => handleFilterChange('endDate', e.target.value)}
              InputLabelProps={{ shrink: true }}
            />
            </Box>
          </Stack>

          {/* Información del Reporte Seleccionado */}
          {selectedReportType && (
            <Box>
              <Box
                sx={{
                  p: 2,
                  bgcolor: 'primary.50',
                  borderRadius: 1,
                  border: '1px solid',
                  borderColor: 'primary.200'
                }}
              >
                <Box display="flex" alignItems="center" gap={1} mb={1}>
                  <IconComponent color="primary" />
                  <Typography variant="h6" color="primary">
                    {selectedReportType.label}
                  </Typography>
                </Box>
                <Typography variant="body2" color="text.secondary">
                  {selectedReportType.description}
                </Typography>
              </Box>
            </Box>
          )}

          {/* Botones de Acción */}
          <Box>
            <Box display="flex" gap={2} flexWrap="wrap">
              <Button
                variant="contained"
                onClick={generateReport}
                disabled={loading}
                startIcon={loading ? <CircularProgress size={20} /> : <BarChart />}
                sx={{ minWidth: 150 }}
              >
                {loading ? 'Generando...' : 'Generar Reporte'}
              </Button>

              <Button
                variant="outlined"
                onClick={() => exportReport('excel')}
                disabled={loading}
                startIcon={<GetApp />}
              >
                Excel
              </Button>

              <Button
                variant="outlined"
                onClick={() => exportReport('pdf')}
                disabled={loading}
                startIcon={<GetApp />}
              >
                PDF
              </Button>

              <Button
                variant="outlined"
                onClick={() => exportReport('csv')}
                disabled={loading}
                startIcon={<GetApp />}
              >
                CSV
              </Button>
            </Box>
          </Box>
        </Stack>
      </CardContent>
    </Card>
  );
};

export default ReportGenerator;