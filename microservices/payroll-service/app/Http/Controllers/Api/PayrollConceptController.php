<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollConcept;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PayrollConceptController extends Controller
{
    /**
     * Display a listing of payroll concepts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PayrollConcept::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by active status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        $concepts = $query->orderBy('type')
            ->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $concepts,
            'message' => 'Conceptos de nómina obtenidos exitosamente'
        ]);
    }

    /**
     * Store a newly created payroll concept.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:payroll_concepts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction,tax,benefit',
            'description' => 'nullable|string|max:500',
            'calculation_type' => 'required|in:fixed,percentage,formula',
            'default_value' => 'nullable|numeric|min:0',
            'formula' => 'nullable|string|max:500',
            'is_taxable' => 'boolean',
            'affects_social_security' => 'boolean',
            'is_mandatory' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'status' => 'in:active,inactive'
        ]);

        // Validate calculation type requirements
        if ($validated['calculation_type'] === 'fixed' && empty($validated['default_value'])) {
            return response()->json([
                'success' => false,
                'message' => 'El valor es requerido para conceptos de tipo fijo'
            ], 422);
        }

        if ($validated['calculation_type'] === 'formula' && empty($validated['formula'])) {
            return response()->json([
                'success' => false,
                'message' => 'La fórmula es requerida para conceptos de tipo fórmula'
            ], 422);
        }

        $concept = PayrollConcept::create($validated);

        return response()->json([
            'success' => true,
            'data' => $concept,
            'message' => 'Concepto de nómina creado exitosamente'
        ], 201);
    }

    /**
     * Display the specified payroll concept.
     */
    public function show(PayrollConcept $payrollConcept): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $payrollConcept,
            'message' => 'Concepto de nómina obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified payroll concept.
     */
    public function update(Request $request, PayrollConcept $payrollConcept): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('payroll_concepts', 'code')->ignore($payrollConcept->id)
            ],
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:earning,deduction,tax,benefit',
            'description' => 'nullable|string|max:500',
            'calculation_type' => 'sometimes|in:fixed,percentage,formula',
            'default_value' => 'nullable|numeric|min:0',
            'formula' => 'nullable|string|max:500',
            'is_taxable' => 'boolean',
            'affects_social_security' => 'boolean',
            'is_mandatory' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'status' => 'in:active,inactive'
        ]);

        $payrollConcept->update($validated);

        return response()->json([
            'success' => true,
            'data' => $payrollConcept->fresh(),
            'message' => 'Concepto de nómina actualizado exitosamente'
        ]);
    }

    /**
     * Remove the specified payroll concept.
     */
    public function destroy(PayrollConcept $payrollConcept): JsonResponse
    {
        // Check if concept is being used in any payroll
        if ($payrollConcept->payrollDetails()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un concepto que está siendo utilizado en nóminas'
            ], 422);
        }

        $payrollConcept->delete();

        return response()->json([
            'success' => true,
            'message' => 'Concepto de nómina eliminado exitosamente'
        ]);
    }

    /**
     * Activate a payroll concept.
     */
    public function activate(PayrollConcept $concept): JsonResponse
    {
        $concept->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'data' => $concept->fresh(),
            'message' => 'Concepto de nómina activado exitosamente'
        ]);
    }

    /**
     * Deactivate a payroll concept.
     */
    public function deactivate(PayrollConcept $concept): JsonResponse
    {
        // Check if it's a mandatory concept
        if ($concept->is_mandatory) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede desactivar un concepto obligatorio'
            ], 422);
        }

        $concept->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'data' => $concept->fresh(),
            'message' => 'Concepto de nómina desactivado exitosamente'
        ]);
    }

    /**
     * Get payroll concepts by type.
     */
    public function byType(string $type): JsonResponse
    {
        $validTypes = ['earning', 'deduction', 'tax', 'benefit'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de concepto inválido'
            ], 422);
        }

        $concepts = PayrollConcept::where('type', $type)
            ->where('status', 'active')
            ->orderBy('priority_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $concepts,
            'message' => "Conceptos de tipo {$type} obtenidos exitosamente"
        ]);
    }

    /**
     * Get all active payroll concepts.
     */
    public function active(): JsonResponse
    {
        $concepts = PayrollConcept::where('status', 'active')
            ->orderBy('type')
            ->orderBy('priority_order')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return response()->json([
            'success' => true,
            'data' => $concepts,
            'message' => 'Conceptos activos obtenidos exitosamente'
        ]);
    }

    /**
     * Get payroll concepts configuration for settings.
     */
    public function config(): JsonResponse
    {
        $concepts = PayrollConcept::select('id', 'code', 'name', 'type', 'category', 'status', 'is_mandatory')
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        $summary = [
            'total_concepts' => PayrollConcept::count(),
            'active_concepts' => PayrollConcept::where('status', 'active')->count(),
            'mandatory_concepts' => PayrollConcept::where('is_mandatory', true)->count(),
            'by_type' => PayrollConcept::groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'concepts' => $concepts,
                'summary' => $summary
            ],
            'message' => 'Configuración de conceptos obtenida exitosamente'
        ]);
    }

    /**
     * Validate a payroll concept formula.
     */
    public function validateFormula(Request $request): JsonResponse
    {
        $request->validate([
            'formula' => 'required|string|max:500',
            'base_concept_codes' => 'nullable|array',
            'base_concept_codes.*' => 'string|exists:payroll_concepts,code'
        ]);

        // Basic formula validation (you can expand this with more sophisticated validation)
        $formula = $request->formula;
        $baseConcepts = $request->base_concept_codes ?? [];

        // Check if all variables in formula exist in base concepts
        preg_match_all('/\{([A-Z0-9_]+)\}/', $formula, $matches);
        $formulaVariables = $matches[1] ?? [];

        $missingConcepts = array_diff($formulaVariables, $baseConcepts);

        if (!empty($missingConcepts)) {
            return response()->json([
                'success' => false,
                'message' => 'Variables no definidas en la fórmula: ' . implode(', ', $missingConcepts)
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fórmula válida',
            'data' => [
                'variables_found' => $formulaVariables,
                'base_concepts_used' => $baseConcepts
            ]
        ]);
    }
}