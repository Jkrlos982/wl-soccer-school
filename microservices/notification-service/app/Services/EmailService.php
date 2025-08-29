<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\School;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class EmailService
{
    /**
     * Send transactional email
     */
    public function sendTransactionalEmail($to, $subject, $content, $attachments = [])
    {
        try {
            Mail::send([], [], function ($message) use ($to, $subject, $content, $attachments) {
                $message->to($to)
                       ->subject($subject)
                       ->html($content);
                       
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? null
                        ]);
                    }
                }
            });
            
            return [
                'success' => true,
                'message_id' => null, // Laravel Mail no retorna ID
                'response' => 'Email sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }
    
    /**
     * Send templated email
     */
    public function sendTemplatedEmail($to, $template, $variables = [], $attachments = [])
    {
        try {
            $renderedContent = $this->renderEmailTemplate($template, $variables);
            
            return $this->sendTransactionalEmail(
                $to,
                $template->subject,
                $renderedContent,
                $attachments
            );
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }
    
    /**
     * Send bulk emails
     */
    public function sendBulkEmails($recipients, $subject, $content, $attachments = [])
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($recipients as $recipient) {
            $result = $this->sendTransactionalEmail(
                $recipient['email'],
                $subject,
                $content,
                $attachments
            );
            
            $results[] = [
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? null,
                'result' => $result
            ];
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }
        
        return [
            'success' => $failureCount === 0,
            'total_sent' => count($recipients),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }
    
    /**
     * Send templated bulk emails
     */
    public function sendBulkTemplatedEmails($recipients, $template, $globalVariables = [], $attachments = [])
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($recipients as $recipient) {
            // Merge global variables with recipient-specific variables
            $variables = array_merge($globalVariables, $recipient['variables'] ?? []);
            
            $result = $this->sendTemplatedEmail(
                $recipient['email'],
                $template,
                $variables,
                $attachments
            );
            
            $results[] = [
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? null,
                'result' => $result
            ];
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }
        
        return [
            'success' => $failureCount === 0,
            'total_sent' => count($recipients),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }
    
    /**
     * Render email template with variables
     */
    private function renderEmailTemplate($template, $variables = [])
    {
        $content = $template->content;
        
        // Reemplazar variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // Aplicar layout base si no está presente
        if (!str_contains($content, '<html>')) {
            $content = $this->wrapInEmailLayout($content, $template->school);
        }
        
        return $content;
    }
    
    /**
     * Wrap content in email layout
     */
    private function wrapInEmailLayout($content, $school)
    {
        $schoolName = $school->name ?? 'WL School';
        $schoolLogo = $school->logo_url ?? asset('images/default-logo.png');
        $schoolColors = $school->brand_colors ?? ['primary' => '#007bff', 'secondary' => '#6c757d'];
        
        return View::make('emails.layout', [
            'content' => $content,
            'school_name' => $schoolName,
            'school_logo' => $schoolLogo,
            'primary_color' => $schoolColors['primary'],
            'secondary_color' => $schoolColors['secondary']
        ])->render();
    }
    
    /**
     * Preview email template
     */
    public function previewTemplate($template, $variables = [])
    {
        try {
            $renderedContent = $this->renderEmailTemplate($template, $variables);
            
            return [
                'success' => true,
                'content' => $renderedContent,
                'variables_used' => array_keys($variables)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null
            ];
        }
    }
    
    /**
     * Validate email addresses
     */
    public function validateEmails($emails)
    {
        $valid = [];
        $invalid = [];
        
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $email;
            } else {
                $invalid[] = $email;
            }
        }
        
        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'valid_count' => count($valid),
            'invalid_count' => count($invalid)
        ];
    }
    
    /**
     * Get email statistics
     */
    public function getEmailStats($schoolId = null, $dateFrom = null, $dateTo = null)
    {
        // Esta funcionalidad se implementaría con un sistema de tracking más avanzado
        // Por ahora retornamos datos básicos
        return [
            'total_sent' => 0,
            'total_delivered' => 0,
            'total_opened' => 0,
            'total_clicked' => 0,
            'bounce_rate' => 0,
            'open_rate' => 0,
            'click_rate' => 0
        ];
    }
    
    /**
     * Create email template from content
     */
    public function createTemplateFromContent($schoolId, $name, $subject, $content, $variables = [], $category = 'general')
    {
        try {
            $template = NotificationTemplate::create([
                'school_id' => $schoolId,
                'name' => $name,
                'code' => strtolower(str_replace(' ', '_', $name)),
                'type' => 'email',
                'category' => $category,
                'subject' => $subject,
                'content' => $content,
                'variables' => $variables,
                'is_active' => true,
                'is_default' => false
            ]);
            
            return [
                'success' => true,
                'template' => $template,
                'message' => 'Template created successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'template' => null
            ];
        }
    }
}