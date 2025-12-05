<!DOCTYPE html>
<html lang="fr">
@php
use Illuminate\Support\Facades\Storage;
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin - CHELSY Restaurant')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.svg') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('styles')
    @vite(['resources/js/app.js'])
    <style>
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar-hidden {
            transform: translateX(-100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-blue-600 to-blue-800 text-white transform transition-transform duration-300 ease-in-out">
        <div class="flex items-center justify-between h-16 px-6 border-b border-blue-500">
            <h1 class="text-xl font-bold flex items-center">
                <i class="fas fa-utensils mr-2"></i>
                CHELSY Admin
            </h1>
            <button id="sidebarToggle" class="lg:hidden text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="mt-6">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                Dashboard
            </a>
            <a href="{{ route('admin.restaurant') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.restaurant*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-store w-5 mr-3"></i>
                Restaurant
            </a>
            <a href="{{ route('admin.categories') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.categories*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-tags w-5 mr-3"></i>
                Catégories
            </a>
            <a href="{{ route('admin.dishes') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.dishes*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-hamburger w-5 mr-3"></i>
                Plats
            </a>
            <a href="{{ route('admin.orders') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.orders*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-shopping-cart w-5 mr-3"></i>
                Commandes
            </a>
            <a href="{{ route('admin.reviews') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.reviews*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-star w-5 mr-3"></i>
                Avis
            </a>
            <a href="{{ route('admin.complaints') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.complaints*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-exclamation-triangle w-5 mr-3"></i>
                Réclamations
            </a>
            <a href="{{ route('admin.promo-codes') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.promo-codes*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-ticket-alt w-5 mr-3"></i>
                Codes Promo
            </a>
            <a href="{{ route('admin.banners') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.banners*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-image w-5 mr-3"></i>
                Bannières
            </a>
            <a href="{{ route('admin.faqs') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.faqs*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-question-circle w-5 mr-3"></i>
                FAQ
            </a>
            <a href="{{ route('admin.users') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.users*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-users w-5 mr-3"></i>
                Utilisateurs
            </a>
            <a href="{{ route('admin.profile') }}" class="flex items-center px-6 py-3 text-white hover:bg-blue-700 transition-colors {{ request()->routeIs('admin.profile*') ? 'bg-blue-700 border-r-4 border-yellow-400' : '' }}">
                <i class="fas fa-user-circle w-5 mr-3"></i>
                Mon Profil
            </a>
        </nav>
        <div class="absolute bottom-0 w-full p-6 border-t border-blue-500">
            <button id="logoutBtn" class="flex items-center w-full px-4 py-2 text-white hover:bg-red-600 rounded transition-colors">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                Déconnexion
            </button>
            <form id="logoutForm" action="{{ route('admin.logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between h-16 px-6 w-full">
                <button id="mobileSidebarToggle" class="lg:hidden text-gray-600 hover:text-gray-900">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                @php
                    $user = auth()->user();
                    $avatarUrl = $user->avatar ? Storage::url($user->avatar) : null;
                @endphp
                <a href="{{ route('admin.profile') }}" class="flex items-center space-x-3 hover:opacity-80 transition-opacity ml-auto flex-row-reverse ">
                    
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $user->firstname }} {{ $user->lastname }}" class="w-10 h-10 rounded-full object-cover border-2 border-blue-500 ml-3">
                    @else
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center border-2 border-blue-500 ml-3">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                    @endif
                    <span class="text-gray-700 font-semibold  ">
                        {{ $user->firstname }} {{ $user->lastname }}
                    </span>
                </a>
            </div>
        </header>

        <!-- Content -->
        <main class="p-6">
            <!-- Les notifications sont maintenant gérées par Toastr dans le script -->
            @if($errors->any())
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-400 mr-2"></i>
                        <div>
                            <p class="text-red-700 font-bold">Erreurs de validation :</p>
                            <ul class="list-disc list-inside text-red-600 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        // Sidebar toggle
        document.getElementById('mobileSidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('sidebar-hidden');
        });
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('sidebar-hidden');
        });


        // SweetAlert pour la déconnexion
        document.getElementById('logoutBtn')?.addEventListener('click', function() {
            Swal.fire({
                title: 'Déconnexion',
                text: 'Êtes-vous sûr de vouloir vous déconnecter ?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Oui, me déconnecter',
                cancelButtonText: 'Annuler',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutForm').submit();
                }
            });
        });

        // SweetAlert for delete confirmations
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-form').forEach(form => {
                if (form.hasAttribute('onsubmit')) {
                    form.removeAttribute('onsubmit');
                }
                
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    const formElement = this;
                    const button = formElement.querySelector('button[type="submit"]');
                    
                    Swal.fire({
                        title: 'Êtes-vous sûr ?',
                        text: "Cette action est irréversible !",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Oui, supprimer !',
                        cancelButtonText: 'Annuler',
                        reverseButtons: true,
                        buttonsStyling: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (button) {
                                button.disabled = true;
                                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Suppression...';
                            }
                            formElement.submit();
                        }
                    });
                    
                    return false;
                }, true);
            });
        });
    </script>

    @if (session('success') || session('error') || session('info') || session('warning'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
                toastr.success("{{ session('success') }}");
            @endif

            @if (session('error'))
                toastr.error("{{ session('error') }}");
            @endif

            @if (session('info'))
                toastr.info("{{ session('info') }}");
            @endif

            @if (session('warning'))
                toastr.warning("{{ session('warning') }}");
            @endif
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
