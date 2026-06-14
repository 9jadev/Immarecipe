<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    TicketPercent,
    Settings,
    Users,
    TrendingUp,
    Clock,
    Check,
    X,
    Copy,
    Download,
} from 'lucide-vue-next';
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
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

const props = defineProps<{
    settings: {
        id: number;
        discounts: Array<{
            percentage: number;
            label: string;
            color: string;
            probability: number;
        }>;
        allow_no_discount: boolean;
        max_spins_per_email: number;
        code_expiry_hours: number;
        is_active: boolean;
    };
    submissions: Array<{
        id: number;
        name: string;
        phone: string | null;
        discount_won: number;
        discount_code: string;
        is_claimed: boolean;
        created_at: string;
        expires_at: string;
    }>;
    stats: {
        total_spins: number;
        total_discounts_given: number;
        claimed_count: number;
        conversion_rate: number;
    };
}>();

defineOptions({
    layout: AdminLayout,
});

const activeTab = ref('submissions');

const settingsForm = useForm({
    discounts: props.settings.discounts,
    allow_no_discount: props.settings.allow_no_discount,
    max_spins_per_email: props.settings.max_spins_per_email,
    code_expiry_hours: props.settings.code_expiry_hours,
    is_active: props.settings.is_active,
});

const copiedCode = ref<string | null>(null);

const saveSettings = () => {
    settingsForm.put('/admin/discount-spinner/settings');
};

const copyCode = (code: string) => {
    navigator.clipboard.writeText(code);
    copiedCode.value = code;
    setTimeout(() => copiedCode.value = null, 2000);
};

