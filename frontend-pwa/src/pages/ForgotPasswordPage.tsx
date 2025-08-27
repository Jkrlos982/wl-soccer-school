import React from 'react';
import { Paper, Box } from '@mui/material';
import AuthLayout from '../components/layout/AuthLayout';
import { ForgotPasswordForm } from '../components/auth';

const ForgotPasswordPage: React.FC = () => {
  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'flex',
        background: 'linear-gradient(135deg, #1976d215 0%, #dc004e15 100%)',
      }}
    >
      <Box sx={{ display: 'flex', minHeight: '100vh' }}>
        {/* Left side - Branding/Image */}
        <Box
          sx={{
            flex: { xs: 0, sm: '0 0 33.33%', md: '0 0 50%', lg: '0 0 58.33%' },
            background: 'linear-gradient(45deg, #1976d2, #dc004e)',
            display: { xs: 'none', sm: 'flex' },
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            color: 'white',
            position: 'relative',
            '&::before': {
              content: '""',
              position: 'absolute',
              top: 0,
              left: 0,
              right: 0,
              bottom: 0,
              background: 'rgba(0,0,0,0.1)',
            },
          }}
        >
          <Box sx={{ zIndex: 1, textAlign: 'center', px: 4 }}>
            <Box
              component="h1"
              sx={{
                fontSize: { sm: '3rem', md: '4rem', lg: '5rem' },
                fontWeight: 'bold',
                mb: 2,
                textShadow: '2px 2px 4px rgba(0,0,0,0.3)',
              }}
            >
              WL-School
            </Box>
            <Box
              component="p"
              sx={{
                fontSize: { sm: '1.2rem', md: '1.5rem' },
                opacity: 0.9,
                maxWidth: '400px',
                lineHeight: 1.6,
              }}
            >
              No te preocupes, recuperar tu contraseña es fácil y seguro
            </Box>
          </Box>
        </Box>

        {/* Right side - Forgot Password Form */}
        <Box
          sx={{
            flex: { xs: '1', sm: '0 0 66.67%', md: '0 0 50%', lg: '0 0 41.67%' },
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            p: { xs: 2, sm: 4, md: 6 },
          }}
        >
          <Box sx={{ width: '100%', maxWidth: '500px' }}>
            <Paper
              elevation={0}
              sx={{
                p: { xs: 3, sm: 4, md: 5 },
                borderRadius: 3,
                background: 'rgba(255, 255, 255, 0.95)',
                backdropFilter: 'blur(10px)',
                border: '1px solid rgba(255, 255, 255, 0.2)',
                boxShadow: '0 8px 32px rgba(0, 0, 0, 0.1)',
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
        </Box>
      </Box>
    </Box>
  );
};

export default ForgotPasswordPage;