@extends('admin.layout')

@section('title', 'Importer des Catégories')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.categories') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center mt-4">
                <i class="fas fa-file-import text-blue-600 mr-3"></i>
                Importer des Catégories
            </h1>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Format du fichier CSV</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p class="mb-2">Le fichier CSV doit contenir les colonnes suivantes (séparées par des points-virgules <code class="bg-blue-100 px-1 rounded">;</code>) :</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Nom</strong> (obligatoire) - Le nom de la catégorie</li>
                            <li><strong>Slug</strong> (optionnel) - L'identifiant unique. S'il n'est pas fourni, il sera généré automatiquement</li>
                            <li><strong>Description</strong> (optionnel) - Description de la catégorie</li>
                            <li><strong>Ordre</strong> (optionnel) - Ordre d'affichage (par défaut: 0)</li>
                            <li><strong>Actif</strong> (optionnel) - 1/0, Oui/Non, True/False (par défaut: Oui)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Exemple de fichier CSV :</h3>
            <pre class="text-xs bg-white p-3 rounded border overflow-x-auto"><code>Nom;Slug;Description;Ordre;Actif
Entrées;entrees;Nos délicieuses entrées;1;Oui
Plats Principaux;plats-principaux;Nos plats principaux;2;Oui
Desserts;desserts;Nos desserts;3;Oui
Boissons;boissons;Nos boissons;4;Oui</code></pre>
        </div>

        <form action="{{ route('admin.categories.import') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-file-csv text-blue-500 mr-2"></i>
                    Fichier CSV <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                        <div class="flex text-sm text-gray-600">
                            <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>Télécharger un fichier</span>
                                <input id="file" name="file" type="file" accept=".csv,.txt" class="sr-only" required>
                            </label>
                            <p class="pl-1">ou glissez-déposez</p>
                        </div>
                        <p class="text-xs text-gray-500">CSV, TXT jusqu'à 5MB</p>
                    </div>
                </div>
                @error('file')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center">
                    <i class="fas fa-upload mr-2"></i>
                    Importer
                </button>
                <a href="{{ route('admin.categories') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    Annuler
                </a>
            </div>
        </form>

        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Note importante</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Si une catégorie avec le même slug existe déjà, elle sera mise à jour au lieu d'être créée.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Afficher le nom du fichier sélectionné
    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            const label = document.querySelector('label[for="file"]');
            if (label) {
                label.innerHTML = `<span>${fileName}</span>`;
            }
        }
    });
</script>
@endpush
@endsection

