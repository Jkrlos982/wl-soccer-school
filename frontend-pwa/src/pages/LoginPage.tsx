import React from 'react';
import { Container, Paper, Box } from '@mui/material';
import AuthLayout from '../components/layout/AuthLayout';
import { LoginForm } from '../components/auth';

const LoginPage: React.FC = () => {
  return (
    <Container component="main" maxWidth="sm">
      <Box
        sx={{
          minHeight: '100vh',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          py: 3,
        }}
      >
        <Paper
          elevation={3}
          sx={{
            width: '100%',
            maxWidth: 400,
            borderRadius: 2,
            overflow: 'hidden',
          }}
        >
          <AuthLayout
            title="Iniciar Sesión"
            subtitle="Accede a tu cuenta de WL School"
          >
            <LoginForm />
          </AuthLayout>
        </Paper>
      </Box>
    </Container>
  );
};

export default LoginPage;