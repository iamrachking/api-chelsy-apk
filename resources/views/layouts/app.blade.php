<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.svg') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <!-- Toastr -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @if(session('success'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-400 mr-2"></i>
                                <p class="text-green-700">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-400 mr-2"></i>
                                <p class="text-red-700">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
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
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>

        <script>
            // Toastr configuration
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "4000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            @if(session('success'))
                toastr.success('{{ session('success') }}', 'Succès');
            @endif

            @if(session('error'))
                toastr.error('{{ session('error') }}', 'Erreur');
            @endif

            // SweetAlert pour la déconnexion
            function handleLogout() {
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
                        document.getElementById('logoutForm')?.submit() || document.getElementById('logoutFormMobile')?.submit();
                    }
                });
            }

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
    </body>
</html>
