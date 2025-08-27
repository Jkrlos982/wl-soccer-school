import React from 'react';
import { Container, Paper, Box } from '@mui/material';
import AuthLayout from '../components/layout/AuthLayout';
import { RegisterForm } from '../components/auth';

const RegisterPage: React.FC = () => {
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
            title="Crear Cuenta"
            subtitle="Únete a WL School y comienza tu experiencia educativa"
          >
            <RegisterForm />
          </AuthLayout>
        </Paper>
      </Box>
    </Container>
  );
};

export default RegisterPage;