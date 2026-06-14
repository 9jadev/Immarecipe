<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariantOption;
use App\Models\ProductVariantValue;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->get('category'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'featured') {
                $query->where('is_featured', true);
            }
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->get('stock_status'));
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        $categories = Category::active()->ordered()->get();

        return Inertia::render('admin/products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status', 'stock_status']),
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = Category::active()->ordered()->get();

        return Inertia::render('admin/products/Form', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'stock_count' => ['required', 'integer', 'min:0'],
            'stock_status' => ['required', 'in:in_stock,low_stock,out_of_stock'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'weight_unit' => ['nullable', 'string', 'max:10'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            // Variant validation
            'has_variants' => ['boolean'],
            'variant_options' => ['nullable', 'array'],
            'variant_options.*.name' => ['required_with:variant_options', 'string', 'max:255'],
            'variant_options.*.values' => ['required_with:variant_options', 'array', 'min:1'],
            'variant_options.*.values.*.id' => ['nullable'],
            'variant_options.*.values.*.value' => ['required', 'string', 'max:255'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100', 'unique:product_variants,sku'],
            'variants.*.variant_name' => ['required_with:variants', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.compare_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.stock_count' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.is_active' => ['boolean'],
            'variants.*.value_ids' => ['nullable', 'array'],
        ]);

        // Handle image uploads
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imageUrls[] = Storage::url($path);
            }
        }
        $validated['images'] = $imageUrls;

        DB::transaction(function () use ($validated, $request) {
            $product = Product::create($validated);
            $product->updateStockStatus();

            // Handle variants
            if (!empty($validated['has_variants']) && !empty($validated['variant_options'])) {
                $this->createVariants($product, $validated['variant_options'], $validated['variants'] ?? []);
            }
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $categories = Category::active()->ordered()->get();

        // Load variants with their options and values
        $product->load([
            'variantOptions.values',
            'variants.variantValues',
        ]);

        // Transform variant options for the form
        $variantOptions = $product->variantOptions->map(function ($option) {
            return [
                'id' => $option->id,
                'name' => $option->name,
                'values' => $option->values->map(function ($value) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                    ];
                })->toArray(),
            ];
        })->toArray();

        // Transform variants for the form
        $variants = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'variant_name' => $variant->variant_name,
                'price' => $variant->price ?? '',
                'compare_price' => $variant->compare_price ?? '',
                'stock_count' => $variant->stock_count,
                'is_active' => $variant->is_active,
                'value_ids' => $variant->variantValues->pluck('id')->toArray(),
            ];
        })->toArray();

        // Create a clean product array with the transformed data
        $productData = $product->toArray();
        $productData['variantOptions'] = $variantOptions;
        $productData['variants'] = $variants;

        return Inertia::render('admin/products/Form', [
            'product' => $productData,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,'.$product->id],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,'.$product->id],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'stock_count' => ['required', 'integer', 'min:0'],
            'stock_status' => ['required', 'in:in_stock,low_stock,out_of_stock'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'weight_unit' => ['nullable', 'string', 'max:10'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'existing_images' => ['nullable', 'array'],
            'existing_images.*' => ['string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            // Variant validation
            'has_variants' => ['boolean'],
            'variant_options' => ['nullable', 'array'],
            'variant_options.*.id' => ['nullable', 'integer', 'exists:product_variant_options,id'],
            'variant_options.*.name' => ['required_with:variant_options', 'string', 'max:255'],
            'variant_options.*.values' => ['required_with:variant_options', 'array', 'min:1'],
            'variant_options.*.values.*.id' => ['nullable', 'integer', 'exists:product_variant_values,id'],
            'variant_options.*.values.*.value' => ['required', 'string', 'max:255'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.variant_name' => ['required_with:variants', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.compare_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.stock_count' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.is_active' => ['boolean'],
            'variants.*.value_ids' => ['nullable', 'array'],
        ]);

        // Handle image uploads
        $imageUrls = $request->input('existing_images', []);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imageUrls[] = Storage::url($path);
            }
        }
        $validated['images'] = $imageUrls;

        // Remove old images that are no longer in the list
        if ($product->images) {
            foreach ($product->images as $oldImage) {
                if (! in_array($oldImage, $imageUrls)) {
                    $relativePath = str_replace('/storage/', '', $oldImage);
                    Storage::disk('public')->delete($relativePath);
                }
            }
        }

        DB::transaction(function () use ($validated, $request, $product) {
            $product->update($validated);
            $product->updateStockStatus();

            // Handle variants - delete old and create new
            if (!empty($validated['has_variants'])) {
                $this->updateVariants($product, $validated['variant_options'] ?? [], $validated['variants'] ?? []);
            } else {
                // Remove all variants if has_variants is false
                $product->variantOptions()->delete();
                $product->variants()->delete();
            }
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Toggle the active status of the product.
     */
    public function toggleActive(Product $product)
    {
        $product->update([
            'is_active' => ! $product->is_active,
        ]);

        return back()->with('success', 'Product status updated successfully.');
    }

    /**
     * Toggle the featured status of the product.
     */
    public function toggleFeatured(Product $product)
    {
        $product->update([
            'is_featured' => ! $product->is_featured,
        ]);

        return back()->with('success', 'Product featured status updated successfully.');
    }

    /**
     * Create variants for a product.
     */
    private function createVariants(Product $product, array $optionsData, array $variantsData): void
    {
        $valueMap = []; // Maps [option_name][value] => value_id

        // Create variant options and values
        foreach ($optionsData as $index => $optionData) {
            $option = ProductVariantOption::create([
                'product_id' => $product->id,
                'name' => $optionData['name'],
                'position' => $index,
            ]);

            foreach ($optionData['values'] as $valueIndex => $value) {
                $valueStr = is_string($value) ? $value : $value['value'] ?? $value;
                $valueModel = ProductVariantValue::create([
                    'variant_option_id' => $option->id,
                    'value' => $valueStr,
                    'position' => $valueIndex,
                ]);
                $valueMap[$optionData['name']][$valueStr] = $valueModel->id;
            }
        }

        // Create variant combinations - match by variant_name
        foreach ($variantsData as $variantData) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $variantData['sku'],
                'variant_name' => $variantData['variant_name'],
                'price' => $variantData['price'] ?? null,
                'compare_price' => $variantData['compare_price'] ?? null,
                'stock_count' => $variantData['stock_count'] ?? 0,
                'stock_status' => 'in_stock',
                'is_active' => $variantData['is_active'] ?? true,
            ]);
            $variant->updateStockStatus();

            // Find value IDs by matching variant_name (e.g., "Small / Red")
            $valueParts = array_map('trim', explode('/', $variantData['variant_name']));
            $valueIds = [];

            // Match each part to its option and value
            $optionNames = array_keys($valueMap);
            foreach ($valueParts as $index => $part) {
                if (isset($optionNames[$index]) && isset($valueMap[$optionNames[$index]][$part])) {
                    $valueIds[] = $valueMap[$optionNames[$index]][$part];
                }
            }

            // Attach values
            if (!empty($valueIds)) {
                $variant->variantValues()->attach($valueIds);
            }
        }
    }

    /**
     * Update variants for a product.
     */
    private function updateVariants(Product $product, array $optionsData, array $variantsData): void
    {
        // Delete existing variants and options
        $product->variantOptions()->delete();
        $product->variants()->delete();

        // Create new ones
        $this->createVariants($product, $optionsData, $variantsData);
    }
}

