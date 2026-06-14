<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Package, ArrowLeft, Save, Upload, X, ImageIcon, Loader2, AlertCircle, Plus, Trash2 } from 'lucide-vue-next';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, computed, watch } from 'vue';

interface Category {
    id: number;
    name: string;
}

interface VariantValue {
    id: number | null;
    value: string;
}

interface VariantOption {
    id: number | null;
    name: string;
    values: VariantValue[];
}

interface VariantItem {
    id: number | null;
    sku: string;
    variant_name: string;
    price: string;
    compare_price: string;
    stock_count: number;
    is_active: boolean;
    value_ids: number[];
}

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string;
    description: string | null;
    short_description: string | null;
    category_id: number | null;
    price: number;
    compare_price: number | null;
    stock_count: number;
    stock_status: string;
    is_active: boolean;
    is_featured: boolean;
    weight: number | null;
    weight_unit: string;
    images: string[] | null;
    meta_title: string | null;
    meta_description: string | null;
    variantOptions?: VariantOption[];
    variants?: VariantItem[];
}

const props = defineProps<{
    product?: Product;
    categories: Category[];
}>();

defineOptions({
    layout: AdminLayout,
});

const isEditing = !!props.product;

// Destructure categories for template access
const { categories } = props;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
    {
        title: 'Products',
        href: '/admin/products',
    },
    {
        title: isEditing ? 'Edit Product' : 'Create Product',
        href: isEditing ? `/admin/products/${props.product?.id}/edit` : '/admin/products/create',
    },
];

// Track existing images (for editing)
const existingImages = ref<string[]>(props.product?.images ?? []);

// Track new files to upload
const newImageFiles = ref<File[]>([]);

// Preview URLs for new files
const newImagePreviews = ref<string[]>([]);

// Validation error message
const imageError = ref<string>('');

// Loading state
const isSubmitting = ref(false);

// Server validation errors
const serverErrors = ref<Record<string, string>>({});

// Max file size (5MB)
const MAX_FILE_SIZE = 5 * 1024 * 1024;

// Variant state
const hasVariants = ref((props.product?.variantOptions?.length ?? 0) > 0);
const variantOptions = ref<VariantOption[]>(props.product?.variantOptions || []);
const variants = ref<VariantItem[]>(props.product?.variants || []);

// Computed for template reactivity
const showVariantSection = computed(() => hasVariants.value);

// Get all error messages for display
const allErrors = computed(() => {
    const errors: string[] = [];

    // Form errors from Inertia
    Object.entries(form.errors).forEach(([field, message]) => {
        errors.push(`${field}: ${message}`);
    });

    // Server errors
    Object.entries(serverErrors.value).forEach(([field, message]) => {
        const formErrors = form.errors as Record<string, string>;
        if (!formErrors[field]) {
            errors.push(`${field}: ${message}`);
        }
    });

    return errors;
});

// Check if there are any errors
const hasErrors = computed(() => Object.keys(form.errors).length > 0 || Object.keys(serverErrors.value).length > 0);

// Clear all errors
const clearAllErrors = () => {
    form.clearErrors();
    serverErrors.value = {};
};

// Get error for a specific field (checks both form.errors and serverErrors)
const getError = (field: string): string | undefined => {
    const formErrors = form.errors as Record<string, string>;
    return formErrors[field] || serverErrors.value[field];
};

// Get error for a variant field (e.g., variants.0.sku)
const getVariantError = (index: number, field: string): string | undefined => {
    const key = `variants.${index}.${field}`;
    return getError(key);
};

// Get error for a variant option field (e.g., variant_options.0.name)
const getVariantOptionError = (index: number, field: string): string | undefined => {
    const key = `variant_options.${index}.${field}`;
    return getError(key);
};

