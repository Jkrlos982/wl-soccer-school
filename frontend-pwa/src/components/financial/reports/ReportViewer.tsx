import React, { useState } from 'react';
import {
  Card,
  CardContent,
  CardHeader,
  Typography,
  Box,
  Stack,
  Chip,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Divider,
  Button,
  Menu,
  MenuItem,
  CircularProgress
} from '@mui/material';
import {
  TrendingUp,
  TrendingDown,
  AccountBalance,
  Assessment,
  FileDownload,
  PictureAsPdf,
  TableChart,
  Description
} from '@mui/icons-material';

interface ReportData {
  type: string;
  period: {
    start: string;
    end: string;
  };
  data: any;
}

interface ReportViewerProps {
  reportData: ReportData;
  onExport?: (format: 'excel' | 'pdf' | 'csv') => Promise<void>;
}

const ReportViewer: React.FC<ReportViewerProps> = ({ reportData, onExport }) => {
  const [exportMenuAnchor, setExportMenuAnchor] = useState<null | HTMLElement>(null);
  const [isExporting, setIsExporting] = useState(false);
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-CO', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  const getReportTitle = () => {
    switch (reportData.type) {
      case 'income-statement':
        return 'Estado de Resultados';
      case 'cash-flow':
        return 'Flujo de Caja';
      case 'balance-sheet':
        return 'Balance General';
      case 'summary':
        return 'Resumen Ejecutivo';
      default:
        return 'Reporte Financiero';
    }
  };

  const getReportIcon = () => {
    switch (reportData.type) {
      case 'income-statement':
        return <TrendingUp color="primary" />;
      case 'cash-flow':
        return <Assessment color="primary" />;
      case 'balance-sheet':
        return <AccountBalance color="primary" />;
      case 'summary':
        return <TrendingUp color="primary" />;
      default:
        return <Assessment color="primary" />;
    }
  };

  const handleExportClick = (event: React.MouseEvent<HTMLElement>) => {
    setExportMenuAnchor(event.currentTarget);
  };

  const handleExportClose = () => {
    setExportMenuAnchor(null);
  };

  const handleExport = async (format: 'excel' | 'pdf' | 'csv') => {
    if (!onExport) return;
    
    setIsExporting(true);
    setExportMenuAnchor(null);
    
    try {
      await onExport(format);
    } catch (error) {
      console.error('Error exporting report:', error);
    } finally {
      setIsExporting(false);
    }
  };

  const renderIncomeStatement = () => (
    <Stack spacing={3}>
      <Box>
        <Typography variant="h6" gutterBottom>
          Resumen Financiero
        </Typography>
        <TableContainer component={Paper} variant="outlined">
          <Table>
            <TableBody>
              <TableRow>
                <TableCell><strong>Ingresos Totales</strong></TableCell>
                <TableCell align="right">
                  <Typography color="success.main" fontWeight="bold">
                    {formatCurrency(reportData.data.totalIncome || 0)}
                  </Typography>
                </TableCell>
              </TableRow>
              <TableRow>
                <TableCell><strong>Gastos Totales</strong></TableCell>
                <TableCell align="right">
                  <Typography color="error.main" fontWeight="bold">
                    {formatCurrency(reportData.data.totalExpenses || 0)}
                  </Typography>
                </TableCell>
              </TableRow>
              <TableRow>
                <TableCell colSpan={2}><Divider /></TableCell>
              </TableRow>
              <TableRow>
                <TableCell><strong>Utilidad Neta</strong></TableCell>
                <TableCell align="right">
                  <Typography 
                    color={(reportData.data.netProfit || 0) >= 0 ? 'success.main' : 'error.main'}
                    fontWeight="bold"
                    variant="h6"
                  >
                    {formatCurrency(reportData.data.netProfit || 0)}
                  </Typography>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TableContainer>
      </Box>
    </Stack>
  );

  const renderCashFlow = () => (
    <Stack spacing={3}>
      <Box>
        <Typography variant="h6" gutterBottom>
          Flujo de Efectivo
        </Typography>
        <TableContainer component={Paper} variant="outlined">
          <Table>
            <TableBody>
              <TableRow>
                <TableCell><strong>Flujo de Efectivo Operacional</strong></TableCell>
                <TableCell align="right">
                  <Typography 
                    color={(reportData.data.cashFlow || 0) >= 0 ? 'success.main' : 'error.main'}
                    fontWeight="bold"
                  >
                    {formatCurrency(reportData.data.cashFlow || 0)}
                  </Typography>
                </TableCell>
              </TableRow>
              <TableRow>
                <TableCell><strong>Entradas de Efectivo</strong></TableCell>
                <TableCell align="right">
                  <Typography color="success.main">
                    {formatCurrency(reportData.data.totalIncome || 0)}
                  </Typography>
                </TableCell>
              </TableRow>
              <TableRow>
                <TableCell><strong>Salidas de Efectivo</strong></TableCell>
                <TableCell align="right">
                  <Typography color="error.main">
                    {formatCurrency(reportData.data.totalExpenses || 0)}
                  </Typography>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TableContainer>
      </Box>
    </Stack>
  );

  const renderBalanceSheet = () => (
    <Stack spacing={3}>
      <Box>
        <Typography variant="h6" gutterBottom>
          Posición Financiera
        </Typography>
        <TableContainer component={Paper} variant="outlined">
          <Table>
            <TableHead>
              <TableRow>
                <TableCell><strong>Concepto</strong></TableCell>
                <TableCell align="right"><strong>Monto</strong></TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              <TableRow>
                <TableCell>Activos Totales</TableCell>
                <TableCell align="right">
                  {formatCurrency(reportData.data.totalIncome || 0)}
                </TableCell>
              </TableRow>
              <TableRow>
                <TableCell>Pasivos Totales</TableCell>
                <TableCell align="right">
                  {formatCurrency(reportData.data.totalExpenses || 0)}
                </TableCell>
              </TableRow>
              <TableRow>
                <TableCell><strong>Patrimonio</strong></TableCell>
                <TableCell align="right">
                  <Typography fontWeight="bold">
                    {formatCurrency((reportData.data.totalIncome || 0) - (reportData.data.totalExpenses || 0))}
                  </Typography>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TableContainer>
      </Box>
    </Stack>
  );

  const renderSummary = () => (
    <Stack spacing={3}>
      <Box>
        <Typography variant="h6" gutterBottom>
          Métricas Clave
        </Typography>
        <Stack direction="row" spacing={2} flexWrap="wrap">
          <Chip
            icon={<TrendingUp />}
            label={`Ingresos: ${formatCurrency(reportData.data.totalIncome || 0)}`}
            color="success"
            variant="outlined"
          />
          <Chip
            icon={<TrendingDown />}
            label={`Gastos: ${formatCurrency(reportData.data.totalExpenses || 0)}`}
            color="error"
            variant="outlined"
          />
          <Chip
            icon={<AccountBalance />}
            label={`Utilidad: ${formatCurrency(reportData.data.netProfit || 0)}`}
            color={(reportData.data.netProfit || 0) >= 0 ? 'success' : 'error'}
            variant="outlined"
          />
        </Stack>
      </Box>
      
      <Box>
        <Typography variant="body1" color="text.secondary">
          Este resumen muestra las métricas financieras más importantes para el período seleccionado.
          Los datos se basan en las transacciones registradas en el sistema.
        </Typography>
      </Box>
    </Stack>
  );

  const renderReportContent = () => {
    switch (reportData.type) {
      case 'income-statement':
        return renderIncomeStatement();
      case 'cash-flow':
        return renderCashFlow();
      case 'balance-sheet':
        return renderBalanceSheet();
      case 'summary':
        return renderSummary();
      default:
        return (
          <Typography color="text.secondary">
            Tipo de reporte no reconocido: {reportData.type}
          </Typography>
        );
    }
  };

  return (
    <Card sx={{ mt: 3 }}>
      <CardHeader
        title={
          <Box display="flex" alignItems="center" justifyContent="space-between">
            <Box display="flex" alignItems="center" gap={1}>
              {getReportIcon()}
              <Typography variant="h5" component="h2">
                {getReportTitle()}
              </Typography>
            </Box>
            {onExport && (
              <Box>
                <Button
                  variant="outlined"
                  startIcon={isExporting ? <CircularProgress size={16} /> : <FileDownload />}
                  onClick={handleExportClick}
                  disabled={isExporting}
                  size="small"
                >
                  {isExporting ? 'Exportando...' : 'Exportar'}
                </Button>
                <Menu
                  anchorEl={exportMenuAnchor}
                  open={Boolean(exportMenuAnchor)}
                  onClose={handleExportClose}
                >
                  <MenuItem onClick={() => handleExport('excel')}>
                    <TableChart sx={{ mr: 1 }} fontSize="small" />
                    Excel
                  </MenuItem>
                  <MenuItem onClick={() => handleExport('pdf')}>
                    <PictureAsPdf sx={{ mr: 1 }} fontSize="small" />
                    PDF
                  </MenuItem>
                  <MenuItem onClick={() => handleExport('csv')}>
                    <Description sx={{ mr: 1 }} fontSize="small" />
                    CSV
                  </MenuItem>
                </Menu>
              </Box>
            )}
          </Box>
        }
        subheader={
          <Typography variant="body2" color="text.secondary">
            Período: {formatDate(reportData.period.start)} - {formatDate(reportData.period.end)}
          </Typography>
        }
      />
      <CardContent>
        {renderReportContent()}
      </CardContent>
    </Card>
  );
};

export default ReportViewer;