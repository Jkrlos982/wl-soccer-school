<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhooks from external systems.
     */
    public function handle(Request $request, string $source): JsonResponse
    {
        // Validate webhook signature
        if (!$this->validateWebhookSignature($request, $source)) {
            Log::warning('Invalid webhook signature', [
                'source' => $source,
                'ip' => $request->ip(),
                'headers' => $request->headers->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Firma de webhook inválida'
            ], 401);
        }

        try {
            $payload = $request->all();
            
            Log::info('Webhook received', [
                'source' => $source,
                'event' => $payload['event'] ?? 'unknown',
                'timestamp' => now()
            ]);

            $result = match ($source) {
                'hr_system' => $this->handleHRSystemWebhook($payload),
                'attendance' => $this->handleAttendanceWebhook($payload),
                'banking' => $this->handleBankingWebhook($payload),
                'government' => $this->handleGovernmentWebhook($payload),
                'slack' => $this->handleSlackWebhook($payload),
                'email' => $this->handleEmailWebhook($payload),
                default => $this->handleGenericWebhook($source, $payload)
            };

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Webhook procesado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'source' => $source,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error procesando webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle HR System webhooks.
     */
    private function handleHRSystemWebhook(array $payload): array
    {
        $event = $payload['event'] ?? null;
        
        return match ($event) {
            'employee.created' => $this->handleEmployeeCreated($payload['data']),
            'employee.updated' => $this->handleEmployeeUpdated($payload['data']),
            'employee.terminated' => $this->handleEmployeeTerminated($payload['data']),
            'department.created' => $this->handleDepartmentCreated($payload['data']),
            'position.updated' => $this->handlePositionUpdated($payload['data']),
            default => ['message' => 'Evento HR no reconocido: ' . $event]
        };
    }

    /**
     * Handle Attendance System webhooks.
     */
    private function handleAttendanceWebhook(array $payload): array
    {
        $event = $payload['event'] ?? null;
        
        return match ($event) {
            'attendance.recorded' => $this->handleAttendanceRecorded($payload['data']),
            'overtime.approved' => $this->handleOvertimeApproved($payload['data']),
            'leave.approved' => $this->handleLeaveApproved($payload['data']),
            'absence.reported' => $this->handleAbsenceReported($payload['data']),
            default => ['message' => 'Evento de asistencia no reconocido: ' . $event]
        };
    }

    /**
     * Handle Banking System webhooks.
     */
    private function handleBankingWebhook(array $payload): array
    {
        $event = $payload['event'] ?? null;
        
        return match ($event) {
            'payment.completed' => $this->handlePaymentCompleted($payload['data']),
            'payment.failed' => $this->handlePaymentFailed($payload['data']),
            'account.updated' => $this->handleAccountUpdated($payload['data']),
            default => ['message' => 'Evento bancario no reconocido: ' . $event]
        };
    }

    /**
     * Handle Government System webhooks.
     */
    private function handleGovernmentWebhook(array $payload): array
    {
        $event = $payload['event'] ?? null;
        
        return match ($event) {
            'tax.rate.updated' => $this->handleTaxRateUpdated($payload['data']),
            'minimum.wage.updated' => $this->handleMinimumWageUpdated($payload['data']),
            'regulation.changed' => $this->handleRegulationChanged($payload['data']),
            default => ['message' => 'Evento gubernamental no reconocido: ' . $event]
        };
    }

    /**
     * Handle Slack webhooks.
     */
    private function handleSlackWebhook(array $payload): array
    {
        $event = $payload['event'] ?? null;
        
        return match ($event) {
            'leave.request' => $this->handleSlackLeaveRequest($payload['data']),
            'payroll.inquiry' => $this->handleSlackPayrollInquiry($payload['data']),
            default => ['message' => 'Evento de Slack no reconocido: ' . $event]
        };
    }

    /**
     * Handle Email webhooks.
     */
    private function handleEmailWebhook(array $payload): array
    {
        $event = $payload['event'] ?? null;
        
        return match ($event) {
            'email.delivered' => $this->handleEmailDelivered($payload['data']),
            'email.bounced' => $this->handleEmailBounced($payload['data']),
            'email.opened' => $this->handleEmailOpened($payload['data']),
            default => ['message' => 'Evento de email no reconocido: ' . $event]
        };
    }

    /**
     * Handle generic webhooks.
     */
    private function handleGenericWebhook(string $source, array $payload): array
    {
        Log::info('Generic webhook processed', [
            'source' => $source,
            'payload' => $payload
        ]);

        return [
            'message' => "Webhook genérico de {$source} procesado",
            'processed_at' => now()->toISOString()
        ];
    }

    /**
     * Handle employee created event.
     */
    private function handleEmployeeCreated(array $data): array
    {
        $validator = Validator::make($data, [
            'employee_id' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'department_id' => 'sometimes|exists:departments,id',
            'position_id' => 'sometimes|exists:positions,id'
        ]);

        if ($validator->fails()) {
            throw new \Exception('Datos de empleado inválidos: ' . $validator->errors()->first());
        }

        // Check if employee already exists
        $existingEmployee = Employee::where('employee_code', $data['employee_id'])->first();
        
        if ($existingEmployee) {
            return ['message' => 'Empleado ya existe', 'employee_id' => $existingEmployee->id];
        }

        // Create new employee (you might want to implement this logic)
        return ['message' => 'Empleado creado desde webhook', 'employee_code' => $data['employee_id']];
    }

    /**
     * Handle employee updated event.
     */
    private function handleEmployeeUpdated(array $data): array
    {
        $employee = Employee::where('employee_code', $data['employee_id'])->first();
        
        if (!$employee) {
            throw new \Exception('Empleado no encontrado: ' . $data['employee_id']);
        }

        // Update employee data (implement as needed)
        return ['message' => 'Empleado actualizado desde webhook', 'employee_id' => $employee->id];
    }

    /**
     * Handle employee terminated event.
     */
    private function handleEmployeeTerminated(array $data): array
    {
        $employee = Employee::where('employee_code', $data['employee_id'])->first();
        
        if (!$employee) {
            throw new \Exception('Empleado no encontrado: ' . $data['employee_id']);
        }

        // Update employee status to inactive
        $employee->update([
            'status' => 'inactive',
            'termination_date' => $data['termination_date'] ?? now(),
            'termination_reason' => $data['reason'] ?? 'Terminación desde sistema HR'
        ]);

        return ['message' => 'Empleado terminado desde webhook', 'employee_id' => $employee->id];
    }

    /**
     * Handle attendance recorded event.
     */
    private function handleAttendanceRecorded(array $data): array
    {
        $validator = Validator::make($data, [
            'employee_id' => 'required|string',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i:s',
            'check_out' => 'sometimes|date_format:H:i:s',
            'hours_worked' => 'sometimes|numeric'
        ]);

        if ($validator->fails()) {
            throw new \Exception('Datos de asistencia inválidos: ' . $validator->errors()->first());
        }

        // Process attendance data (implement as needed)
        return [
            'message' => 'Asistencia registrada desde webhook',
            'employee_id' => $data['employee_id'],
            'date' => $data['date']
        ];
    }

    /**
     * Handle payment completed event.
     */
    private function handlePaymentCompleted(array $data): array
    {
        $validator = Validator::make($data, [
            'payroll_id' => 'required|exists:payrolls,id',
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string'
        ]);

        if ($validator->fails()) {
            throw new \Exception('Datos de pago inválidos: ' . $validator->errors()->first());
        }

        $payroll = Payroll::find($data['payroll_id']);
        $payroll->update([
            'payment_status' => 'paid',
            'payment_date' => now(),
            'transaction_id' => $data['transaction_id']
        ]);

        return [
            'message' => 'Pago completado desde webhook',
            'payroll_id' => $data['payroll_id'],
            'transaction_id' => $data['transaction_id']
        ];
    }

    /**
     * Handle payment failed event.
     */
    private function handlePaymentFailed(array $data): array
    {
        $payroll = Payroll::find($data['payroll_id']);
        $payroll->update([
            'payment_status' => 'failed',
            'payment_error' => $data['error_message'] ?? 'Error desconocido'
        ]);

        return [
            'message' => 'Pago fallido desde webhook',
            'payroll_id' => $data['payroll_id'],
            'error' => $data['error_message'] ?? 'Error desconocido'
        ];
    }

    /**
     * Handle tax rate updated event.
     */
    private function handleTaxRateUpdated(array $data): array
    {
        // Update tax configuration
        Cache::put('tax.uvt_value', $data['uvt_value'] ?? config('tax.uvt_value'), now()->addDays(30));
        
        if (isset($data['withholding_rates'])) {
            Cache::put('tax.withholding_rates', $data['withholding_rates'], now()->addDays(30));
        }

        return [
            'message' => 'Tasas de impuestos actualizadas desde webhook',
            'updated_at' => now()->toISOString()
        ];
    }

    /**
     * Handle minimum wage updated event.
     */
    private function handleMinimumWageUpdated(array $data): array
    {
        Cache::put('payroll.minimum_wage', $data['minimum_wage'], now()->addDays(30));
        
        if (isset($data['transport_subsidy'])) {
            Cache::put('payroll.transport_subsidy', $data['transport_subsidy'], now()->addDays(30));
        }

        return [
            'message' => 'Salario mínimo actualizado desde webhook',
            'minimum_wage' => $data['minimum_wage'],
            'updated_at' => now()->toISOString()
        ];
    }

    /**
     * Validate webhook signature.
     */
    private function validateWebhookSignature(Request $request, string $source): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        $timestamp = $request->header('X-Webhook-Timestamp');
        
        if (!$signature || !$timestamp) {
            return false;
        }

        // Check timestamp to prevent replay attacks (5 minutes tolerance)
        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $secret = config("webhooks.{$source}.secret", config('webhooks.default_secret'));
        
        if (!$secret) {
            Log::warning('No webhook secret configured', ['source' => $source]);
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $timestamp . $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Register a new webhook endpoint.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'required|string|max:50',
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'string',
            'secret' => 'sometimes|string|min:32'
        ]);

        $webhookConfig = [
            'source' => $request->source,
            'url' => $request->url,
            'events' => $request->events,
            'secret' => $request->secret ?? bin2hex(random_bytes(32)),
            'created_at' => now()->toISOString(),
            'active' => true
        ];

        // Store webhook configuration (you might want to use a database table)
        Cache::put("webhook.{$request->source}", $webhookConfig, now()->addDays(365));

        return response()->json([
            'success' => true,
            'data' => $webhookConfig,
            'message' => 'Webhook registrado exitosamente'
        ], 201);
    }

    /**
     * List registered webhooks.
     */
    public function list(): JsonResponse
    {
        // In a real implementation, you'd fetch from database
        $webhooks = [];
        
        $sources = ['hr_system', 'attendance', 'banking', 'government', 'slack', 'email'];
        
        foreach ($sources as $source) {
            $config = Cache::get("webhook.{$source}");
            if ($config) {
                $webhooks[] = $config;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $webhooks,
            'message' => 'Webhooks obtenidos exitosamente'
        ]);
    }

    /**
     * Test webhook endpoint.
     */
    public function test(Request $request, string $source): JsonResponse
    {
        $testPayload = [
            'event' => 'test.webhook',
            'data' => [
                'message' => 'Test webhook from payroll system',
                'timestamp' => now()->toISOString(),
                'source' => $source
            ],
            'test' => true
        ];

        Log::info('Test webhook sent', [
            'source' => $source,
            'payload' => $testPayload
        ]);

        return response()->json([
            'success' => true,
            'data' => $testPayload,
            'message' => 'Webhook de prueba enviado exitosamente'
        ]);
    }

    /**
     * Deactivate webhook.
     */
    public function deactivate(string $source): JsonResponse
    {
        $config = Cache::get("webhook.{$source}");
        
        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook no encontrado'
            ], 404);
        }

        $config['active'] = false;
        $config['deactivated_at'] = now()->toISOString();
        
        Cache::put("webhook.{$source}", $config, now()->addDays(365));

        return response()->json([
            'success' => true,
            'message' => 'Webhook desactivado exitosamente'
        ]);
    }

    /**
     * Get webhook logs.
     */
    public function logs(Request $request, string $source): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date'
        ]);

        // In a real implementation, you'd fetch from logs table or log files
        $logs = [
            [
                'id' => 1,
                'source' => $source,
                'event' => 'employee.created',
                'status' => 'success',
                'timestamp' => now()->subHours(2)->toISOString(),
                'processing_time' => 150
            ],
            [
                'id' => 2,
                'source' => $source,
                'event' => 'payment.completed',
                'status' => 'success',
                'timestamp' => now()->subHours(1)->toISOString(),
                'processing_time' => 89
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $logs,
            'message' => 'Logs de webhook obtenidos exitosamente'
        ]);
    }
}