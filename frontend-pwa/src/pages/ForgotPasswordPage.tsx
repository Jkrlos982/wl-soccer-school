import React from 'react';
import { Container, Paper, Box } from '@mui/material';
import AuthLayout from '../components/layout/AuthLayout';
import { ForgotPasswordForm } from '../components/auth';

const ForgotPasswordPage: React.FC = () => {
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
            title="Recuperar Contraseña"
            subtitle="Te ayudamos a recuperar el acceso a tu cuenta"
          >
            <ForgotPasswordForm />
          </AuthLayout>
        </Paper>
      </Box>
    </Container>
  );
};

export default ForgotPasswordPage;