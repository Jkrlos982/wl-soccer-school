<?php

namespace App\Http\Controllers;

use App\Models\FinancialConcept;
use App\Models\ConceptTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialConceptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FinancialConcept::query();

        // Filtros
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('school_id')) {
            $query->forSchool($request->school_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_default')) {
            $query->where('is_default', $request->boolean('is_default'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Incluir relaciones
        $query->with(['template', 'creator', 'updater']);

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $concepts = $query->paginate($perPage);

        return response()->json($concepts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|max:100|unique:financial_concepts,code',
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'school_id' => 'nullable|integer',
            'template_id' => 'nullable|exists:concept_templates,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        $data['is_default'] = false;

        $concept = FinancialConcept::create($data);
        $concept->load(['template', 'creator']);

        return response()->json([
            'message' => 'Financial concept created successfully',
            'data' => $concept
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FinancialConcept $financialConcept): JsonResponse
    {
        $financialConcept->load(['template', 'creator', 'updater', 'transactions']);
        
        return response()->json([
            'data' => $financialConcept
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialConcept $financialConcept): JsonResponse
    {
        // No permitir editar conceptos por defecto del sistema
        if ($financialConcept->is_default) {
            return response()->json([
                'message' => 'Default system concepts cannot be modified'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|max:100|unique:financial_concepts,code,' . $financialConcept->id,
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'school_id' => 'nullable|integer',
            'template_id' => 'nullable|exists:concept_templates,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = Auth::id();

        $financialConcept->update($data);
        $financialConcept->load(['template', 'creator', 'updater']);

        return response()->json([
            'message' => 'Financial concept updated successfully',
            'data' => $financialConcept
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialConcept $financialConcept): JsonResponse
    {
        // No permitir eliminar conceptos por defecto del sistema
        if ($financialConcept->is_default) {
            return response()->json([
                'message' => 'Default system concepts cannot be deleted'
            ], 403);
        }

        // Verificar si tiene transacciones asociadas
        if ($financialConcept->transactions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete concept with associated transactions'
            ], 409);
        }

        $financialConcept->delete();

        return response()->json([
            'message' => 'Financial concept deleted successfully'
        ]);
    }

    /**
     * Get active concepts.
     */
    public function getActive(Request $request): JsonResponse
    {
        $query = FinancialConcept::active();

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('school_id')) {
            $query->forSchool($request->school_id);
        }

        $concepts = $query->orderBy('name')->get();
        
        return response()->json([
            'data' => $concepts
        ]);
    }

    /**
     * Get default concepts (public endpoint).
     */
    public function getDefaults(): JsonResponse
    {
        $concepts = FinancialConcept::defaults()->active()->orderBy('name')->get();
        
        return response()->json([
            'data' => $concepts
        ]);
    }

    /**
     * Get concepts by type.
     */
    public function getByType(string $type, Request $request): JsonResponse
    {
        if (!in_array($type, ['income', 'expense'])) {
            return response()->json([
                'message' => 'Invalid type. Must be income or expense'
            ], 400);
        }

        $query = FinancialConcept::ofType($type)->active();

        if ($request->has('school_id')) {
            $query->forSchool($request->school_id);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $concepts = $query->orderBy('name')->get();
        
        return response()->json([
            'data' => $concepts
        ]);
    }

    /**
     * Get concepts by category.
     */
    public function getByCategory(string $category, Request $request): JsonResponse
    {
        $query = FinancialConcept::byCategory($category)->active();

        if ($request->has('school_id')) {
            $query->forSchool($request->school_id);
        }

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        $concepts = $query->orderBy('name')->get();
        
        return response()->json([
            'data' => $concepts
        ]);
    }

    /**
     * Toggle concept status.
     */
    public function toggleStatus(FinancialConcept $financialConcept): JsonResponse
    {
        // No permitir cambiar estado de conceptos por defecto del sistema
        if ($financialConcept->is_default) {
            return response()->json([
                'message' => 'Default system concept status cannot be changed'
            ], 403);
        }

        $financialConcept->update([
            'is_active' => !$financialConcept->is_active,
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Concept status updated successfully',
            'data' => $financialConcept->fresh()
        ]);
    }

    /**
     * Create concept from template.
     */
    public function createFromTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:concept_templates,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:100|unique:financial_concepts,code',
            'school_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $template = ConceptTemplate::findOrFail($request->template_id);
        
        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        
        $concept = $template->createFinancialConcept($data);
        $concept->load(['template', 'creator']);

        return response()->json([
            'message' => 'Financial concept created from template successfully',
            'data' => $concept
        ], 201);
    }

    /**
     * Get concept statistics.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $query = FinancialConcept::query();

        if ($request->has('school_id')) {
            $query->forSchool($request->school_id);
        }

        $stats = [
            'total' => $query->count(),
            'active' => $query->where('is_active', true)->count(),
            'inactive' => $query->where('is_active', false)->count(),
            'income' => $query->ofType('income')->count(),
            'expense' => $query->ofType('expense')->count(),
            'defaults' => $query->where('is_default', true)->count(),
            'custom' => $query->where('is_default', false)->count(),
            'by_category' => $query->select('category', DB::raw('count(*) as total'))
                                  ->groupBy('category')
                                  ->pluck('total', 'category')
                                  ->toArray()
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Bulk update concepts.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'concept_ids' => 'required|array',
            'concept_ids.*' => 'integer|exists:financial_concepts,id',
            'action' => 'required|in:activate,deactivate,delete',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $conceptIds = $request->concept_ids;
        $action = $request->action;
        $data = $request->get('data', []);

        // No permitir operaciones en conceptos por defecto
        $defaultConcepts = FinancialConcept::whereIn('id', $conceptIds)
                                          ->where('is_default', true)
                                          ->count();

        if ($defaultConcepts > 0) {
            return response()->json([
                'message' => 'Cannot perform bulk operations on default system concepts'
            ], 403);
        }

        $updatedCount = 0;

        switch ($action) {
            case 'activate':
                $updatedCount = FinancialConcept::whereIn('id', $conceptIds)
                                               ->update([
                                                   'is_active' => true,
                                                   'updated_by' => Auth::id()
                                               ]);
                break;

            case 'deactivate':
                $updatedCount = FinancialConcept::whereIn('id', $conceptIds)
                                               ->update([
                                                   'is_active' => false,
                                                   'updated_by' => Auth::id()
                                               ]);
                break;

            case 'delete':
                // Verificar que no tengan transacciones asociadas
                $conceptsWithTransactions = FinancialConcept::whereIn('id', $conceptIds)
                                                           ->whereHas('transactions')
                                                           ->count();

                if ($conceptsWithTransactions > 0) {
                    return response()->json([
                        'message' => 'Cannot delete concepts with associated transactions'
                    ], 409);
                }

                $updatedCount = FinancialConcept::whereIn('id', $conceptIds)->delete();
                break;
        }

        return response()->json([
            'message' => "Bulk {$action} completed successfully",
            'updated_count' => $updatedCount
        ]);
    }
}
