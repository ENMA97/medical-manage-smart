<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\ItemCategoryResource;
use App\Models\Inventory\ItemCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ItemCategoryController extends Controller
{
    /**
     * قائمة الفئات
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ItemCategory::with(['parent'])
            ->withCount('items')
            ->when($request->parent_id, fn($q, $id) => $q->where('parent_id', $id))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name_ar');

        $categories = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return ItemCategoryResource::collection($categories);
    }

    /**
     * شجرة الفئات
     */
    public function tree(): AnonymousResourceCollection
    {
        $categories = ItemCategory::with(['children.children'])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return ItemCategoryResource::collection($categories);
    }

    /**
     * إنشاء فئة جديدة
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بإنشاء فئات');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:item_categories,code'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'uuid', 'exists:item_categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $category = ItemCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الفئة بنجاح',
            'data' => new ItemCategoryResource($category->load('parent')),
        ], 201);
    }

    /**
     * عرض فئة
     */
    public function show(ItemCategory $category): ItemCategoryResource
    {
        return new ItemCategoryResource(
            $category->load(['parent', 'children'])->loadCount('items')
        );
    }

    /**
     * تحديث فئة
     */
    public function update(Request $request, ItemCategory $category): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بتعديل الفئات');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:item_categories,code,' . $category->id],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'uuid', 'exists:item_categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        // منع جعل الفئة parent لنفسها
        if (isset($validated['parent_id']) && $validated['parent_id'] === $category->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن جعل الفئة تابعة لنفسها',
            ], 422);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الفئة بنجاح',
            'data' => new ItemCategoryResource($category->fresh('parent')),
        ]);
    }

    /**
     * حذف فئة
     */
    public function destroy(ItemCategory $category): JsonResponse
    {
        if (Gate::denies('inventory.manage')) {
            abort(403, 'غير مصرح لك بحذف الفئات');
        }

        // التحقق من عدم وجود أصناف
        if ($category->items()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الفئة لوجود أصناف مرتبطة بها',
            ], 422);
        }

        // التحقق من عدم وجود فئات فرعية
        if ($category->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الفئة لوجود فئات فرعية',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الفئة بنجاح',
        ]);
    }
}
