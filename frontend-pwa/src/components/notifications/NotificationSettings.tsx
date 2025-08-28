import React, { useState, useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  Box,
  Button,
  Card,
  CardContent,
  Divider,
  FormControlLabel,
  Switch,
  TextField,
  Typography,
  Alert,
  Snackbar,
} from '@mui/material';
import {
  Save as SaveIcon,
} from '@mui/icons-material';
import { RootState } from '../../store';
import {
  fetchNotificationSettings,
  updateNotificationSettings,
} from '../../store/notificationSlice';
import {
  NotificationSettings as NotificationSettingsType,
  NotificationType,
  NotificationSettingsForm,
} from '../../types/notification';

interface NotificationSettingsProps {
  schoolId?: string;
}

const NotificationSettings: React.FC<NotificationSettingsProps> = ({ schoolId }) => {
  const dispatch = useDispatch();
  const { data: settings, loading, error } = useSelector((state: RootState) => state.notification.settings);
  
  const [localSettings, setLocalSettings] = useState<NotificationSettingsForm | null>(null);
  const [showAlert, setShowAlert] = useState(false);
  const [alertMessage, setAlertMessage] = useState('');
  const [alertSeverity, setAlertSeverity] = useState<'success' | 'error'>('success');


  useEffect(() => {
    dispatch(fetchNotificationSettings() as any);
  }, [dispatch]);

  useEffect(() => {
    if (settings) {
      setLocalSettings({ ...settings });
    }
  }, [settings]);

  const handleSettingChange = (key: keyof NotificationSettingsForm, value: any) => {
    if (localSettings) {
      setLocalSettings({
        ...localSettings,
        [key]: value,
      });
    }
  };

  const handleSaveSettings = async () => {
    if (!localSettings) return;

    try {
      await dispatch(updateNotificationSettings(localSettings) as any);
      setAlertMessage('Configuración guardada exitosamente');
      setAlertSeverity('success');
      setShowAlert(true);
    } catch (error) {
      setAlertMessage('Error al guardar la configuración');
      setAlertSeverity('error');
      setShowAlert(true);
    }
  };





  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <Typography>Cargando configuración...</Typography>
      </Box>
    );
  }

  if (!localSettings) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <Typography>No se pudo cargar la configuración</Typography>
      </Box>
    );
  }

  return (
    <Box>
      {/* Configuración General */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Typography variant="h6" gutterBottom>
            Configuración General
          </Typography>
          
          <Box display="flex" flexDirection="column" gap={2}>
            <Box display="flex" flexWrap="wrap" gap={2}>
              <FormControlLabel
                control={
                  <Switch
                    checked={localSettings.enableEmail}
                    onChange={(e) => handleSettingChange('enableEmail', e.target.checked)}
                  />
                }
                label="Notificaciones por Email"
              />
              
              <FormControlLabel
                control={
                  <Switch
                    checked={localSettings.enableSMS}
                    onChange={(e) => handleSettingChange('enableSMS', e.target.checked)}
                  />
                }
                label="Notificaciones por SMS"
              />
              
              <FormControlLabel
                control={
                  <Switch
                    checked={localSettings.enableWhatsApp}
                    onChange={(e) => handleSettingChange('enableWhatsApp', e.target.checked)}
                  />
                }
                label="Notificaciones por WhatsApp"
              />
            </Box>
          </Box>
          
          <Divider sx={{ my: 3 }} />
          
          <Box display="flex" flexWrap="wrap" gap={2}>
            <TextField
              label="Días de Recordatorio de Pago"
              type="number"
              value={localSettings.paymentReminderDays}
              onChange={(e) => handleSettingChange('paymentReminderDays', parseInt(e.target.value))}
              inputProps={{ min: 1, max: 30 }}
              sx={{ minWidth: 200 }}
            />
            
            <TextField
              label="Frecuencia de Notificaciones de Mora (días)"
              type="number"
              value={localSettings.overdueNotificationFrequency}
              onChange={(e) => handleSettingChange('overdueNotificationFrequency', parseInt(e.target.value))}
              inputProps={{ min: 1, max: 30 }}
              sx={{ minWidth: 200 }}
            />
          </Box>
        </CardContent>
      </Card>



      {/* Botón Guardar */}
      <Box display="flex" justifyContent="flex-end">
        <Button
          variant="contained"
          size="large"
          startIcon={<SaveIcon />}
          onClick={handleSaveSettings}
          disabled={loading}
        >
          Guardar Configuración
        </Button>
      </Box>

      {/* Alertas */}
      <Snackbar
        open={showAlert}
        autoHideDuration={6000}
        onClose={() => setShowAlert(false)}
      >
        <Alert
          onClose={() => setShowAlert(false)}
          severity={alertSeverity}
          sx={{ width: '100%' }}
        >
          {alertMessage}
        </Alert>
      </Snackbar>
    </Box>
  );
};

export default NotificationSettings;