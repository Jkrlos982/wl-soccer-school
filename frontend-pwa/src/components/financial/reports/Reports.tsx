import React, { useState } from 'react';
import { Box, Container, Typography } from '@mui/material';
import ReportGenerator from './ReportGenerator';
import ReportViewer from './ReportViewer';

interface ReportData {
  type: string;
  period: {
    start: string;
    end: string;
  };
  data: any;
}

const Reports: React.FC = () => {
  const [reportData, setReportData] = useState<ReportData | null>(null);
  const [exportHandler, setExportHandler] = useState<((format: 'excel' | 'pdf' | 'csv') => Promise<void>) | null>(null);

  const handleReportGenerated = (
    data: ReportData, 
    exportFn: (format: 'excel' | 'pdf' | 'csv') => Promise<void>
  ) => {
    setReportData(data);
    setExportHandler(() => exportFn);
  };

  return (
    <Container maxWidth="lg" sx={{ py: 3 }}>
      <Box mb={4}>
        <Typography variant="h4" component="h1" gutterBottom>
          Reportes Financieros
        </Typography>
        <Typography variant="body1" color="text.secondary">
          Genera y visualiza reportes financieros detallados para análisis y toma de decisiones.
        </Typography>
      </Box>

      <ReportGenerator onReportGenerated={handleReportGenerated} />
      
      {reportData && (
        <Box mt={4}>
          <ReportViewer 
            reportData={reportData} 
            onExport={exportHandler || undefined}
          />
        </Box>
      )}
    </Container>
  );
};

export default Reports;