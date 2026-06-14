<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StorefrontController extends Controller
{
    /**
     * Display the homepage with products and categories.
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->active()
            ->with(['category', 'variants' => fn($q) => $q->where('is_active', true)])
            ->when($request->category, function ($q, $slug) {
                $q->whereHas('category', fn($q) => $q->where('slug', $slug));
            })
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });

        $products = $query->latest()->paginate(12)->withQueryString();

        // Transform products to include has_variants flag
        $products->getCollection()->transform(function ($product) {
            $product->has_variants = $product->variants->isNotEmpty();
            return $product;
        });
        $categories = Category::active()->ordered()->get();

        return Inertia::render('Storefront/Home', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    /**
     * Display products by category.
     */
    public function category(Category $category)
    {
        $products = Product::active()
            ->with('category')
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(12);

        $categories = Category::active()->ordered()->get();

        return Inertia::render('Storefront/Home', [
            'products' => $products,
            'categories' => $categories,
            'currentCategory' => $category,
            'filters' => ['category' => $category->slug],
        ]);
    }

    /**
     * Display a single product.
     */
    public function product(Product $product)
    {
        if (!$product->is_active) {
            abort(404);
        }

        $product->load('category', 'variantOptions.values', 'variants.variantValues');

        $relatedProducts = Product::active()
            ->with('category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return Inertia::render('Storefront/Product', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
