<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariantOption;
use App\Models\ProductVariantValue;
use App\Models\ProductVariant;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    private const IMAGE_DISK = 'cloudinary';

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
                $imageUrls[] = $this->uploadProductImage($image);
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
                $imageUrls[] = $this->uploadProductImage($image);
            }
        }
        $validated['images'] = $imageUrls;

        // Remove old images that are no longer in the list
        if ($product->images) {
            foreach ($product->images as $oldImage) {
                if (! in_array($oldImage, $imageUrls)) {
                    $this->deleteProductImage($oldImage);
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

    private function uploadProductImage(UploadedFile $image): string
    {
        $response = $this->cloudinary()->uploadApi()->upload(
            $image->getRealPath(),
            array_filter([
                'folder' => 'products',
                'public_id' => (string) Str::uuid(),
                'resource_type' => 'image',
                'type' => 'upload',
                'upload_preset' => config('services.cloudinary.upload_preset'),
            ], static fn ($value) => $value !== null && $value !== '')
        )->getArrayCopy();

        // #region debug-point A:cloudinary-upload-metadata
        (static function () use ($response): void { $p = base_path('.dbg/cloudinary-image-404.env'); $u = 'http://127.0.0.1:7777/event'; $s = 'cloudinary-image-404'; if (is_file($p)) { foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) { if (str_starts_with($line, 'DEBUG_SERVER_URL=')) { $u = substr($line, 17); } elseif (str_starts_with($line, 'DEBUG_SESSION_ID=')) { $s = substr($line, 17); } } } @file_get_contents($u, false, stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => json_encode(['sessionId' => $s, 'runId' => 'post-fix', 'hypothesisId' => 'A', 'location' => 'app/Http/Controllers/Admin/ProductController.php:399', 'msg' => '[DEBUG] Cloudinary upload response captured', 'data' => ['public_id' => $response['public_id'] ?? null, 'format' => $response['format'] ?? null, 'version' => $response['version'] ?? null, 'secure_url' => $response['secure_url'] ?? null], 'ts' => (int) round(microtime(true) * 1000)]), 'ignore_errors' => true, 'timeout' => 2]])); })();
        // #endregion

        $secureUrl = $response['secure_url'] ?? null;

        if (! is_string($secureUrl) || $secureUrl === '') {
            throw new \RuntimeException('Unable to retrieve Cloudinary file URL after upload.');
        }

        return $secureUrl;
    }

    private function cloudinary(): Cloudinary
    {
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    private function deleteProductImage(string $image): void
    {
        $cloudinaryPublicId = $this->extractCloudinaryPublicId($image);
        if ($cloudinaryPublicId !== null) {
            Storage::disk(self::IMAGE_DISK)->delete($cloudinaryPublicId);

            return;
        }

        $localPath = $this->extractLocalStoragePath($image);
        if ($localPath !== null) {
            Storage::disk('public')->delete($localPath);
        }
    }

    private function extractCloudinaryPublicId(string $image): ?string
    {
        $host = parse_url($image, PHP_URL_HOST);
        if (! is_string($host) || ! str_contains($host, 'res.cloudinary.com')) {
            return null;
        }

        $path = parse_url($image, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $uploadIndex = array_search('upload', $segments, true);
        if ($uploadIndex === false) {
            return null;
        }

        $publicIdSegments = array_slice($segments, $uploadIndex + 1);
        if ($publicIdSegments === []) {
            return null;
        }

        foreach ($publicIdSegments as $index => $segment) {
            if (preg_match('/^v\d+$/', $segment) === 1) {
                $publicIdSegments = array_slice($publicIdSegments, $index + 1);
                break;
            }
        }

        if ($publicIdSegments === []) {
            return null;
        }

        $lastSegment = array_pop($publicIdSegments);
        if ($lastSegment === null) {
            return null;
        }

        $filename = pathinfo($lastSegment, PATHINFO_FILENAME);
        $publicIdSegments[] = $filename !== '' ? $filename : $lastSegment;

        return implode('/', $publicIdSegments);
    }

    private function extractLocalStoragePath(string $image): ?string
    {
        $path = parse_url($image, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : $image;
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, '/storage/')) {
            return Str::after($path, '/storage/');
        }

        if (str_starts_with($path, 'storage/')) {
            return Str::after($path, 'storage/');
        }

        return null;
    }
}
