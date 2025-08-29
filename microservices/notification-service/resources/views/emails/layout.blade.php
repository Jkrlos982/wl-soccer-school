{{-- resources/views/emails/layout.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $school_name }}</title>
    <style>
        /* CSS Variables */
        :root {
            --primary-color: {{ $primary_color ?? '#007bff' }};
            --secondary-color: {{ $secondary_color ?? '#6c757d' }};
        }
        
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Container styles */
        .email-wrapper {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .email-container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        /* Header styles */
        .email-header {
            background-color: var(--primary-color, #007bff);
            background: linear-gradient(135deg, var(--primary-color, #007bff) 0%, var(--secondary-color, #6c757d) 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .email-header img {
            max-height: 60px;
            max-width: 200px;
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .email-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        /* Body styles */
        .email-body {
            padding: 40px 30px;
            font-size: 16px;
            line-height: 1.8;
        }
        
        .email-body h1 {
            color: var(--primary-color, #007bff);
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .email-body h2 {
            color: var(--primary-color, #007bff);
            font-size: 24px;
            margin-bottom: 15px;
            margin-top: 30px;
            font-weight: 600;
        }
        
        .email-body h3 {
            color: #495057;
            font-size: 20px;
            margin-bottom: 12px;
            margin-top: 25px;
            font-weight: 600;
        }
        
        .email-body p {
            margin-bottom: 16px;
            color: #495057;
        }
        
        .email-body ul, .email-body ol {
            margin-bottom: 16px;
            padding-left: 20px;
        }
        
        .email-body li {
            margin-bottom: 8px;
            color: #495057;
        }
        
        /* Button styles */
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background-color: var(--primary-color, #007bff);
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            margin: 15px 0;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: var(--secondary-color, #6c757d);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white !important;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color, #007bff) !important;
            border: 2px solid var(--primary-color, #007bff);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color, #007bff);
            color: white !important;
        }
        
        /* Alert styles */
        .alert {
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 6px;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        
        /* Table styles */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table th {
            background-color: var(--primary-color, #007bff);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        /* Card styles */
        .card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .card-header {
            background-color: var(--primary-color, #007bff);
            color: white;
            padding: 15px 20px;
            margin: -20px -20px 20px -20px;
            border-radius: 6px 6px 0 0;
            font-weight: 600;
        }
        
        /* Footer styles */
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px 20px;
            text-align: center;
            font-size: 13px;
            color: #6c757d;
            line-height: 1.6;
        }
        
        .email-footer p {
            margin-bottom: 8px;
        }
        
        .email-footer a {
            color: var(--primary-color, #007bff);
            text-decoration: none;
        }
        
        .email-footer a:hover {
            text-decoration: underline;
        }
        
        /* Divider */
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
            border: none;
        }
        
        /* Responsive styles */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }
            
            .email-header {
                padding: 20px 15px;
            }
            
            .email-header h1 {
                font-size: 20px;
            }
            
            .email-body {
                padding: 25px 20px;
                font-size: 15px;
            }
            
            .email-body h1 {
                font-size: 24px;
            }
            
            .email-body h2 {
                font-size: 20px;
            }
            
            .email-body h3 {
                font-size: 18px;
            }
            
            .btn {
                display: block;
                width: 100%;
                text-align: center;
                padding: 12px 20px;
                font-size: 15px;
            }
            
            .table {
                font-size: 13px;
            }
            
            .table th,
            .table td {
                padding: 8px 10px;
            }
            
            .card {
                padding: 15px;
            }
            
            .email-footer {
                padding: 20px 15px;
                font-size: 12px;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background-color: #ffffff;
            }
        }
        
        /* Print styles */
        @media print {
            .email-wrapper {
                max-width: none;
                padding: 0;
            }
            
            .email-container {
                box-shadow: none;
                border: 1px solid #ccc;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <img src="{{ $school_logo }}" alt="{{ $school_name }}" />
                <h1>{{ $school_name }}</h1>
            </div>
            
            <div class="email-body">
                {!! $content !!}
            </div>
            
            <div class="email-footer">
                <p><strong>Este es un mensaje automático de {{ $school_name }}.</strong></p>
                <p>Si tienes alguna pregunta, no dudes en contactar con nosotros.</p>
                <hr class="divider" style="margin: 15px 0;">
                <p>&copy; {{ date('Y') }} {{ $school_name }}. Todos los derechos reservados.</p>
                <p>
                    <small>
                        Powered by <strong>WL School</strong> - Sistema de Gestión Educativa
                    </small>
                </p>
            </div>
        </div>
    </div>
</body>
</html>