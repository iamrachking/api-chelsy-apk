@extends('admin.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Utilisateurs')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-users text-blue-600 mr-3"></i>
                    Utilisateurs
                </h1>
                <p class="text-gray-500 mt-1">Gérez les utilisateurs et administrateurs</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.users.export') }}" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all shadow-lg">
                    <i class="fas fa-file-export mr-2"></i>
                    Exporter en CSV
                </a>
                <a href="{{ route('admin.users.create-admin') }}" class="flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-user-plus mr-2"></i>
                    Créer un Admin
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Téléphone</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors {{ $user->is_blocked ? 'bg-red-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($user->avatar)
                                        <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->firstname }}" class="h-10 w-10 rounded-full object-cover mr-3">
                                    @else
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $user->firstname }} {{ $user->lastname }}
                                            @if($user->is_admin)
                                                <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                                    <i class="fas fa-shield-alt mr-1"></i>Admin
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">{{ $user->email }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">{{ $user->phone ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->is_blocked)
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-ban mr-1"></i>Bloqué
                                    </span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Actif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="far fa-calendar mr-2"></i>{{ $user->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @php
                                        $currentUser = auth()->user();
                                        $canBlock = true;
                                        $canDelete = true;
                                        if ($user->is_admin && $adminCount <= 1) {
                                            $canBlock = false;
                                            $canDelete = false;
                                        }
                                        if ($user->id === $currentUser->id && $user->is_admin && $adminCount <= 1) {
                                            $canBlock = false;
                                            $canDelete = false;
                                        }
                                    @endphp
                                    
                                    @if($canBlock)
                                        <form action="{{ route('admin.users.toggle-block', $user->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="{{ $user->is_blocked ? 'text-green-600 hover:text-green-900' : 'text-orange-600 hover:text-orange-900' }} transition-colors" title="{{ $user->is_blocked ? 'Débloquer' : 'Bloquer' }}">
                                                <i class="fas fa-{{ $user->is_blocked ? 'unlock' : 'lock' }}"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if($canDelete)
                                        <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" class="inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 block"></i>
                                Aucun utilisateur trouvé
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 px-6">
            {{ $users->links() }}
        </div>
    </div>
</div>

@if(session('admin_password'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '⚠️ Email non envoyé',
            html: `
                <div class="text-left">
                    <p class="mb-3">L'administrateur <strong>{{ session('admin_name') }}</strong> ({{ session('admin_email') }}) a été créé avec succès, mais l'email n'a pas pu être envoyé.</p>
                    <p class="mb-2"><strong>Mot de passe temporaire :</strong></p>
                    <div class="bg-gray-100 p-3 rounded border-2 border-blue-500 mb-3">
                        <code class="text-lg font-mono font-bold text-blue-600">{{ session('admin_password') }}</code>
                    </div>
                    <p class="text-sm text-gray-600">⚠️ Veuillez noter ce mot de passe et le communiquer manuellement à l'administrateur.</p>
                </div>
            `,
            icon: 'warning',
            confirmButtonText: 'J\'ai noté le mot de passe',
            confirmButtonColor: '#3085d6',
            width: '600px',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    });
</script>
@endif
@endsection
