import React, { useState } from 'react';
import {
  Container,
  Typography,
  Box,
  Fade
} from '@mui/material';
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

const FinancialReports: React.FC = () => {
  const [reportData, setReportData] = useState<ReportData | null>(null);

  const handleReportGenerated = (data: ReportData) => {
    setReportData(data);
  };

  return (
    <Container maxWidth="lg" sx={{ py: 4 }}>
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
        <Fade in={true} timeout={500}>
          <Box>
            <ReportViewer reportData={reportData} />
          </Box>
        </Fade>
      )}
    </Container>
  );
};

export default FinancialReports;