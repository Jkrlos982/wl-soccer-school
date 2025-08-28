<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\VoucherTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class VoucherGenerator
{
    protected VoucherNumberGenerator $numberGenerator;

    public function __construct(VoucherNumberGenerator $numberGenerator)
    {
        $this->numberGenerator = $numberGenerator;
    }

    /**
     * Generate a payment voucher PDF.
     */
    public function generatePaymentVoucher(Payment $payment): string
    {
        // Get the template
        $template = $this->getTemplate('payment_voucher', $payment->school_id);
        
        if (!$template) {
            throw new \Exception('No payment voucher template found');
        }

        // Prepare data for template rendering
        $data = $this->preparePaymentData($payment);
        
        // Render HTML
        $html = $template->render($data);
        
        // Generate PDF
        $pdf = $this->generatePDF($html);
        
        // Save PDF and return path
        return $this->savePDF($pdf, $payment);
    }

    /**
     * Generate a receipt PDF.
     */
    public function generateReceipt(Payment $payment): string
    {
        $template = $this->getTemplate('receipt', $payment->school_id);
        
        if (!$template) {
            throw new \Exception('No receipt template found');
        }

        $data = $this->preparePaymentData($payment);
        $html = $template->render($data);
        $pdf = $this->generatePDF($html);
        
        return $this->savePDF($pdf, $payment, 'receipt');
    }

    /**
     * Get template for voucher generation.
     */
    protected function getTemplate(string $type, ?int $schoolId = null): ?VoucherTemplate
    {
        return VoucherTemplate::getDefaultTemplate($type, $schoolId);
    }

    /**
     * Prepare payment data for template rendering.
     */
    protected function preparePaymentData(Payment $payment): array
    {
        // Load relationships
        $payment->load([
            'accountReceivable.concept',
            'createdBy'
        ]);

        return [
            'payment' => [
                'id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'amount' => number_format($payment->amount, 2),
                'payment_date' => $payment->payment_date->format('d/m/Y'),
                'payment_method' => $payment->getPaymentMethods()[$payment->payment_method] ?? $payment->payment_method,
                'status' => $payment->getStatuses()[$payment->status] ?? $payment->status,
            ],
            'account_receivable' => [
                'id' => $payment->accountReceivable->id,
                'amount' => number_format($payment->accountReceivable->amount, 2),
                'due_date' => $payment->accountReceivable->due_date->format('d/m/Y'),
                'description' => $payment->accountReceivable->description,
                'remaining_amount' => number_format($payment->accountReceivable->remaining_amount, 2),
            ],
            'concept' => [
                'name' => $payment->accountReceivable->concept->name ?? 'N/A',
                'description' => $payment->accountReceivable->concept->description ?? '',
            ],
            'student' => [
                'id' => $payment->accountReceivable->student_id,
                'full_name' => 'Estudiante #' . $payment->accountReceivable->student_id, // This should be replaced with actual student data
            ],
            'school' => [
                'id' => $payment->school_id,
                'name' => 'Institución Educativa', // This should be replaced with actual school data
                'logo' => asset('images/default-logo.png'),
            ],
            'date' => Carbon::now()->format('d/m/Y'),
            'time' => Carbon::now()->format('H:i:s'),
            'generated_by' => $payment->createdBy->name ?? 'Sistema',
        ];
    }

    /**
     * Generate PDF from HTML.
     */
    protected function generatePDF(string $html): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);
    }

    /**
     * Save PDF to storage and return the path.
     */
    protected function savePDF(\Barryvdh\DomPDF\PDF $pdf, Payment $payment, string $type = 'voucher'): string
    {
        $filename = $this->generateFilename($payment, $type);
        $path = 'vouchers/' . $filename;
        
        // Save to storage
        Storage::disk('public')->put($path, $pdf->output());
        
        return $path;
    }

    /**
     * Generate filename for the PDF.
     */
    protected function generateFilename(Payment $payment, string $type = 'voucher'): string
    {
        $date = Carbon::now()->format('Y-m-d');
        $reference = str_replace(['/', '\\', ' '], '-', $payment->reference_number);
        
        return "{$type}_{$reference}_{$date}.pdf";
    }

    /**
     * Generate and save voucher, updating payment record.
     */
    public function generateAndSaveVoucher(Payment $payment): string
    {
        $voucherPath = $this->generatePaymentVoucher($payment);
        
        // Update payment with voucher path
        $payment->update([
            'voucher_path' => $voucherPath
        ]);
        
        return $voucherPath;
    }

    /**
     * Get voucher URL for download.
     */
    public function getVoucherUrl(string $path): string
    {
        return asset('storage/' . $path);
    }

    /**
     * Check if voucher exists.
     */
    public function voucherExists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    /**
     * Delete voucher file.
     */
    public function deleteVoucher(string $path): bool
    {
        if ($this->voucherExists($path)) {
            return Storage::disk('public')->delete($path);
        }
        
        return false;
    }

    /**
     * Regenerate voucher for existing payment.
     */
    public function regenerateVoucher(Payment $payment): string
    {
        // Delete old voucher if exists
        if ($payment->voucher_path) {
            $this->deleteVoucher($payment->voucher_path);
        }
        
        // Generate new voucher
        return $this->generateAndSaveVoucher($payment);
    }
}