const getDiscountColor = (percentage: number) => {
    const discount = props.settings.discounts.find(d => d.percentage === percentage);
    return discount?.color || '#ccc';
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const isExpired = (expiresAt: string) => {
    return new Date(expiresAt) < new Date();
};

const exportData = () => {
    const headers = ['Name', 'Phone'];
    const rows = props.submissions.map(s => [
        s.name,
        s.phone || '-',
    ]);

    const csvContent = [
        headers.join(','),
        ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `spin-submissions-${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>

    <Head title="Discount Spinner" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                <TicketPercent class="h-8 w-8" />
                Discount Spinner
            </h1>
            <p class="text-muted-foreground">
                Manage your discount wheel settings and view customer submissions.
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Spins</CardTitle>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_spins }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Discounts Given</CardTitle>
                    <TicketPercent class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_discounts_given }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Claimed</CardTitle>
                    <Check class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.claimed_count }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Conversion Rate</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.conversion_rate }}%</div>
                </CardContent>
            </Card>
        </div>

        <!-- Custom Tabs -->
        <div class="w-full">
            <!-- Tab Headers -->
            <div class="flex border-b border-border mb-4">
                <button @click="activeTab = 'submissions'"
                    class="px-4 py-2 font-medium text-sm border-b-2 transition-colors"
                    :class="activeTab === 'submissions' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'">
                    Submissions
                </button>
                <button @click="activeTab = 'settings'"
                    class="px-4 py-2 font-medium text-sm border-b-2 transition-colors flex items-center gap-2"
                    :class="activeTab === 'settings' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'">
                    <Settings class="h-4 w-4" />
                    Settings
                </button>
            </div>

            <!-- Submissions Tab -->
            <div v-if="activeTab === 'submissions'">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Customer Submissions</CardTitle>
                            <CardDescription>
                                View all customer spins and their discount codes.
                            </CardDescription>
                        </div>
                        <Button variant="outline" size="sm" @click="exportData" class="flex items-center gap-2">
                            <Download class="h-4 w-4" />
                            Export CSV
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-border">
                                        <th class="text-left py-3 px-2 font-medium">Name</th>
                                        <th class="text-left py-3 px-2 font-medium">Phone</th>
                                        <th class="text-left py-3 px-2 font-medium">Discount</th>
                                        <th class="text-left py-3 px-2 font-medium">Code</th>
                                        <th class="text-left py-3 px-2 font-medium">Status</th>
                                        <th class="text-left py-3 px-2 font-medium">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="submission in submissions" :key="submission.id"
                                        class="border-b border-border/50">
                                        <td class="py-3 px-2 font-medium">{{ submission.name }}</td>
                                        <td class="py-3 px-2">{{ submission.phone || '-' }}</td>
                                        <td class="py-3 px-2">
                                            <span class="px-2 py-1 rounded text-xs font-medium" :style="{
                                                backgroundColor: getDiscountColor(submission.discount_won),
                                                color: submission.discount_won === 0 || submission.discount_won === 100 ? 'white' : 'black'
                                            }">
                                                {{ submission.discount_won === 0 ? 'Try Again' : submission.discount_won
                                                    + '% OFF' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-2">
                                            <div class="flex items-center gap-2">
                                                <code class="bg-muted px-2 py-1 rounded text-xs">
                                                    {{ submission.discount_code }}
                                                </code>
                                                <Button variant="ghost" size="sm"
                                                    @click="copyCode(submission.discount_code)">
                                                    <Check v-if="copiedCode === submission.discount_code"
                                                        class="h-3 w-3 text-green-500" />
                                                    <Copy v-else class="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </td>
                                        <td class="py-3 px-2">
                                            <div class="flex items-center gap-2">
                                                <span v-if="submission.is_claimed"
                                                    class="flex items-center gap-1 text-green-600 text-xs">
                                                    <Check class="h-3 w-3" />
                                                    Claimed
                                                </span>
                                                <span v-else-if="isExpired(submission.expires_at)"
                                                    class="flex items-center gap-1 text-red-600 text-xs">
                                                    <X class="h-3 w-3" />
                                                    Expired
                                                </span>
                                                <span v-else class="flex items-center gap-1 text-yellow-600 text-xs">
                                                    <Clock class="h-3 w-3" />
                                                    Pending
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-2 text-xs text-muted-foreground">
                                            {{ formatDate(submission.created_at) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Settings Tab -->
            <div v-if="activeTab === 'settings'">
                <Card>
                    <CardHeader>
                        <CardTitle>Wheel Settings</CardTitle>
                        <CardDescription>
                            Configure the discount wheel probabilities and options.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Active Toggle -->
                        <div class="flex items-center justify-between">
                            <div>
                                <Label class="text-base">Active</Label>
                                <p class="text-sm text-muted-foreground">
                                    Enable or disable the spin wheel
                                </p>
                            </div>
                            <Checkbox :checked="settingsForm.is_active"
                                @update:checked="settingsForm.is_active = $event" />
                        </div>

                        <!-- Max Spins -->
                        <div class="grid gap-2">
                            <Label for="max-spins">Max Spins Per Email (30 days)</Label>
                            <Input id="max-spins" v-model="settingsForm.max_spins_per_email" type="number" min="1"
                                max="10" />
                        </div>

                        <!-- Code Expiry -->
                        <div class="grid gap-2">
                            <Label for="expiry">Code Expiry (hours)</Label>
                            <Input id="expiry" v-model="settingsForm.code_expiry_hours" type="number" min="1"
                                max="168" />
                        </div>

                        <!-- Discounts Table -->
                        <div>
                            <Label class="text-base mb-2 block">Discount Segments</Label>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-border">
                                            <th class="text-left py-3 px-2 font-medium">Label</th>
                                            <th class="text-left py-3 px-2 font-medium">Color</th>
                                            <th class="text-left py-3 px-2 font-medium">Probability (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(discount, index) in settingsForm.discounts" :key="index"
                                            class="border-b border-border/50">
                                            <td class="py-3 px-2">
                                                <Input v-model="discount.label" />
                                            </td>
                                            <td class="py-3 px-2">
                                                <div class="flex items-center gap-2">
                                                    <input v-model="discount.color" type="color"
                                                        class="w-8 h-8 rounded cursor-pointer" />
                                                    <span class="text-xs text-muted-foreground">{{ discount.color
                                                    }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-2">
                                                <Input v-model="discount.probability" type="number" min="0" max="100" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <Button @click="saveSettings" :disabled="settingsForm.processing">
                                Save Settings
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
