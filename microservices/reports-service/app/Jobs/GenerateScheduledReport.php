<?php

namespace App\Jobs;

use App\Models\ReportTemplate;
use App\Models\GeneratedReport;
use App\Services\ReportService;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Exception;

class GenerateScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * The report template ID
     */
    protected int $templateId;

    /**
     * Additional parameters for report generation
     */
    protected array $parameters;

    /**
     * Recipients for the generated report
     */
    protected array $recipients;

    /**
     * Create a new job instance.
     */
    public function __construct(int $templateId, array $parameters = [], array $recipients = [])
    {
        $this->templateId = $templateId;
        $this->parameters = $parameters;
        $this->recipients = $recipients;
        
        // Set queue and delay if specified in parameters
        if (isset($parameters['queue'])) {
            $this->onQueue($parameters['queue']);
        }
        
        if (isset($parameters['delay'])) {
            $this->delay($parameters['delay']);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService, AlertService $alertService): void
    {
        try {
            Log::info('Starting scheduled report generation', [
                'template_id' => $this->templateId,
                'parameters' => $this->parameters,
                'job_id' => $this->job->getJobId()
            ]);

            // Find the report template
            $template = ReportTemplate::findOrFail($this->templateId);
            
            // Check if template is active and scheduled
            if (!$template->is_active || !$template->is_scheduled) {
                Log::warning('Template is not active or not scheduled', [
                    'template_id' => $this->templateId,
                    'is_active' => $template->is_active,
                    'is_scheduled' => $template->is_scheduled
                ]);
                return;
            }

            // Merge template parameters with job parameters
            $mergedParameters = array_merge(
                $template->parameters ?? [],
                $this->parameters
            );

            // Generate the report
            $generatedReport = $reportService->generateReport($template, $mergedParameters);

            if ($generatedReport) {
                Log::info('Scheduled report generated successfully', [
                    'template_id' => $this->templateId,
                    'report_id' => $generatedReport->id,
                    'file_path' => $generatedReport->file_path
                ]);

                // Send notifications if recipients are specified
                if (!empty($this->recipients)) {
                    $this->sendReportNotifications($generatedReport, $template);
                }

                // Update template's last generated timestamp
                $template->update([
                    'last_generated_at' => now(),
                    'generation_count' => $template->generation_count + 1
                ]);

                // Check all alerts after successful generation
                $alertService->checkAllAlerts();
            } else {
                throw new Exception('Report generation returned null');
            }

        } catch (Exception $e) {
            Log::error('Scheduled report generation failed', [
                'template_id' => $this->templateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->job->getJobId()
            ]);

            // Update template with error information
            if (isset($template)) {
                $template->update([
                    'last_error' => $e->getMessage(),
                    'last_error_at' => now()
                ]);
            }

            // Check all alerts after failure
            if (isset($alertService)) {
                $alertService->checkAllAlerts();
            }

            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /**
     * Send notifications about the generated report
     */
    protected function sendReportNotifications(GeneratedReport $report, ReportTemplate $template): void
    {
        try {
            foreach ($this->recipients as $recipient) {
                if (isset($recipient['email'])) {
                    $this->sendEmailNotification($recipient['email'], $report, $template);
                }
                
                if (isset($recipient['webhook'])) {
                    $this->sendWebhookNotification($recipient['webhook'], $report, $template);
                }
            }
        } catch (Exception $e) {
            Log::warning('Failed to send report notifications', [
                'report_id' => $report->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(string $email, GeneratedReport $report, ReportTemplate $template): void
    {
        try {
            Mail::send('emails.scheduled-report', [
                'report' => $report,
                'template' => $template,
                'download_url' => route('api.reports.download', $report->id)
            ], function ($message) use ($email, $template, $report) {
                $message->to($email)
                    ->subject("Scheduled Report: {$template->name}")
                    ->attach($report->file_path, [
                        'as' => $report->filename,
                        'mime' => $this->getMimeType($report->format)
                    ]);
            });

            Log::info('Email notification sent', [
                'report_id' => $report->id,
                'email' => $email
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send email notification', [
                'report_id' => $report->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send webhook notification
     */
    protected function sendWebhookNotification(string $webhookUrl, GeneratedReport $report, ReportTemplate $template): void
    {
        try {
            $payload = [
                'event' => 'report_generated',
                'report' => [
                    'id' => $report->id,
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'format' => $report->format,
                    'filename' => $report->filename,
                    'file_size' => $report->file_size,
                    'generated_at' => $report->created_at->toISOString(),
                    'download_url' => route('api.reports.download', $report->id)
                ],
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout(30)->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Webhook notification sent', [
                    'report_id' => $report->id,
                    'webhook_url' => $webhookUrl,
                    'response_status' => $response->status()
                ]);
            } else {
                Log::warning('Webhook notification failed', [
                    'report_id' => $report->id,
                    'webhook_url' => $webhookUrl,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send webhook notification', [
                'report_id' => $report->id,
                'webhook_url' => $webhookUrl,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get MIME type for report format
     */
    protected function getMimeType(string $format): string
    {
        return match (strtolower($format)) {
            'pdf' => 'application/pdf',
            'excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'json' => 'application/json',
            'html' => 'text/html',
            default => 'application/octet-stream'
        };
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('Scheduled report generation job failed permanently', [
            'template_id' => $this->templateId,
            'parameters' => $this->parameters,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update template with permanent failure status
        try {
            $template = ReportTemplate::find($this->templateId);
            if ($template) {
                $template->update([
                    'last_error' => $exception->getMessage(),
                    'last_error_at' => now(),
                    'failed_attempts' => $template->failed_attempts + 1
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to update template after job failure', [
                'template_id' => $this->templateId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'report-generation',
            'scheduled',
            "template:{$this->templateId}"
        ];
    }
}
