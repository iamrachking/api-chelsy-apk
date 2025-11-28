@extends('admin.layout')

@section('title', 'Détails de la Commande')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Commande #{{ $order->id }}</h1>
        <a href="{{ route('admin.orders') }}" class="text-blue-600 hover:text-blue-800">← Retour</a>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-xl font-bold mb-4">Informations</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-gray-600">Client</p>
                <p class="font-semibold">{{ $order->user->firstname ?? 'N/A' }} {{ $order->user->lastname ?? '' }}</p>
            </div>
            <div>
                <p class="text-gray-600">Statut</p>
                <p class="font-semibold">{{ $order->status }}</p>
            </div>
            <div>
                <p class="text-gray-600">Total</p>
                <p class="font-semibold">{{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
            </div>
            <div>
                <p class="text-gray-600">Date</p>
                <p class="font-semibold">{{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-xl font-bold mb-4">Articles</h2>
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 text-left">Plat</th>
                    <th class="px-4 py-2 text-left">Quantité</th>
                    <th class="px-4 py-2 text-left">Prix</th>
                    <th class="px-4 py-2 text-left">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $item->dish->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $item->quantity }}</td>
                        <td class="px-4 py-2">{{ number_format($item->price, 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-2">{{ number_format($item->price * $item->quantity, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Changer le statut</h2>
        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
            @csrf
            @method('PUT')
            <select name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-4">
                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>En préparation</option>
                <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>Prête</option>
                <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Livrée</option>
                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Mettre à jour
            </button>
        </form>
    </div>
</div>
@endsection

