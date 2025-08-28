<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VoucherTemplate;

class VoucherTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Payment Voucher Template
        VoucherTemplate::create([
            'school_id' => null, // Global template
            'name' => 'Comprobante de Pago por Defecto',
            'type' => VoucherTemplate::TYPE_PAYMENT_VOUCHER,
            'template_html' => $this->getPaymentVoucherTemplate(),
            'variables' => [
                'payment.reference_number',
                'payment.amount',
                'payment.payment_date',
                'payment.payment_method',
                'student.full_name',
                'concept.name',
                'school.name',
                'school.logo',
                'date',
                'time',
                'account_receivable.description',
                'account_receivable.remaining_amount'
            ],
            'is_default' => true
        ]);

        // Default Receipt Template
        VoucherTemplate::create([
            'school_id' => null,
            'name' => 'Recibo por Defecto',
            'type' => VoucherTemplate::TYPE_RECEIPT,
            'template_html' => $this->getReceiptTemplate(),
            'variables' => [
                'payment.reference_number',
                'payment.amount',
                'payment.payment_date',
                'payment.payment_method',
                'student.full_name',
                'concept.name',
                'school.name',
                'date',
                'time'
            ],
            'is_default' => true
        ]);
    }

    /**
     * Get the default payment voucher HTML template.
     */
    private function getPaymentVoucherTemplate(): string
    {
        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }
        .voucher {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #2c3e50;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header img {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .content {
            padding: 30px;
            background: white;
        }
        .voucher-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        .info-section h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-item strong {
            color: #2c3e50;
            display: inline-block;
            width: 120px;
        }
        .amount-section {
            background: #e8f5e8;
            border: 2px solid #27ae60;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-section h3 {
            margin: 0 0 10px 0;
            color: #27ae60;
            font-size: 16px;
        }
        .amount {
            font-size: 28px;
            font-weight: bold;
            color: #27ae60;
        }
        .footer {
            background: #ecf0f1;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #bdc3c7;
        }
        .footer p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 12px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(52, 152, 219, 0.1);
            z-index: -1;
            font-weight: bold;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .voucher { border: 1px solid #000; }
        }
    </style>
</head>
<body>
    <div class="watermark">PAGADO</div>
    <div class="voucher">
        <div class="header">
            <img src="{{ school.logo }}" alt="Logo" onerror="this.style.display=\'none\'">
            <h1>{{ school.name }}</h1>
        </div>
        
        <div class="content">
            <div class="voucher-title">
                COMPROBANTE DE PAGO
            </div>
            
            <div class="info-grid">
                <div class="info-section">
                    <h3>Información del Pago</h3>
                    <div class="info-item">
                        <strong>Número:</strong> {{ payment.reference_number }}
                    </div>
                    <div class="info-item">
                        <strong>Fecha:</strong> {{ payment.payment_date }}
                    </div>
                    <div class="info-item">
                        <strong>Método:</strong> {{ payment.payment_method }}
                    </div>
                    <div class="info-item">
                        <strong>Estado:</strong> {{ payment.status }}
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>Información del Estudiante</h3>
                    <div class="info-item">
                        <strong>Estudiante:</strong> {{ student.full_name }}
                    </div>
                    <div class="info-item">
                        <strong>Concepto:</strong> {{ concept.name }}
                    </div>
                    <div class="info-item">
                        <strong>Descripción:</strong> {{ account_receivable.description }}
                    </div>
                </div>
            </div>
            
            <div class="amount-section">
                <h3>Monto Pagado</h3>
                <div class="amount">${{ payment.amount }}</div>
            </div>
            
            <div class="info-grid">
                <div class="info-section">
                    <h3>Detalles de la Cuenta</h3>
                    <div class="info-item">
                        <strong>Saldo Restante:</strong> ${{ account_receivable.remaining_amount }}
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>Información del Sistema</h3>
                    <div class="info-item">
                        <strong>Generado:</strong> {{ date }} {{ time }}
                    </div>
                    <div class="info-item">
                        <strong>Por:</strong> {{ generated_by }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Gracias por su pago</strong></p>
            <p>Este comprobante es válido como constancia de pago</p>
            <p>Para consultas, conserve este documento</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Get the default receipt HTML template.
     */
    private function getReceiptTemplate(): string
    {
        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .receipt {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
        }
        .content {
            padding: 20px;
        }
        .receipt-info {
            margin-bottom: 20px;
        }
        .receipt-info p {
            margin: 5px 0;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #e8f5e8;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h1>{{ school.name }}</h1>
            <h2>RECIBO</h2>
        </div>
        
        <div class="content">
            <div class="receipt-info">
                <p><strong>Número de Recibo:</strong> {{ payment.reference_number }}</p>
                <p><strong>Fecha:</strong> {{ payment.payment_date }}</p>
                <p><strong>Estudiante:</strong> {{ student.full_name }}</p>
                <p><strong>Concepto:</strong> {{ concept.name }}</p>
                <p><strong>Método de Pago:</strong> {{ payment.payment_method }}</p>
            </div>
            
            <div class="amount">
                Monto: ${{ payment.amount }}
            </div>
        </div>
        
        <div class="footer">
            <p>Generado el {{ date }} a las {{ time }}</p>
            <p>Documento válido como comprobante de pago</p>
        </div>
    </div>
</body>
</html>';
    }
}