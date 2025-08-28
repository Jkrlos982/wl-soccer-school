import React, { useState } from 'react';
import {
  Box,
  Container,
  Typography,
  Tabs,
  Tab,
  Paper,
  Breadcrumbs,
  Link
} from '@mui/material';
import {
  Notifications as NotificationsIcon,
  Description as TemplateIcon,
  Settings as SettingsIcon,
  History as HistoryIcon,
  Home as HomeIcon
} from '@mui/icons-material';
import { useParams } from 'react-router-dom';

// Importar componentes de notificaciones
import NotificationTemplateManager from '../components/notifications/NotificationTemplateManager';
import NotificationSettings from '../components/notifications/NotificationSettings';
import NotificationHistory from '../components/notifications/NotificationHistory';

interface TabPanelProps {
  children?: React.ReactNode;
  index: number;
  value: number;
}

function TabPanel(props: TabPanelProps) {
  const { children, value, index, ...other } = props;

  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`notification-tabpanel-${index}`}
      aria-labelledby={`notification-tab-${index}`}
      {...other}
    >
      {value === index && (
        <Box sx={{ py: 3 }}>
          {children}
        </Box>
      )}
    </div>
  );
}

function a11yProps(index: number) {
  return {
    id: `notification-tab-${index}`,
    'aria-controls': `notification-tabpanel-${index}`,
  };
}

const NotificationManagementPage: React.FC = () => {
  const { schoolId } = useParams<{ schoolId: string }>();
  const [currentTab, setCurrentTab] = useState(0);

  const handleTabChange = (event: React.SyntheticEvent, newValue: number) => {
    setCurrentTab(newValue);
  };

  if (!schoolId) {
    return (
      <Container maxWidth="lg">
        <Box py={4}>
          <Typography variant="h4" color="error">
            Error: ID de escuela no proporcionado
          </Typography>
        </Box>
      </Container>
    );
  }

  return (
    <Container maxWidth="lg">
      <Box py={4}>
        {/* Breadcrumbs */}
        <Breadcrumbs aria-label="breadcrumb" sx={{ mb: 3 }}>
          <Link
            color="inherit"
            href="/"
            sx={{ display: 'flex', alignItems: 'center' }}
          >
            <HomeIcon sx={{ mr: 0.5 }} fontSize="inherit" />
            Inicio
          </Link>
          <Typography
            color="text.primary"
            sx={{ display: 'flex', alignItems: 'center' }}
          >
            <NotificationsIcon sx={{ mr: 0.5 }} fontSize="inherit" />
            Gestión de Notificaciones
          </Typography>
        </Breadcrumbs>

        {/* Título principal */}
        <Box mb={4}>
          <Typography variant="h4" component="h1" gutterBottom>
            Gestión de Notificaciones de Pago
          </Typography>
          <Typography variant="body1" color="text.secondary">
            Administra plantillas, configuraciones e historial de notificaciones de pago
          </Typography>
        </Box>

        {/* Tabs de navegación */}
        <Paper sx={{ mb: 3 }}>
          <Tabs
            value={currentTab}
            onChange={handleTabChange}
            aria-label="notification management tabs"
            variant="fullWidth"
            sx={{
              borderBottom: 1,
              borderColor: 'divider',
              '& .MuiTab-root': {
                minHeight: 64,
                textTransform: 'none',
                fontSize: '1rem',
                fontWeight: 500
              }
            }}
          >
            <Tab
              icon={<TemplateIcon />}
              label="Plantillas"
              {...a11yProps(0)}
              sx={{ flexDirection: 'row', gap: 1 }}
            />
            <Tab
              icon={<SettingsIcon />}
              label="Configuración"
              {...a11yProps(1)}
              sx={{ flexDirection: 'row', gap: 1 }}
            />
            <Tab
              icon={<HistoryIcon />}
              label="Historial"
              {...a11yProps(2)}
              sx={{ flexDirection: 'row', gap: 1 }}
            />
          </Tabs>
        </Paper>

        {/* Contenido de las tabs */}
        <TabPanel value={currentTab} index={0}>
          <NotificationTemplateManager />
        </TabPanel>

        <TabPanel value={currentTab} index={1}>
          <NotificationSettings schoolId={schoolId} />
        </TabPanel>

        <TabPanel value={currentTab} index={2}>
          <NotificationHistory schoolId={schoolId} />
        </TabPanel>
      </Box>
    </Container>
  );
};

export default NotificationManagementPage;