// Get error for a variant option value field (e.g., variant_options.0.values.0.value)
const getVariantValueError = (optIndex: number, valIndex: number): string | undefined => {
    const key = `variant_options.${optIndex}.values.${valIndex}.value`;
    return getError(key);
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    imageError.value = '';

    if (target.files) {
        const files = Array.from(target.files);
        const validFiles: File[] = [];
        const invalidFiles: string[] = [];

        // Validate each file
        files.forEach(file => {
            if (file.size > MAX_FILE_SIZE) {
                invalidFiles.push(`${file.name} (${(file.size / 1024 / 1024).toFixed(2)}MB - exceeds 5MB limit)`);
            } else if (!file.type.startsWith('image/')) {
                invalidFiles.push(`${file.name} is not a valid image file`);
            } else {
                validFiles.push(file);
            }
        });

        // Show error for invalid files
        if (invalidFiles.length > 0) {
            imageError.value = `Some files were rejected: ${invalidFiles.join(', ')}`;
        }

        // Add valid files to upload list
        if (validFiles.length > 0) {
            newImageFiles.value.push(...validFiles);

            // Create preview URLs for valid files only
            validFiles.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (e.target?.result) {
                        newImagePreviews.value.push(e.target.result as string);
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        // Reset input
        target.value = '';
    }
};

const removeExistingImage = (index: number) => {
    existingImages.value.splice(index, 1);
};

const removeNewImage = (index: number) => {
    newImageFiles.value.splice(index, 1);
    newImagePreviews.value.splice(index, 1);
};

const form = useForm({
    name: props.product?.name ?? '',
    slug: props.product?.slug ?? '',
    sku: props.product?.sku ?? '',
    description: props.product?.description ?? '',
    short_description: props.product?.short_description ?? '',
    category_id: props.product?.category_id?.toString() ?? '',
    price: props.product?.price ?? '',
    compare_price: props.product?.compare_price ?? '',
    stock_count: props.product?.stock_count ?? 0,
    stock_status: props.product?.stock_status ?? 'in_stock',
    is_active: props.product?.is_active ?? true,
    is_featured: props.product?.is_featured ?? false,
    weight: props.product?.weight ?? '',
    weight_unit: props.product?.weight_unit ?? 'kg',
    meta_title: props.product?.meta_title ?? '',
    meta_description: props.product?.meta_description ?? '',
});

// Variant management functions
const addVariantOption = () => {
    variantOptions.value.push({
        id: null,
        name: '',
        values: [{ id: null, value: '' }],
    });
};

const removeVariantOption = (index: number) => {
    variantOptions.value.splice(index, 1);
    generateVariants();
};

const addVariantValue = (optionIndex: number) => {
    variantOptions.value[optionIndex].values.push({ id: null, value: '' });
};

const removeVariantValue = (optionIndex: number, valueIndex: number) => {
    const option = variantOptions.value[optionIndex];
    if (option.values.length > 1) {
        option.values.splice(valueIndex, 1);
        generateVariants();
    }
};

// Generate all possible variant combinations
const generateVariants = () => {
    if (!hasVariants.value || variantOptions.value.length === 0) {
        variants.value = [];
        return;
    }

    // Check if all options have valid values
    const validOptions = variantOptions.value.filter(
        opt => opt.name.trim() && opt.values.some(v => v.value.trim())
    );

    if (validOptions.length === 0) {
        variants.value = [];
        return;
    }

    // Get arrays of values for each option
    const valueArrays = validOptions.map(opt =>
        opt.values.filter(v => v.value.trim()).map(v => ({
            optionName: opt.name,
            value: v.value.trim(),
            id: v.id,
        }))
    );

    // Generate cartesian product
    const cartesian = <T>(...arrays: T[][]): T[][] => {
        return arrays.reduce<T[][]>(
            (acc, arr) => acc.flatMap(x => arr.map(y => [...x, y])),
            [[]] as T[][]
        );
    };

    const combinations = cartesian(...valueArrays);

    // Create variant objects
    const newVariants: VariantItem[] = combinations.map((combo, index) => {
        const variantName = combo.map(c => c.value).join(' / ');
        const existingVariant = variants.value.find(v => v.variant_name === variantName);

        return {
            id: existingVariant?.id || null,
            sku: existingVariant?.sku || `${form.sku || 'VAR'}-${index + 1}`,
            variant_name: variantName,
            price: existingVariant?.price ?? '',
            compare_price: existingVariant?.compare_price ?? '',
            stock_count: existingVariant?.stock_count ?? 0,
            is_active: existingVariant?.is_active ?? true,
            value_ids: combo.map(c => c.id).filter((id): id is number => id !== null),
        };
    });

    variants.value = newVariants;
};

// Watch for changes in variant options to regenerate combinations
watch([() => hasVariants.value, variantOptions], () => {
    generateVariants();
}, { deep: true });

// Initialize variant options when hasVariants is toggled on
watch(() => hasVariants.value, (newValue) => {
    if (newValue && variantOptions.value.length === 0) {
        addVariantOption();
    }
});

// Frontend validation for variants
const validateVariants = (): boolean => {
    const errors: Record<string, string> = {};

    if (hasVariants.value) {
        // Check variant options
        variantOptions.value.forEach((option, optIndex) => {
            // Check option name
            if (!option.name.trim()) {
                errors[`variant_options.${optIndex}.name`] = 'Option name is required';
            }

            // Check values
            option.values.forEach((val, valIndex) => {
                if (!val.value.trim()) {
                    errors[`variant_options.${optIndex}.values.${valIndex}.value`] = 'Value is required';
                }
            });
        });

        // Check variants have required fields
        variants.value.forEach((variant, varIndex) => {
            if (!variant.sku.trim()) {
                errors[`variants.${varIndex}.sku`] = 'SKU is required';
            }
            if (variant.stock_count < 0) {
                errors[`variants.${varIndex}.stock_count`] = 'Stock count must be 0 or greater';
            }
        });
    }

    if (Object.keys(errors).length > 0) {
        serverErrors.value = errors;
        Object.entries(errors).forEach(([field, message]) => {
            // @ts-expect-error - Dynamic field assignment
            form.setError(field, message);
        });
        return false;
    }

    return true;
};

const submit = () => {
    // Clear previous errors
    clearAllErrors();

    // Validate variants on frontend first
    if (hasVariants.value && !validateVariants()) {
        isSubmitting.value = false;
        toast.error('Please fix the validation errors');
        return;
    }

    isSubmitting.value = true;
    const formData = new FormData();

    // Add all form fields
    formData.append('name', form.name);
    formData.append('slug', form.slug);
    formData.append('sku', form.sku);
    formData.append('description', form.description);
    formData.append('short_description', form.short_description);
    formData.append('category_id', form.category_id ? form.category_id : '');
    formData.append('price', String(form.price));
    formData.append('compare_price', form.compare_price ? String(form.compare_price) : '');
    formData.append('stock_count', String(form.stock_count));
    formData.append('stock_status', form.stock_status);
    formData.append('is_active', String(form.is_active ? 1 : 0));
    formData.append('is_featured', String(form.is_featured ? 1 : 0));
    formData.append('weight', form.weight ? String(form.weight) : '');
    formData.append('weight_unit', form.weight_unit);
    formData.append('meta_title', form.meta_title);
    formData.append('meta_description', form.meta_description);

    // Add variant fields
    formData.append('has_variants', String(hasVariants.value ? 1 : 0));

    if (hasVariants.value) {
        // Add variant options
        variantOptions.value.forEach((option, index) => {
            formData.append(`variant_options[${index}][name]`, option.name);
            option.values.forEach((value, vIndex) => {
                // Send as nested object format for backend validation
                formData.append(`variant_options[${index}][values][${vIndex}][id]`, value.id?.toString() || '');
                formData.append(`variant_options[${index}][values][${vIndex}][value]`, value.value);
            });
        });

        // Add variants
        variants.value.forEach((variant, index) => {
            formData.append(`variants[${index}][sku]`, variant.sku);
            formData.append(`variants[${index}][variant_name]`, variant.variant_name);
            formData.append(`variants[${index}][price]`, variant.price || '');
            formData.append(`variants[${index}][compare_price]`, variant.compare_price || '');
            formData.append(`variants[${index}][stock_count]`, String(variant.stock_count));
            formData.append(`variants[${index}][is_active]`, String(variant.is_active ? 1 : 0));
            variant.value_ids.forEach((id, vIndex) => {
                formData.append(`variants[${index}][value_ids][${vIndex}]`, String(id));
            });
        });
    }

    // Add existing images
    existingImages.value.forEach((url, index) => {
        formData.append(`existing_images[${index}]`, url);
    });

    // Add new image files
    newImageFiles.value.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
    });

    if (isEditing) {
        formData.append('_method', 'PUT');
        router.post(`/admin/products/${props.product!.id}`, formData, {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Product updated successfully');
                newImageFiles.value = [];
                newImagePreviews.value = [];
                serverErrors.value = {};
            },
            onError: (errors) => {
                toast.error('Failed to update product. Please check the form for errors.');
                serverErrors.value = errors as Record<string, string>;
                // Also set form errors so they show inline
                Object.entries(errors).forEach(([field, message]) => {
                    // @ts-expect-error - Dynamic field assignment
                    form.setError(field, message as string);
                });
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } else {
        router.post('/admin/products', formData, {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Product created successfully');
                newImageFiles.value = [];
                newImagePreviews.value = [];
                serverErrors.value = {};
            },
            onError: (errors) => {
                toast.error('Failed to create product. Please check the form for errors.');
                serverErrors.value = errors as Record<string, string>;
                // Also set form errors so they show inline
                Object.entries(errors).forEach(([field, message]) => {
                    // @ts-expect-error - Dynamic field assignment
                    form.setError(field, message as string);
                });
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    }
};
</script>

<template>

    <Head :title="isEditing ? 'Edit Product' : 'Create Product'" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/admin/products">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back
                </Link>
            </Button>
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <Package class="h-8 w-8" />
                    {{ isEditing ? 'Edit Product' : 'Create Product' }}
                </h1>
                <p class="text-muted-foreground">
                    {{ isEditing ? 'Update the product details.' : 'Add a new product to your inventory.' }}
                </p>
            </div>
        </div>

        <!-- Validation Error Summary -->
        <div v-if="hasErrors" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <AlertCircle class="h-5 w-5 text-destructive flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                    <p class="font-medium text-destructive">Please fix the following errors:</p>
                    <ul class="mt-2 text-sm text-destructive/80 space-y-1">
                        <li v-for="(error, index) in allErrors" :key="index" class="flex items-start gap-2">
                            <span class="text-destructive">•</span>
                            <span>{{ error }}</span>
                        </li>
                    </ul>
                </div>
                <button type="button" @click="clearAllErrors" class="text-destructive hover:text-destructive/80">
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                        <CardDescription>
                            Enter the basic details of your product.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="name">Product Name *</Label>
                            <Input id="name" v-model="form.name" type="text" placeholder="Enter product name"
                                :class="{ 'border-destructive': form.errors.name }" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Slug (optional)</Label>
                            <Input id="slug" v-model="form.slug" type="text" placeholder="product-slug"
                                :class="{ 'border-destructive': form.errors.slug }" />
                            <p class="text-xs text-muted-foreground">Leave empty to auto-generate from the name.</p>
                            <p v-if="form.errors.slug" class="text-sm text-destructive">{{ form.errors.slug }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="sku">SKU (optional)</Label>
                            <Input id="sku" v-model="form.sku" type="text" placeholder="ABC123"
                                :class="{ 'border-destructive': form.errors.sku }" />
                            <p class="text-xs text-muted-foreground">Leave empty to auto-generate.</p>
                            <p v-if="form.errors.sku" class="text-sm text-destructive">{{ form.errors.sku }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="short_description">Short Description</Label>
                            <Input id="short_description" v-model="form.short_description" type="text"
                                placeholder="Brief description for listings" maxlength="500"
                                :class="{ 'border-destructive': form.errors.short_description }" />
                            <p v-if="form.errors.short_description" class="text-sm text-destructive">{{
                                form.errors.short_description }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <textarea id="description" v-model="form.description" rows="5"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Full product description"></textarea>
                            <p v-if="form.errors.description" class="text-sm text-destructive">{{
                                form.errors.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Images -->
                <Card>
                    <CardHeader>
                        <CardTitle>Product Images</CardTitle>
                        <CardDescription>
                            Upload product images (JPEG, PNG, GIF, WebP - Max 5MB each).
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Error Message -->
                        <div v-if="imageError"
                            class="flex items-start gap-2 p-3 bg-destructive/10 border border-destructive/20 rounded-lg text-destructive">
                            <AlertCircle class="h-5 w-5 flex-shrink-0 mt-0.5" />
                            <div class="text-sm">
                                <p class="font-medium">Upload Error</p>
                                <p class="text-xs mt-1">{{ imageError }}</p>
                            </div>
                        </div>

                        <!-- Image Upload Area -->
                        <div
                            class="border-2 border-dashed border-muted-foreground/25 rounded-lg p-6 text-center hover:border-primary/50 transition-colors">
                            <input type="file" id="image-upload" multiple
                                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="hidden"
                                @change="handleFileSelect" />
                            <label for="image-upload" class="cursor-pointer">
                                <Upload class="h-10 w-10 mx-auto text-muted-foreground mb-2" />
                                <p class="text-sm text-muted-foreground">
                                    <span class="text-primary font-medium">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    PNG, JPG, GIF, WebP up to 5MB
                                </p>
                            </label>
                        </div>

                        <!-- Existing Images -->
                        <div v-if="existingImages.length > 0" class="space-y-2">
                            <Label class="text-sm font-medium">Current Images</Label>
                            <div class="grid grid-cols-3 gap-3">
                                <div v-for="(image, index) in existingImages" :key="index"
                                    class="relative group aspect-square rounded-lg overflow-hidden border bg-muted">
                                    <img :src="image" :alt="`Product image ${index + 1}`"
                                        class="w-full h-full object-cover" />
                                    <button type="button" @click="removeExistingImage(index)"
                                        class="absolute top-1 right-1 p-1 bg-destructive text-destructive-foreground rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- New Image Previews -->
                        <div v-if="newImagePreviews.length > 0" class="space-y-2">
                            <Label class="text-sm font-medium">New Images (pending upload)</Label>
                            <div class="grid grid-cols-3 gap-3">
                                <div v-for="(preview, index) in newImagePreviews" :key="index"
                                    class="relative group aspect-square rounded-lg overflow-hidden border bg-muted">
                                    <img :src="preview" :alt="`New image ${index + 1}`"
                                        class="w-full h-full object-cover" />
                                    <button type="button" @click="removeNewImage(index)"
                                        class="absolute top-1 right-1 p-1 bg-destructive text-destructive-foreground rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- No Images Placeholder -->
                        <div v-if="existingImages.length === 0 && newImagePreviews.length === 0"
                            class="flex flex-col items-center justify-center py-4 text-muted-foreground">
                            <ImageIcon class="h-12 w-12 mb-2 opacity-50" />
                            <p class="text-sm">No images uploaded</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pricing -->
                <Card>
                    <CardHeader>
                        <CardTitle>Pricing</CardTitle>
                        <CardDescription>
                            Set the price and compare price for discounts.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="price">Price *</Label>
                                <Input id="price" v-model="form.price" type="number" step="0.01" min="0"
                                    placeholder="0.00" :class="{ 'border-destructive': form.errors.price }" />
                                <p v-if="form.errors.price" class="text-sm text-destructive">{{ form.errors.price }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="compare_price">Compare Price</Label>
                                <Input id="compare_price" v-model="form.compare_price" type="number" step="0.01" min="0"
                                    placeholder="0.00" :class="{ 'border-destructive': form.errors.compare_price }" />
                                <p class="text-xs text-muted-foreground">Original price for showing discount.</p>
                                <p v-if="form.errors.compare_price" class="text-sm text-destructive">{{
                                    form.errors.compare_price }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Inventory -->
                <Card>
                    <CardHeader>
                        <CardTitle>Inventory</CardTitle>
                        <CardDescription>
                            Manage stock levels and status.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="stock_count">Stock Count *</Label>
                                <Input id="stock_count" v-model="form.stock_count" type="number" min="0"
                                    :class="{ 'border-destructive': form.errors.stock_count }" />
                                <p v-if="form.errors.stock_count" class="text-sm text-destructive">{{
                                    form.errors.stock_count }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="stock_status">Stock Status *</Label>
                                <Select v-model="form.stock_status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="in_stock">In Stock</SelectItem>
                                        <SelectItem value="low_stock">Low Stock</SelectItem>
                                        <SelectItem value="out_of_stock">Out of Stock</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.stock_status" class="text-sm text-destructive">{{
                                    form.errors.stock_status }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Variants -->
                <Card>
                    <CardHeader>
                        <CardTitle>Variants</CardTitle>
                        <CardDescription>
                            Add product variants like Size, Color, etc.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Has Variants Toggle -->
                        <div class="flex items-center space-x-2">
                            <Checkbox id="has_variants" v-model="hasVariants" />
                            <Label for="has_variants" class="cursor-pointer">
                                This product has variants (Size, Color, etc.)
                            </Label>
                            <span class="text-xs text-muted-foreground ml-2">({{ hasVariants ? 'Yes' : 'No' }})</span>
                        </div>

                        <!-- Variant Options -->
                        <div v-if="showVariantSection" class="space-y-4 mt-4">
                            <div class="flex items-center justify-between">
                                <Label class="text-sm font-medium">Variant Options</Label>
                                <Button type="button" variant="outline" size="sm" @click="addVariantOption">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Add Option
                                </Button>
                            </div>

                            <!-- Variant Option Items -->
                            <div v-for="(option, optIndex) in variantOptions" :key="optIndex"
                                class="border rounded-lg p-4 space-y-3">
                                <div class="flex items-center gap-2">
                                    <Input v-model="option.name" placeholder="Option name (e.g., Size)" class="flex-1"
                                        :class="{ 'border-destructive': getVariantOptionError(optIndex, 'name') }" />
                                    <Button type="button" variant="ghost" size="icon"
                                        @click="removeVariantOption(optIndex)" :disabled="variantOptions.length <= 1">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                                <p v-if="getVariantOptionError(optIndex, 'name')" class="text-sm text-destructive">
                                    {{ getVariantOptionError(optIndex, 'name') }}
                                </p>

                                <!-- Values -->
                                <div class="space-y-2">
                                    <Label class="text-xs text-muted-foreground">Values</Label>
                                    <div class="flex flex-wrap gap-2">
                                        <div v-for="(val, valIndex) in option.values" :key="valIndex"
                                            class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1">
                                                <Input v-model="val.value" placeholder="Value" class="w-28 h-8 text-sm"
                                                    :class="{ 'border-destructive': getVariantValueError(optIndex, valIndex) }"
                                                    @blur="generateVariants" />
                                                <Button type="button" variant="ghost" size="icon-sm"
                                                    @click="removeVariantValue(optIndex, valIndex)"
                                                    :disabled="option.values.length <= 1">
                                                    <X class="h-3 w-3" />
                                                </Button>
                                            </div>
                                            <p v-if="getVariantValueError(optIndex, valIndex)" class="text-xs text-destructive">
                                                {{ getVariantValueError(optIndex, valIndex) }}
                                            </p>
                                        </div>
                                        <Button type="button" variant="outline" size="sm"
                                            @click="addVariantValue(optIndex)" class="h-8">
                                            <Plus class="h-3 w-3 mr-1" />
                                            Add
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Generated Variants Table -->
                            <div v-if="variants.length > 0" class="mt-6">
                                <div class="flex items-center justify-between mb-2">
                                    <Label class="text-sm font-medium">
                                        Generated Variants ({{ variants.length }})
                                    </Label>
                                    <p class="text-xs text-muted-foreground">
                                        Base Price: ₦{{ Number(form.price || 0).toLocaleString() }}
                                    </p>
                                </div>
                                <div class="border rounded-lg overflow-x-auto">
                                    <table class="w-full text-sm min-w-[600px]">
                                        <thead class="bg-muted">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-medium">Variant</th>
                                                <th class="px-3 py-2 text-left font-medium">SKU</th>
                                                <th class="px-3 py-2 text-left font-medium">Price (₦)</th>
                                                <th class="px-3 py-2 text-left font-medium">Compare Price (₦)</th>
                                                <th class="px-3 py-2 text-left font-medium">Stock</th>
                                                <th class="px-3 py-2 text-center font-medium">Active</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <tr v-for="(variant, varIndex) in variants" :key="varIndex"
                                                :class="{ 'bg-destructive/5': getVariantError(varIndex, 'sku') || getVariantError(varIndex, 'stock_count') }"
                                                class="hover:bg-muted/50">
                                                <td class="px-3 py-2 font-medium">{{ variant.variant_name }}</td>
                                                <td class="px-3 py-2">
                                                    <Input v-model="variant.sku" class="h-8 w-28"
                                                        :class="{ 'border-destructive': getVariantError(varIndex, 'sku') }" />
                                                    <p v-if="getVariantError(varIndex, 'sku')"
                                                        class="text-xs text-destructive mt-1">
                                                        {{ getVariantError(varIndex, 'sku') }}
                                                    </p>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <Input v-model="variant.price" type="number" step="0.01" min="0"
                                                        :placeholder="String(form.price || '0')" class="h-8 w-24"
                                                        :class="{ 'border-destructive': getVariantError(varIndex, 'price') }" />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <Input v-model="variant.compare_price" type="number" step="0.01"
                                                        min="0" :placeholder="String(form.compare_price || '0')"
                                                        class="h-8 w-24"
                                                        :class="{ 'border-destructive': getVariantError(varIndex, 'compare_price') }" />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <Input v-model.number="variant.stock_count" type="number" min="0"
                                                        class="h-8 w-20"
                                                        :class="{ 'border-destructive': getVariantError(varIndex, 'stock_count') }" />
                                                    <p v-if="getVariantError(varIndex, 'stock_count')"
                                                        class="text-xs text-destructive mt-1">
                                                        {{ getVariantError(varIndex, 'stock_count') }}
                                                    </p>
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <Checkbox v-model="variant.is_active" />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-xs text-muted-foreground mt-2">
                                    Leave price fields empty to use the base product price.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Shipping -->
                <Card>
                    <CardHeader>
                        <CardTitle>Shipping</CardTitle>
                        <CardDescription>
                            Product weight for shipping calculations.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="weight">Weight</Label>
                                <Input id="weight" v-model="form.weight" type="number" step="0.01" min="0"
                                    placeholder="0.00" :class="{ 'border-destructive': form.errors.weight }" />
                                <p v-if="form.errors.weight" class="text-sm text-destructive">{{ form.errors.weight }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="weight_unit">Weight Unit</Label>
                                <Select v-model="form.weight_unit">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select unit" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="kg">Kilograms (kg)</SelectItem>
                                        <SelectItem value="g">Grams (g)</SelectItem>
                                        <SelectItem value="lb">Pounds (lb)</SelectItem>
                                        <SelectItem value="oz">Ounces (oz)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- SEO -->
                <Card>
                    <CardHeader>
                        <CardTitle>SEO</CardTitle>
                        <CardDescription>
                            Optimize for search engines.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="meta_title">Meta Title</Label>
                            <Input id="meta_title" v-model="form.meta_title" type="text" placeholder="SEO title"
                                maxlength="255" :class="{ 'border-destructive': form.errors.meta_title }" />
                            <p v-if="form.errors.meta_title" class="text-sm text-destructive">{{ form.errors.meta_title
                            }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="meta_description">Meta Description</Label>
                            <textarea id="meta_description" v-model="form.meta_description" rows="3"
                                class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="SEO description" maxlength="500"></textarea>
                            <p v-if="form.errors.meta_description" class="text-sm text-destructive">{{
                                form.errors.meta_description }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Organization -->
                <Card>
                    <CardHeader>
                        <CardTitle>Organization</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="category_id">Category</Label>
                            <Select v-model="form.category_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">None</SelectItem>
                                    <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                                        {{ cat.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.category_id" class="text-sm text-destructive">{{
                                form.errors.category_id }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Status -->
                <Card>
                    <CardHeader>
                        <CardTitle>Status</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <Checkbox id="is_active" :checked="form.is_active"
                                @update:checked="form.is_active = $event" />
                            <Label for="is_active" class="cursor-pointer">
                                Active
                            </Label>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Checkbox id="is_featured" :checked="form.is_featured"
                                @update:checked="form.is_featured = $event" />
                            <Label for="is_featured" class="cursor-pointer">
                                Featured
                            </Label>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="space-y-2">
                            <Button type="submit" class="w-full" :disabled="isSubmitting">
                                <Loader2 v-if="isSubmitting" class="h-4 w-4 mr-2 animate-spin" />
                                <Save v-else class="h-4 w-4 mr-2" />
                                <span v-if="isSubmitting">{{ isEditing ? 'Updating...' : 'Creating...' }}</span>
                                <span v-else>{{ isEditing ? 'Update Product' : 'Create Product' }}</span>
                            </Button>
                            <Link href="/admin/products" class="block">
                                <Button type="button" variant="outline" class="w-full" :disabled="isSubmitting">
                                    Cancel
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </form>
    </div>
</template>
