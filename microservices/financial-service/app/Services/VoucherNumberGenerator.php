<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;

class VoucherNumberGenerator
{
    /**
     * Generate a unique voucher number.
     * Format: COMP-YYYY-MM-NNNN
     * Example: COMP-2024-01-0001
     */
    public function generateNumber(string $type = 'COMP', ?int $schoolId = null): string
    {
        $date = Carbon::now();
        $prefix = $type . '-' . $date->format('Y-m');
        
        // Build query to find the last number
        $query = Payment::where('reference_number', 'like', $prefix . '%')
            ->orderBy('reference_number', 'desc');
            
        // Filter by school if provided
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        $lastNumber = $query->value('reference_number');
        
        // Extract sequence number and increment
        $sequence = $lastNumber ? intval(substr($lastNumber, -4)) + 1 : 1;
        
        return $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate payment voucher number.
     */
    public function generatePaymentVoucherNumber(?int $schoolId = null): string
    {
        return $this->generateNumber('COMP', $schoolId);
    }

    /**
     * Generate receipt number.
     */
    public function generateReceiptNumber(?int $schoolId = null): string
    {
        return $this->generateNumber('REC', $schoolId);
    }

    /**
     * Generate invoice number.
     */
    public function generateInvoiceNumber(?int $schoolId = null): string
    {
        return $this->generateNumber('INV', $schoolId);
    }

    /**
     * Validate voucher number format.
     */
    public function validateFormat(string $number): bool
    {
        // Pattern: TYPE-YYYY-MM-NNNN
        $pattern = '/^[A-Z]{2,4}-\d{4}-\d{2}-\d{4}$/';
        return preg_match($pattern, $number) === 1;
    }

    /**
     * Parse voucher number components.
     */
    public function parseNumber(string $number): array
    {
        if (!$this->validateFormat($number)) {
            throw new \InvalidArgumentException('Invalid voucher number format');
        }

        $parts = explode('-', $number);
        
        return [
            'type' => $parts[0],
            'year' => (int) $parts[1],
            'month' => (int) $parts[2],
            'sequence' => (int) $parts[3]
        ];
    }

    /**
     * Get the next available sequence number for a given prefix.
     */
    public function getNextSequence(string $prefix, ?int $schoolId = null): int
    {
        $query = Payment::where('reference_number', 'like', $prefix . '%')
            ->orderBy('reference_number', 'desc');
            
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        $lastNumber = $query->value('reference_number');
        
        return $lastNumber ? intval(substr($lastNumber, -4)) + 1 : 1;
    }

    /**
     * Check if a voucher number already exists.
     */
    public function exists(string $number, ?int $schoolId = null): bool
    {
        $query = Payment::where('reference_number', $number);
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        return $query->exists();
    }

    /**
     * Generate a unique number ensuring it doesn't exist.
     */
    public function generateUniqueNumber(string $type = 'COMP', ?int $schoolId = null, int $maxAttempts = 10): string
    {
        $attempts = 0;
        
        do {
            $number = $this->generateNumber($type, $schoolId);
            $attempts++;
            
            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException('Unable to generate unique voucher number after ' . $maxAttempts . ' attempts');
            }
            
        } while ($this->exists($number, $schoolId));
        
        return $number;
    }
}