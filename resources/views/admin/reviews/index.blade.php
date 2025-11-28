@extends('admin.layout')

@section('title', 'Avis')

@section('content')
<h1 class="text-2xl font-bold mb-6">Avis</h1>

<div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plat</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commentaire</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($reviews as $review)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $review->user->firstname ?? 'N/A' }} {{ $review->user->lastname ?? '' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $review->dish->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $review->rating }}/5</td>
                    <td class="px-6 py-4">{{ Str::limit($review->comment, 50) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded {{ $review->is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $review->is_approved ? 'Approuvé' : 'En attente' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if(!$review->is_approved)
                            <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800 mr-2">Approuver</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.reviews.reject', $review->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800">Rejeter</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucun avis</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-4">
        {{ $reviews->links() }}
    </div>
</div>
@endsection

