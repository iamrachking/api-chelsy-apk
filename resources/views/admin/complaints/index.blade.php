@extends('admin.layout')

@section('title', 'Réclamations')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Réclamations</h1>
</div>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total</p>
                <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
            </div>
            <i class="fas fa-exclamation-triangle text-3xl text-gray-400"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">En attente</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
            </div>
            <i class="fas fa-clock text-3xl text-yellow-400"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">En cours</p>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['in_progress'] }}</p>
            </div>
            <i class="fas fa-spinner text-3xl text-blue-400"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Résolues</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['resolved'] }}</p>
            </div>
            <i class="fas fa-check-circle text-3xl text-green-400"></i>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="bg-white rounded shadow p-4 mb-6">
    <form method="GET" action="{{ route('admin.complaints') }}" class="flex gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="">Tous les statuts</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Résolues</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Fermées</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <i class="fas fa-filter mr-2"></i>Filtrer
        </button>
        @if(request('status'))
            <a href="{{ route('admin.complaints') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                <i class="fas fa-times mr-2"></i>Réinitialiser
            </a>
        @endif
    </form>
</div>

<!-- Tableau des réclamations -->
<div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sujet</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commande</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($complaints as $complaint)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">#{{ $complaint->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $complaint->user->firstname ?? 'N/A' }} {{ $complaint->user->lastname ?? '' }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="max-w-xs truncate" title="{{ $complaint->subject }}">
                            {{ $complaint->subject }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($complaint->order)
                            <a href="{{ route('admin.orders.show', $complaint->order->id) }}" class="text-blue-600 hover:text-blue-800">
                                #{{ $complaint->order->id }}
                            </a>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'resolved' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800',
                            ];
                            $statusLabels = [
                                'pending' => 'En attente',
                                'in_progress' => 'En cours',
                                'resolved' => 'Résolue',
                                'closed' => 'Fermée',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs rounded font-medium {{ $statusColors[$complaint->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$complaint->status] ?? $complaint->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $complaint->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('admin.complaints.show', $complaint->id) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-eye mr-1"></i>Voir
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                        <p>Aucune réclamation trouvée</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-4 border-t">
        {{ $complaints->appends(request()->query())->links() }}
    </div>
</div>
@endsection

