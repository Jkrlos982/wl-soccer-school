<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\VoucherTemplate;
use App\Services\VoucherGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Exception;

class VoucherController extends Controller
{
    protected VoucherGenerator $voucherGenerator;

    public function __construct(VoucherGenerator $voucherGenerator)
    {
        $this->voucherGenerator = $voucherGenerator;
    }
    /**
     * Upload a voucher file
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $request->validate([
                'voucher' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
                'payment_id' => 'sometimes|integer|exists:payments,id'
            ]);

            $file = $request->file('voucher');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            
            // Generate unique filename
            $filename = 'voucher_' . Str::uuid() . '.' . $extension;
            
            // Store in school-specific directory
            $path = "vouchers/school_{$schoolId}/{$filename}";
            $storedPath = $file->storeAs('vouchers/school_' . $schoolId, $filename, 'public');

            // If payment_id is provided, update the payment record
            if ($request->has('payment_id')) {
                $payment = Payment::forSchool($schoolId)->findOrFail($request->payment_id);
                
                // Delete old voucher if exists
                if ($payment->voucher_path && Storage::disk('public')->exists($payment->voucher_path)) {
                    Storage::disk('public')->delete($payment->voucher_path);
                }
                
                $payment->voucher_path = $storedPath;
                $payment->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Voucher uploaded successfully',
                'data' => [
                    'path' => $storedPath,
                    'url' => asset('storage/' . $storedPath),
                    'original_name' => $originalName,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading voucher',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Download a voucher file
     */
    public function download(Request $request, int $paymentId): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::forSchool($schoolId)->findOrFail($paymentId);

