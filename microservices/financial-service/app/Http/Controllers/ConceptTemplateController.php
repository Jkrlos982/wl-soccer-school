<?php

namespace App\Http\Controllers;

use App\Models\ConceptTemplate;
use App\Models\FinancialConcept;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ConceptTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ConceptTemplate::query();

        // Filtros
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_system')) {
            $query->where('is_system', $request->boolean('is_system'));
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $templates = $query->paginate($perPage);

        return response()->json($templates);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|max:100|unique:concept_templates,code',
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'default_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        $data['is_system'] = false;

        $template = ConceptTemplate::create($data);

        return response()->json([
            'message' => 'Template created successfully',
            'data' => $template
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ConceptTemplate $conceptTemplate): JsonResponse
    {
        $conceptTemplate->load(['financialConcepts', 'creator', 'updater']);
        
        return response()->json([
            'data' => $conceptTemplate
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ConceptTemplate $conceptTemplate): JsonResponse
    {
        // No permitir editar templates del sistema
        if ($conceptTemplate->is_system) {
            return response()->json([
                'message' => 'System templates cannot be modified'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|max:100|unique:concept_templates,code,' . $conceptTemplate->id,
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'default_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = Auth::id();

        $conceptTemplate->update($data);

        return response()->json([
            'message' => 'Template updated successfully',
            'data' => $conceptTemplate->fresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConceptTemplate $conceptTemplate): JsonResponse
    {
        // No permitir eliminar templates del sistema
        if ($conceptTemplate->is_system) {
            return response()->json([
                'message' => 'System templates cannot be deleted'
            ], 403);
        }

        // Verificar si tiene conceptos financieros asociados
        if ($conceptTemplate->financialConcepts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete template with associated financial concepts'
            ], 409);
        }

        $conceptTemplate->delete();

        return response()->json([
            'message' => 'Template deleted successfully'
        ]);
    }

    /**
     * Get active templates.
     */
    public function getActive(): JsonResponse
    {
        $templates = ConceptTemplate::active()->orderBy('name')->get();
        
        return response()->json([
            'data' => $templates
        ]);
    }

    /**
     * Get system templates (public endpoint).
     */
    public function getSystemTemplates(): JsonResponse
    {
        $templates = ConceptTemplate::system()->active()->orderBy('name')->get();
        
        return response()->json([
            'data' => $templates
        ]);
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(ConceptTemplate $conceptTemplate): JsonResponse
    {
        $duplicated = $conceptTemplate->duplicate([
            'name' => $conceptTemplate->name . ' (Copy)',
            'code' => $conceptTemplate->code . '_copy_' . time()
        ]);

        return response()->json([
            'message' => 'Template duplicated successfully',
            'data' => $duplicated
        ], 201);
    }

    /**
     * Create a financial concept from template.
     */
    public function createConcept(Request $request, ConceptTemplate $conceptTemplate): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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

        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        
        $concept = $conceptTemplate->createFinancialConcept($data);

        return response()->json([
            'message' => 'Financial concept created from template successfully',
            'data' => $concept
        ], 201);
    }

    /**
     * Toggle template status.
     */
    public function toggleStatus(ConceptTemplate $conceptTemplate): JsonResponse
    {
        // No permitir cambiar estado de templates del sistema
        if ($conceptTemplate->is_system) {
            return response()->json([
                'message' => 'System template status cannot be changed'
            ], 403);
        }

        $conceptTemplate->update([
            'is_active' => !$conceptTemplate->is_active,
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Template status updated successfully',
            'data' => $conceptTemplate->fresh()
        ]);
    }
}