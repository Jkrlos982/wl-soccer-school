import React from 'react';
import {
  Box,
  Container,
  Paper,
  Typography,
  useTheme,
  useMediaQuery,
  Grid,
} from '@mui/material';
import { styled } from '@mui/material/styles';

interface AuthLayoutProps {
  children: React.ReactNode;
  title: string;
  subtitle?: string;
  maxWidth?: 'xs' | 'sm' | 'md';
}

const StyledContainer = styled(Container)(({ theme }) => ({
  minHeight: '100vh',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  padding: theme.spacing(2),
  background: `linear-gradient(135deg, ${theme.palette.primary.main}15 0%, ${theme.palette.secondary.main}15 100%)`,
}));

const StyledPaper = styled(Paper)(({ theme }) => ({
  padding: theme.spacing(4),
  borderRadius: theme.spacing(2),
  boxShadow: '0 8px 32px rgba(0, 0, 0, 0.1)',
  backdropFilter: 'blur(10px)',
  border: `1px solid ${theme.palette.divider}`,
  width: '100%',
  [theme.breakpoints.up('sm')]: {
    padding: theme.spacing(6),
  },
}));

const LogoContainer = styled(Box)(({ theme }) => ({
  textAlign: 'center',
  marginBottom: theme.spacing(4),
}));

const Logo = styled(Typography)(({ theme }) => ({
  fontWeight: 'bold',
  fontSize: '2rem',
  background: `linear-gradient(45deg, ${theme.palette.primary.main}, ${theme.palette.secondary.main})`,
  backgroundClip: 'text',
  WebkitBackgroundClip: 'text',
  WebkitTextFillColor: 'transparent',
  marginBottom: theme.spacing(1),
}));

const HeaderContainer = styled(Box)(({ theme }) => ({
  textAlign: 'center',
  marginBottom: theme.spacing(4),
}));

const Title = styled(Typography)(({ theme }) => ({
  fontWeight: 600,
  color: theme.palette.text.primary,
  marginBottom: theme.spacing(1),
}));

const Subtitle = styled(Typography)(({ theme }) => ({
  color: theme.palette.text.secondary,
  fontSize: '0.95rem',
}));

const ContentContainer = styled(Box)(() => ({
  width: '100%',
}));

const FooterContainer = styled(Box)(({ theme }) => ({
  textAlign: 'center',
  marginTop: theme.spacing(4),
  paddingTop: theme.spacing(3),
  borderTop: `1px solid ${theme.palette.divider}`,
}));

const FooterText = styled(Typography)(({ theme }) => ({
  color: theme.palette.text.secondary,
  fontSize: '0.875rem',
}));

const AuthLayout: React.FC<AuthLayoutProps> = ({
  children,
  title,
  subtitle,
  maxWidth = 'sm',
}) => {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));

  return (
    <StyledContainer maxWidth={maxWidth}>
      <Box display="flex" justifyContent="center">
        <Box width={{ xs: '100%', sm: '83.33%', md: '66.67%' }}>
          <StyledPaper elevation={0}>
            {/* Logo Section */}
            <LogoContainer>
              <Logo variant="h4">
                WL-School
              </Logo>
              <Typography
                variant="body2"
                color="text.secondary"
                sx={{ fontWeight: 500 }}
              >
                Sistema de Gestión Escolar
              </Typography>
            </LogoContainer>

            {/* Header Section */}
            <HeaderContainer>
              <Title variant={isMobile ? 'h5' : 'h4'}>
                {title}
              </Title>
              {subtitle && (
                <Subtitle variant="body1">
                  {subtitle}
                </Subtitle>
              )}
            </HeaderContainer>

            {/* Content Section */}
            <ContentContainer>
              {children}
            </ContentContainer>

            {/* Footer Section */}
            <FooterContainer>
              <FooterText>
                © {new Date().getFullYear()} WL-School. Todos los derechos reservados.
              </FooterText>
            </FooterContainer>
          </StyledPaper>
        </Box>
      </Box>
    </StyledContainer>
  );
};

export default AuthLayout;