            if (!$payment->voucher_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'No voucher found for this payment'
                ], 404);
            }

            if (!Storage::disk('public')->exists($payment->voucher_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher file not found on storage'
                ], 404);
            }

            $url = asset('storage/' . $payment->voucher_path);
            $fileInfo = [
                'url' => $url,
                'path' => $payment->voucher_path,
                'size' => Storage::disk('public')->size($payment->voucher_path),
                'last_modified' => Storage::disk('public')->lastModified($payment->voucher_path)
            ];

            return response()->json([
                'success' => true,
                'data' => $fileInfo
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading voucher',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a voucher file
     */
    public function delete(Request $request, int $paymentId): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::forSchool($schoolId)->findOrFail($paymentId);

            if (!$payment->voucher_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'No voucher found for this payment'
                ], 404);
            }

            // Only allow deletion if payment is pending
            if ($payment->status !== Payment::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only delete vouchers from pending payments'
                ], 400);
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($payment->voucher_path)) {
                Storage::disk('public')->delete($payment->voucher_path);
            }

            // Remove path from payment record
            $payment->voucher_path = null;
            $payment->save();

            return response()->json([
                'success' => true,
                'message' => 'Voucher deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting voucher',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get voucher information
     */
    public function show(Request $request, int $paymentId): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::forSchool($schoolId)->findOrFail($paymentId);

            if (!$payment->voucher_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'No voucher found for this payment'
                ], 404);
            }

            $voucherInfo = [
                'path' => $payment->voucher_path,
                'exists' => Storage::disk('public')->exists($payment->voucher_path)
            ];

            if ($voucherInfo['exists']) {
                $voucherInfo['url'] = asset('storage/' . $payment->voucher_path);
                $voucherInfo['size'] = Storage::disk('public')->size($payment->voucher_path);
                $voucherInfo['last_modified'] = Storage::disk('public')->lastModified($payment->voucher_path);
                $fullPath = storage_path('app/public/' . $payment->voucher_path);
                $voucherInfo['mime_type'] = file_exists($fullPath) ? mime_content_type($fullPath) : 'application/octet-stream';
            }

            return response()->json([
                'success' => true,
                'data' => $voucherInfo
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving voucher information',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Replace voucher for a payment
     */
    public function replace(Request $request, int $paymentId): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::forSchool($schoolId)->findOrFail($paymentId);

            // Only allow replacement if payment is pending
            if ($payment->status !== Payment::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only replace vouchers for pending payments'
                ], 400);
            }

            $request->validate([
                'voucher' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120' // Max 5MB
            ]);

            $file = $request->file('voucher');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            
            // Generate unique filename
            $filename = 'voucher_' . Str::uuid() . '.' . $extension;
            
            // Delete old voucher if exists
            if ($payment->voucher_path && Storage::disk('public')->exists($payment->voucher_path)) {
                Storage::disk('public')->delete($payment->voucher_path);
            }
            
            // Store new voucher
            $storedPath = $file->storeAs('vouchers/school_' . $schoolId, $filename, 'public');
            
            $payment->voucher_path = $storedPath;
            $payment->save();

            return response()->json([
                'success' => true,
                'message' => 'Voucher replaced successfully',
                'data' => [
                    'path' => $storedPath,
                    'url' => asset('storage/' . $storedPath),
                    'original_name' => $originalName,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error replacing voucher',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate a payment voucher for a specific payment.
     */
    public function generatePaymentVoucher(Request $request, int $paymentId): JsonResponse
    {
        $request->validate([
            'template_id' => 'nullable|exists:voucher_templates,id',
            'regenerate' => 'boolean'
        ]);

        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::with([
                'accountReceivable.concept',
                'accountReceivable.student',
                'createdBy'
            ])->forSchool($schoolId)->findOrFail($paymentId);

            $templateId = $request->input('template_id');
            $regenerate = $request->boolean('regenerate', false);

            // Check if voucher already exists and regenerate is not requested
            if (!$regenerate && $payment->voucher_url) {
                return response()->json([
                    'success' => true,
                    'message' => 'Comprobante generado exitosamente',
                    'data' => [
                        'voucher_url' => $payment->voucher_url,
                        'exists' => true
                    ]
                ]);
            }

            $voucherUrl = $this->voucherGenerator->generatePaymentVoucher(
                $payment,
                $templateId
            );

            return response()->json([
                'success' => true,
                'message' => 'Comprobante generado exitosamente',
                'data' => [
                    'voucher_url' => $voucherUrl,
                    'exists' => false
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a receipt for a specific payment.
     */
    public function generateReceipt(Request $request, int $paymentId): JsonResponse
    {
        $request->validate([
            'template_id' => 'nullable|exists:voucher_templates,id',
            'regenerate' => 'boolean'
        ]);

        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::with([
                'accountReceivable.concept',
                'accountReceivable.student',
                'createdBy'
            ])->forSchool($schoolId)->findOrFail($paymentId);

            $templateId = $request->input('template_id');
            $regenerate = $request->boolean('regenerate', false);

            // Check if receipt already exists
            if (!$regenerate && $payment->receipt_url) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recibo generado exitosamente',
                    'data' => [
                        'receipt_url' => $payment->receipt_url,
                        'exists' => true
                    ]
                ]);
            }

            $receiptUrl = $this->voucherGenerator->generateReceipt(
                $payment,
                $templateId
            );

            return response()->json([
                'success' => true,
                'message' => 'Recibo generado exitosamente',
                'data' => [
                    'receipt_url' => $receiptUrl,
                    'exists' => false
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el recibo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a generated voucher or receipt.
     */
    public function downloadGenerated(Request $request, int $paymentId)
    {
        $request->validate([
            'type' => ['required', Rule::in(['voucher', 'receipt'])]
        ]);

        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::forSchool($schoolId)->findOrFail($paymentId);

            $type = $request->input('type');
            $url = $type === 'voucher' ? $payment->voucher_url : $payment->receipt_url;

            if (!$url) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no ha sido generado'
                ], 404);
            }

            // Extract file path from URL
            $path = str_replace(asset('storage/'), '', $url);
            
            if (!Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no existe'
                ], 404);
            }

            $filename = $type === 'voucher' 
                ? "comprobante_{$payment->reference_number}.pdf"
                : "recibo_{$payment->reference_number}.pdf";

            return response()->download(
                Storage::disk('public')->path($path),
                $filename
            );

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el documento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available voucher templates.
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', Rule::in([
                VoucherTemplate::TYPE_PAYMENT_VOUCHER,
                VoucherTemplate::TYPE_RECEIPT
            ])]
        ]);

        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $type = $request->input('type');

            $query = VoucherTemplate::query()
                ->where(function ($q) use ($schoolId) {
                    $q->whereNull('school_id')
                      ->orWhere('school_id', $schoolId);
                })
                ->orderBy('is_default', 'desc')
                ->orderBy('name');

            if ($type) {
                $query->where('type', $type);
            }

            $templates = $query->get([
                'id',
                'name',
                'type',
                'is_default',
                'school_id'
            ]);

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las plantillas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}