@extends('admin.layout')

@php
use Illuminate\Support\Str;
@endphp

@section('title', 'Dashboard Admin')

@section('content')
<div class="space-y-6">
    <!-- Bouton Export Stats -->
    <div class="flex justify-end">
        <a href="{{ route('admin.dashboard.export-stats') }}" class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all shadow-lg">
            <i class="fas fa-file-export mr-2"></i>
            Exporter les statistiques (CSV)
        </a>
    </div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Commandes Aujourd'hui</p>
                    <p class="text-3xl font-bold mt-2">{{ $stats['orders']['today'] }}</p>
                    <p class="text-blue-100 text-sm mt-1">{{ $stats['orders']['this_month'] }} ce mois</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-shopping-cart text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Revenus Aujourd'hui</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($stats['revenue']['today'], 0, ',', ' ') }} FCFA</p>
                    <p class="text-green-100 text-sm mt-1">{{ number_format($stats['revenue']['this_month'], 0, ',', ' ') }} FCFA ce mois</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-money-bill-wave text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Utilisateurs</p>
                    <p class="text-3xl font-bold mt-2">{{ $stats['users']['total'] }}</p>
                    <p class="text-purple-100 text-sm mt-1">{{ $stats['users']['new_today'] }} nouveaux aujourd'hui</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-users text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Plats</p>
                    <p class="text-3xl font-bold mt-2">{{ $stats['dishes']['total'] }}</p>
                    <p class="text-orange-100 text-sm mt-1">{{ $stats['dishes']['available'] }} disponibles</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-hamburger text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes importantes -->
    @if($stats['alerts']['pending_orders'] > 0 || $stats['alerts']['pending_reviews'] > 0 || $stats['alerts']['pending_complaints'] > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @if($stats['alerts']['pending_orders'] > 0)
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform cursor-pointer" onclick="window.location.href='{{ route('admin.orders') }}'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Commandes en attente</p>
                    <p class="text-3xl font-bold mt-2">{{ $stats['alerts']['pending_orders'] }}</p>
                    <p class="text-yellow-100 text-xs mt-1">Nécessitent une action</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-exclamation-circle text-3xl"></i>
                </div>
            </div>
        </div>
        @endif

        @if($stats['alerts']['pending_reviews'] > 0)
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform cursor-pointer" onclick="window.location.href='{{ route('admin.reviews') }}'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Avis en attente</p>
                    <p class="text-3xl font-bold mt-2">{{ $stats['alerts']['pending_reviews'] }}</p>
                    <p class="text-orange-100 text-xs mt-1">En attente de modération</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-star text-3xl"></i>
                </div>
            </div>
        </div>
        @endif

        @if($stats['alerts']['pending_complaints'] > 0)
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform cursor-pointer" onclick="window.location.href='{{ route('admin.complaints') }}'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Réclamations en attente</p>
                    <p class="text-3xl font-bold mt-2">{{ $stats['alerts']['pending_complaints'] }}</p>
                    <p class="text-red-100 text-xs mt-1">Nécessitent une attention</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-exclamation-triangle text-3xl"></i>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Réclamations et Avis en attente -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if($pendingComplaints->count() > 0)
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        Réclamations en attente
                    </h2>
                    <a href="{{ route('admin.complaints') }}" class="text-white hover:text-red-200 text-sm font-semibold">
                        Voir tout <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Utilisateur</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sujet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pendingComplaints as $complaint)
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location.href='{{ route('admin.complaints.show', $complaint->id) }}'">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">#{{ $complaint->id }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $complaint->user->firstname }} {{ $complaint->user->lastname }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">{{ Str::limit($complaint->subject, 40) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $complaint->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($pendingReviews->count() > 0)
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-star mr-3"></i>
                        Avis en attente de modération
                    </h2>
                    <a href="{{ route('admin.reviews') }}" class="text-white hover:text-orange-200 text-sm font-semibold">
                        Voir tout <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Plat</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Note</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pendingReviews as $review)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $review->user->firstname ?? 'N/A' }} {{ $review->user->lastname ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $review->dish->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-sm font-semibold text-gray-900">{{ $review->rating }}/5</span>
                                        <div class="ml-2 flex text-yellow-400">
                                            @for($i = 0; $i < 5; $i++)
                                                <i class="fas fa-star {{ $i < $review->rating ? '' : 'text-gray-300' }} text-xs"></i>
                                            @endfor
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $review->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-clock mr-3"></i>
                Commandes Récentes
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                                    #{{ $order->id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $order->user->firstname ?? 'N/A' }} {{ $order->user->lastname ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-blue-100 text-blue-800',
                                        'preparing' => 'bg-purple-100 text-purple-800',
                                        'ready' => 'bg-indigo-100 text-indigo-800',
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $color = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="far fa-calendar mr-2"></i>{{ $order->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 block"></i>
                                Aucune commande récente
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
