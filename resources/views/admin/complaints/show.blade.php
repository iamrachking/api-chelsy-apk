@extends('admin.layout')

@section('title', 'Détails de la Réclamation')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Réclamation #{{ $complaint->id }}</h1>
        <a href="{{ route('admin.complaints') }}" class="text-blue-600 hover:text-blue-800 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Retour
        </a>
    </div>

    <!-- Informations générales -->
    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-info-circle mr-2 text-blue-600"></i>
            Informations
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-gray-600 text-sm mb-1">Client</p>
                <p class="font-semibold text-lg">
                    {{ $complaint->user->firstname ?? 'N/A' }} {{ $complaint->user->lastname ?? '' }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                    <i class="fas fa-envelope mr-1"></i>{{ $complaint->user->email ?? 'N/A' }}
                </p>
                @if($complaint->user->phone)
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-phone mr-1"></i>{{ $complaint->user->phone }}
                    </p>
                @endif
            </div>
            <div>
                <p class="text-gray-600 text-sm mb-1">Statut</p>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                        'in_progress' => 'bg-blue-100 text-blue-800 border-blue-300',
                        'resolved' => 'bg-green-100 text-green-800 border-green-300',
                        'closed' => 'bg-gray-100 text-gray-800 border-gray-300',
                    ];
                    $statusLabels = [
                        'pending' => 'En attente',
                        'in_progress' => 'En cours',
                        'resolved' => 'Résolue',
                        'closed' => 'Fermée',
                    ];
                @endphp
                <span class="inline-block px-3 py-1 text-sm rounded font-medium border {{ $statusColors[$complaint->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statusLabels[$complaint->status] ?? $complaint->status }}
                </span>
            </div>
            <div>
                <p class="text-gray-600 text-sm mb-1">Commande associée</p>
                @if($complaint->order)
                    <a href="{{ route('admin.orders.show', $complaint->order->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                        Commande #{{ $complaint->order->id }}
                        <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                    </a>
                    <p class="text-sm text-gray-500 mt-1">
                        Total: {{ number_format($complaint->order->total ?? 0, 0, ',', ' ') }} FCFA
                    </p>
                @else
                    <span class="text-gray-400">Aucune commande associée</span>
                @endif
            </div>
            <div>
                <p class="text-gray-600 text-sm mb-1">Date de création</p>
                <p class="font-semibold">{{ $complaint->created_at->format('d/m/Y à H:i') }}</p>
                @if($complaint->resolved_at)
                    <p class="text-sm text-gray-500 mt-1">
                        Résolue le: {{ $complaint->resolved_at->format('d/m/Y à H:i') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Sujet et message -->
    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-comment-alt mr-2 text-blue-600"></i>
            Réclamation
        </h2>
        <div class="mb-4">
            <p class="text-gray-600 text-sm mb-1">Sujet</p>
            <p class="font-semibold text-lg">{{ $complaint->subject }}</p>
        </div>
        <div>
            <p class="text-gray-600 text-sm mb-2">Message</p>
            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                <p class="text-gray-800 whitespace-pre-wrap">{{ $complaint->message }}</p>
            </div>
        </div>
    </div>

    <!-- Réponse de l'admin -->
    @if($complaint->admin_response)
        <div class="bg-blue-50 p-6 rounded shadow mb-6 border-l-4 border-blue-500">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-reply mr-2 text-blue-600"></i>
                Réponse de l'administrateur
            </h2>
            <div class="bg-white p-4 rounded border border-blue-200">
                <p class="text-gray-800 whitespace-pre-wrap">{{ $complaint->admin_response }}</p>
            </div>
        </div>
    @endif

    <!-- Formulaire de mise à jour -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-edit mr-2 text-blue-600"></i>
            Mettre à jour le statut
        </h2>
        <form action="{{ route('admin.complaints.update-status', $complaint->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Statut <span class="text-red-500">*</span>
                </label>
                <select name="status" id="status" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="pending" {{ $complaint->status === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="in_progress" {{ $complaint->status === 'in_progress' ? 'selected' : '' }}>En cours</option>
                    <option value="resolved" {{ $complaint->status === 'resolved' ? 'selected' : '' }}>Résolue</option>
                    <option value="closed" {{ $complaint->status === 'closed' ? 'selected' : '' }}>Fermée</option>
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="admin_response" class="block text-sm font-medium text-gray-700 mb-2">
                    Réponse de l'administrateur
                </label>
                <textarea 
                    name="admin_response" 
                    id="admin_response" 
                    rows="5" 
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ajoutez une réponse ou un commentaire pour le client..."
                >{{ old('admin_response', $complaint->admin_response) }}</textarea>
                <p class="text-sm text-gray-500 mt-1">Maximum 2000 caractères</p>
                @error('admin_response')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors font-medium">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
                <a href="{{ route('admin.complaints') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors font-medium">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

