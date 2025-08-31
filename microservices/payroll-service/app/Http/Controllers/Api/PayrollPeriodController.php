<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PayrollPeriodController extends Controller
{
    /**
     * Display a listing of payroll periods.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PayrollPeriod::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by year
        if ($request->has('year')) {
            $query->whereYear('start_date', $request->year);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $periods = $query->orderBy('start_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $periods,
            'message' => 'Períodos de nómina obtenidos exitosamente'
        ]);
    }

    /**
     * Store a newly created payroll period.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:monthly,biweekly,weekly,special',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'pay_date' => 'required|date|after_or_equal:end_date',
            'description' => 'nullable|string|max:500',
            'is_special' => 'boolean'
        ]);

        // Check for overlapping periods
        $overlapping = PayrollPeriod::where(function ($query) use ($validated) {
            $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                  ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                  ->orWhere(function ($q) use ($validated) {
                      $q->where('start_date', '<=', $validated['start_date'])
                        ->where('end_date', '>=', $validated['end_date']);
                  });
        })->exists();

        if ($overlapping) {
            throw ValidationException::withMessages([
                'dates' => 'Ya existe un período de nómina que se superpone con las fechas seleccionadas.'
            ]);
        }

        // Map field names for the model
        $data = $validated;
        if (isset($data['type'])) {
            $data['period_type'] = $data['type'];
            unset($data['type']);
        }
        
        // Auto-calculate year, month, and period_number from start_date
        $startDate = \Carbon\Carbon::parse($data['start_date']);
        $data['year'] = $startDate->year;
        $data['month'] = $startDate->month;
        
        // Calculate period number based on type
        if ($data['period_type'] === 'monthly') {
            $data['period_number'] = $startDate->month;
        } elseif ($data['period_type'] === 'biweekly') {
            $data['period_number'] = ceil($startDate->dayOfYear / 14);
        } elseif ($data['period_type'] === 'weekly') {
            $data['period_number'] = $startDate->weekOfYear;
        } else {
            $data['period_number'] = 1; // Default for special periods
        }
        
        $period = PayrollPeriod::create(array_merge($data, [
            'status' => 'draft'
        ]));

        return response()->json([
            'success' => true,
            'data' => $period,
            'message' => 'Período de nómina creado exitosamente'
        ], 201);
    }

    /**
     * Display the specified payroll period.
     */
    public function show(PayrollPeriod $payrollPeriod): JsonResponse
    {
        $payrollPeriod->load(['payrolls.employee']);

        return response()->json([
            'success' => true,
            'data' => $payrollPeriod,
            'message' => 'Período de nómina obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified payroll period.
     */
    public function update(Request $request, PayrollPeriod $payroll_period): JsonResponse
    {
        // Only allow updates if period is in draft status
        if ($payroll_period->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden modificar períodos en estado draft'
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:monthly,biweekly,weekly,special',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'pay_date' => 'sometimes|date|after_or_equal:end_date',
            'description' => 'nullable|string|max:500',
            'is_special' => 'boolean'
        ]);

        // Map field names for the model
        $data = $validated;
        if (isset($data['type'])) {
            $data['period_type'] = $data['type'];
            unset($data['type']);
        }
        
        // Auto-calculate year, month, and period_number if start_date is updated
        if (isset($data['start_date'])) {
            $startDate = \Carbon\Carbon::parse($data['start_date']);
            $data['year'] = $startDate->year;
            $data['month'] = $startDate->month;
            
            // Calculate period number based on type
            $periodType = $data['period_type'] ?? $payroll_period->period_type;
            switch ($periodType) {
                case 'monthly':
                    $data['period_number'] = $startDate->month;
                    break;
                case 'biweekly':
                    $data['period_number'] = ceil($startDate->dayOfYear / 14);
                    break;
                case 'weekly':
                    $data['period_number'] = $startDate->weekOfYear;
                    break;
                default:
                    $data['period_number'] = 1;
            }
        }

        $payroll_period->update($data);

        return response()->json([
            'success' => true,
            'data' => $payroll_period->fresh(),
            'message' => 'Período de nómina actualizado exitosamente'
        ]);
    }

    /**
     * Remove the specified payroll period.
     */
    public function destroy(PayrollPeriod $payrollPeriod): JsonResponse
    {
        // Only allow deletion if period is in draft status and has no payrolls
        if ($payrollPeriod->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden eliminar períodos en estado draft'
            ], 422);
        }

        if ($payrollPeriod->payrolls()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un período que tiene nóminas asociadas'
            ], 422);
        }

        $payrollPeriod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Período de nómina eliminado exitosamente'
        ]);
    }

    /**
     * Open a payroll period for processing.
     */
    public function open(PayrollPeriod $payroll_period): JsonResponse
    {
        if ($payroll_period->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden abrir períodos en estado draft'
            ], 422);
        }

        $payroll_period->update([
            'status' => 'processing',
            'opened_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $payroll_period->fresh(),
            'message' => 'Período de nómina abierto exitosamente'
        ]);
    }

    /**
     * Close a payroll period.
     */
    public function close(PayrollPeriod $payroll_period): JsonResponse
    {
        if (!in_array($payroll_period->status, ['open', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cerrar períodos abiertos o en procesamiento'
            ], 422);
        }

        // Check if all payrolls are processed
        $pendingPayrolls = $payroll_period->payrolls()
            ->whereIn('status', ['draft', 'processing'])
            ->count();

        if ($pendingPayrolls > 0) {
            return response()->json([
                'success' => false,
                'message' => "Hay {$pendingPayrolls} nóminas pendientes de procesar"
            ], 422);
        }

        $payroll_period->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $payroll_period->fresh(),
            'message' => 'Período de nómina cerrado exitosamente'
        ]);
    }

    /**
     * Reopen a closed payroll period.
     */
    public function reopen(PayrollPeriod $payroll_period): JsonResponse
    {
        if ($payroll_period->status !== 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden reabrir períodos cerrados'
            ], 422);
        }

        $payroll_period->update([
            'status' => 'open',
            'closed_at' => null
        ]);

        return response()->json([
            'success' => true,
            'data' => $payroll_period->fresh(),
            'message' => 'Período de nómina reabierto exitosamente'
        ]);
    }

    /**
     * Get payrolls for a specific period.
     */
    public function payrolls(PayrollPeriod $payroll_period): JsonResponse
    {
        $payrolls = $payroll_period->payrolls()
            ->with(['employee:id,first_name,last_name,identification_number'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payrolls,
            'message' => 'Nóminas del período obtenidas exitosamente'
        ]);
    }

    /**
     * Get summary statistics for a payroll period.
     */
    public function summary(PayrollPeriod $payroll_period): JsonResponse
    {
        $payrolls = $payroll_period->payrolls()->get();

        $summary = [
            'period_id' => $payroll_period->id,
            'total_employees' => $payrolls->count(),
            'total_payrolls' => $payrolls->count(),
            'total_gross_salary' => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'total_employer_contributions' => $payrolls->sum('employer_contributions'),
            'status_breakdown' => $payrolls->groupBy('status')
                ->map(function ($group) {
                    return $group->count();
                })
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Resumen del período obtenido exitosamente'
        ]);
    }

    /**
     * Get the current active payroll period.
     */
    public function current(): JsonResponse
    {
        $currentPeriod = PayrollPeriod::where('status', 'processing')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (!$currentPeriod) {
            return response()->json([
                'success' => false,
                'message' => 'No hay período de nómina activo actualmente'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $currentPeriod,
            'message' => 'Período actual obtenido exitosamente'
        ]);
    }

    /**
     * Get all active payroll periods.
     */
    public function active(): JsonResponse
    {
        $activePeriods = PayrollPeriod::whereIn('status', ['open', 'processing'])
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activePeriods,
            'message' => 'Períodos activos obtenidos exitosamente'
        ]);
    }
}