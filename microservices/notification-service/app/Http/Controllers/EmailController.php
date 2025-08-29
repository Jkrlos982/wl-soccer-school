<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send a transactional email
     */
    public function sendTransactional(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'to' => 'required|email',
                'subject' => 'required|string|max:255',
                'content' => 'required|string',
                'from_name' => 'nullable|string|max:255',
                'from_email' => 'nullable|email',
                'reply_to' => 'nullable|email',
                'cc' => 'nullable|array',
                'cc.*' => 'email',
                'bcc' => 'nullable|array',
                'bcc.*' => 'email',
                'attachments' => 'nullable|array',
                'attachments.*.path' => 'required_with:attachments|string',
                'attachments.*.name' => 'nullable|string',
                'attachments.*.mime' => 'nullable|string',
                'priority' => 'nullable|in:high,normal,low',
                'tags' => 'nullable|array',
                'metadata' => 'nullable|array',
                'school_id' => 'nullable|integer',
                'school_name' => 'nullable|string',
                'school_logo' => 'nullable|url',
                'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->emailService->sendTransactionalEmail(
                $request->input('to'),
                $request->input('subject'),
                $request->input('content'),
                $request->only([
                    'from_name', 'from_email', 'reply_to', 'cc', 'bcc',
                    'attachments', 'priority', 'tags', 'metadata',
                    'school_id', 'school_name', 'school_logo',
                    'primary_color', 'secondary_color'
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send transactional email', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a templated email
     */
    public function sendTemplated(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'to' => 'required|email',
                'template_id' => 'required|integer|exists:notification_templates,id',
                'variables' => 'nullable|array',
                'from_name' => 'nullable|string|max:255',
                'from_email' => 'nullable|email',
                'reply_to' => 'nullable|email',
                'cc' => 'nullable|array',
                'cc.*' => 'email',
                'bcc' => 'nullable|array',
                'bcc.*' => 'email',
                'attachments' => 'nullable|array',
                'priority' => 'nullable|in:high,normal,low',
                'tags' => 'nullable|array',
                'metadata' => 'nullable|array',
                'school_id' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get template
            $template = \App\Models\NotificationTemplate::findOrFail($request->input('template_id'));
            
            $result = $this->emailService->sendTemplatedEmail(
                $request->input('to'),
                $template,
                $request->input('variables', []),
                $request->input('attachments', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Templated email sent successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send templated email', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send templated email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send bulk emails
     */
    public function sendBulk(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'emails' => 'required|array|min:1|max:100',
                'emails.*.to' => 'required|email',
                'emails.*.subject' => 'required|string|max:255',
                'emails.*.content' => 'required|string',
                'emails.*.variables' => 'nullable|array',
                'from_name' => 'nullable|string|max:255',
                'from_email' => 'nullable|email',
                'priority' => 'nullable|in:high,normal,low',
                'tags' => 'nullable|array',
                'school_id' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $emails = $request->input('emails');
            $firstEmail = $emails[0];
            
            $result = $this->emailService->sendBulkEmails(
                array_map(function($email) {
                    return ['email' => $email['to'], 'name' => $email['name'] ?? null];
                }, $emails),
                $firstEmail['subject'],
                $firstEmail['content'],
                $request->input('attachments', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk emails processed successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send bulk emails', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send bulk templated emails
     */
    public function sendBulkTemplated(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|integer|exists:notification_templates,id',
                'recipients' => 'required|array|min:1|max:100',
                'recipients.*.to' => 'required|email',
                'recipients.*.variables' => 'nullable|array',
                'from_name' => 'nullable|string|max:255',
                'from_email' => 'nullable|email',
                'priority' => 'nullable|in:high,normal,low',
                'tags' => 'nullable|array',
                'school_id' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get template
            $template = \App\Models\NotificationTemplate::findOrFail($request->input('template_id'));
            
            $result = $this->emailService->sendBulkTemplatedEmails(
                $request->input('recipients'),
                $template,
                [],
                $request->input('attachments', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk templated emails processed successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send bulk templated emails', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk templated emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview email template
     */
    public function previewTemplate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|integer|exists:notification_templates,id',
                'variables' => 'nullable|array',
                'school_id' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get template
            $template = \App\Models\NotificationTemplate::findOrFail($request->input('template_id'));
            
            $preview = $this->emailService->previewTemplate(
                $template,
                $request->input('variables', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Template preview generated successfully',
                'data' => $preview
            ]);

        } catch (Exception $e) {
            Log::error('Failed to preview template', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to preview template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create email template from content
     */
    public function createTemplate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'content' => 'required|string',
                'type' => 'required|in:email,whatsapp,sms,push',
                'category' => 'required|string|max:100',
                'description' => 'nullable|string',
                'variables' => 'nullable|array',
                'school_id' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
                'is_default' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $template = $this->emailService->createTemplateFromContent(
                $request->input('school_id'),
                $request->input('name'),
                $request->input('subject'),
                $request->input('content'),
                $request->input('variables', []),
                $request->input('category', 'general')
            );

            return response()->json([
                'success' => true,
                'message' => 'Email template created successfully',
                'data' => $template
            ], 201);

        } catch (Exception $e) {
            Log::error('Failed to create email template', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'school_id' => 'nullable|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'type' => 'nullable|in:sent,delivered,opened,clicked,bounced,complained'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $statistics = $this->emailService->getEmailStats(
                $request->input('school_id'),
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'message' => 'Email statistics retrieved successfully',
                'data' => $statistics
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get email statistics', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test email configuration
     */
    public function testConfiguration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'to' => 'required|email',
                'provider' => 'nullable|in:smtp,ses,postmark,resend'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->emailService->sendTransactionalEmail(
                $request->input('to'),
                'Test Email Configuration - WL School',
                '<h1>¡Configuración de Email Exitosa!</h1><p>Este es un email de prueba para verificar que la configuración del sistema de emails está funcionando correctamente.</p><p>Si recibes este mensaje, significa que el servicio de notificaciones está operativo.</p>',
                [
                    'school_name' => 'WL School - Test',
                    'school_logo' => 'https://via.placeholder.com/200x60/007bff/ffffff?text=WL+School',
                    'primary_color' => '#007bff',
                    'secondary_color' => '#6c757d'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send test email', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate email address
     */
    public function validateEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validation = $this->emailService->validateEmails([$request->input('email')]);
            $isValid = $validation['valid_count'] > 0;

            return response()->json([
                'success' => true,
                'message' => 'Email validation completed',
                'data' => [
                    'email' => $request->input('email'),
                    'is_valid' => $isValid
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to validate email', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service status
     */
    public function getStatus(): JsonResponse
    {
        try {
            $status = [
                'service' => 'Email Service',
                'status' => 'operational',
                'timestamp' => now()->toISOString(),
                'version' => '1.0.0',
                'providers' => [
                    'smtp' => config('mail.default') === 'smtp',
                    'ses' => config('services.ses.key') !== null,
                    'postmark' => config('services.postmark.token') !== null,
                    'resend' => config('services.resend.key') !== null
                ],
                'configuration' => [
                    'default_driver' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name')
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Email service status retrieved successfully',
                'data' => $status
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get email service status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get service status: ' . $e->getMessage()
            ], 500);
        }
    }
}