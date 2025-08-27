import React from 'react';
import {
  Container,
  Paper,
  Box,
  Typography,
  Button,
  Grid,
  Card,
  CardContent,
  Avatar,
  Chip,
} from '@mui/material';
import {
  Dashboard,
  Person,
  School,
  ExitToApp,
} from '@mui/icons-material';
import { useAuth } from '../hooks/useAuth';

const DashboardPage: React.FC = () => {
  const { user, logout } = useAuth();

  const handleLogout = async () => {
    try {
      await logout();
    } catch (error) {
      console.error('Error during logout:', error);
    }
  };

  return (
    <Container maxWidth="lg" sx={{ py: 4 }}>
      {/* Header */}
      <Box sx={{ mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
          <Dashboard sx={{ fontSize: 32, color: 'primary.main' }} />
          <Typography variant="h4" component="h1" fontWeight="bold">
            Dashboard
          </Typography>
        </Box>
        <Button
          variant="outlined"
          startIcon={<ExitToApp />}
          onClick={handleLogout}
          color="error"
        >
          Cerrar Sesión
        </Button>
      </Box>

      {/* Welcome Section */}
      <Paper elevation={2} sx={{ p: 3, mb: 4, background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white' }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 3 }}>
          <Avatar
            sx={{ 
              width: 80, 
              height: 80, 
              bgcolor: 'rgba(255,255,255,0.2)',
              fontSize: '2rem'
            }}
          >
            {user?.name?.charAt(0).toUpperCase()}
          </Avatar>
          <Box>
            <Typography variant="h5" gutterBottom>
              ¡Bienvenido, {user?.name}!
            </Typography>
            <Typography variant="body1" sx={{ opacity: 0.9, mb: 1 }}>
              {user?.email}
            </Typography>
            <Chip 
              label={user?.role} 
              sx={{ 
                bgcolor: 'rgba(255,255,255,0.2)', 
                color: 'white',
                fontWeight: 'bold'
              }} 
            />
          </Box>
        </Box>
      </Paper>

      {/* Stats Cards */}
      <Box sx={{ display: 'flex', gap: 3, mb: 4, flexWrap: 'wrap' }}>
        <Card elevation={2} sx={{ flex: 1, minWidth: 300 }}>
          <CardContent sx={{ textAlign: 'center', py: 4 }}>
            <Person sx={{ fontSize: 48, color: 'primary.main', mb: 2 }} />
            <Typography variant="h6" gutterBottom>
              Perfil de Usuario
            </Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
              Gestiona tu información personal y configuración de cuenta
            </Typography>
            <Button variant="contained" size="small">
              Ver Perfil
            </Button>
          </CardContent>
        </Card>

        <Card elevation={2} sx={{ flex: 1, minWidth: 300 }}>
          <CardContent sx={{ textAlign: 'center', py: 4 }}>
            <School sx={{ fontSize: 48, color: 'secondary.main', mb: 2 }} />
            <Typography variant="h6" gutterBottom>
              Mi Escuela
            </Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
              Accede a la información y recursos de tu institución educativa
            </Typography>
            <Button variant="contained" color="secondary" size="small">
              Ver Escuela
            </Button>
          </CardContent>
        </Card>
      </Box>

      {/* User Info Section */}
      <Paper elevation={1} sx={{ mt: 4, p: 3 }}>
        <Typography variant="h6" gutterBottom>
          Información de la Sesión
        </Typography>
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: '1fr 1fr' }, gap: 2 }}>
          <Typography variant="body2" color="text.secondary">
            <strong>ID de Usuario:</strong> {user?.id}
          </Typography>
          <Typography variant="body2" color="text.secondary">
            <strong>Rol:</strong> {user?.role}
          </Typography>
          <Typography variant="body2" color="text.secondary">
            <strong>Escuela ID:</strong> {user?.school_id || 'No asignada'}
          </Typography>
          <Typography variant="body2" color="text.secondary">
            <strong>Permisos:</strong> {user?.permissions?.length || 0} permisos
          </Typography>
        </Box>
      </Paper>
    </Container>
  );
};

export default DashboardPage;