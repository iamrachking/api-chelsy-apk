@extends('admin.layout')

@section('title', 'Détails Utilisateur')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ $user->firstname }} {{ $user->lastname }}</h1>
        <a href="{{ route('admin.users') }}" class="text-blue-600 hover:text-blue-800">← Retour</a>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-xl font-bold mb-4">Informations</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-gray-600">Email</p>
                <p class="font-semibold">{{ $user->email }}</p>
            </div>
            <div>
                <p class="text-gray-600">Téléphone</p>
                <p class="font-semibold">{{ $user->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-600">Admin</p>
                <p class="font-semibold">{{ $user->is_admin ? 'Oui' : 'Non' }}</p>
            </div>
            <div>
                <p class="text-gray-600">Date d'inscription</p>
                <p class="font-semibold">{{ $user->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-xl font-bold mb-4">Commandes ({{ $user->orders->count() }})</h2>
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Total</th>
                    <th class="px-4 py-2 text-left">Statut</th>
                    <th class="px-4 py-2 text-left">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($user->orders as $order)
                    <tr class="border-b">
                        <td class="px-4 py-2">#{{ $order->id }}</td>
                        <td class="px-4 py-2">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-2">{{ $order->status }}</td>
                        <td class="px-4 py-2">{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-center text-gray-500">Aucune commande</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

