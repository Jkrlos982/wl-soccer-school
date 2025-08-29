<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CategoryController extends BaseController
{
    use AuthorizesRequests;
    
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::with(['coach'])
            ->where('school_id', $request->user()->school_id ?? 1); // TODO: Obtener school_id del usuario autenticado
            
        // Filtros opcionales
        if ($request->has('active')) {
            $query->active();
        }
        
        if ($request->has('gender')) {
            $query->byGender($request->gender);
        }
        
        $categories = $query->paginate(15);
        
        return CategoryResource::collection($categories);
    }
    
    /**
     * Store a newly created category
     */
    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create([
            'school_id' => $request->user()->school_id ?? 1, // TODO: Obtener school_id del usuario autenticado
            ...$request->validated()
        ]);
        
        $category->load(['coach']);
        
        return new CategoryResource($category);
    }
    
    /**
     * Display the specified category
     */
    public function show(Category $category)
    {
        // $this->authorize('view', $category); // TODO: Implementar policies
        
        $category->load(['coach']);
        
        return new CategoryResource($category);
    }
    
    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        // $this->authorize('update', $category); // TODO: Implementar policies
        
        $category->update($request->validated());
        $category->load(['coach']);
        
        return new CategoryResource($category);
    }
    
    /**
     * Remove the specified category
     */
    public function destroy(Category $category)
    {
        // $this->authorize('delete', $category); // TODO: Implementar policies
        
        // Verificar si tiene jugadores asignados
        // if ($category->players()->exists()) {
        //     return $this->respondWithError(
        //         'No se puede eliminar una categoría con jugadores asignados',
        //         422,
        //         'CATEGORY_HAS_PLAYERS'
        //     );
        // }
        
        $category->delete();
        
        return $this->respondWithSuccess(
            null,
            'Categoría eliminada exitosamente'
        );
    }
}