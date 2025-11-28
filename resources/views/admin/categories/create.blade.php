@extends('admin.layout')

@section('title', 'Créer une Catégorie')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.categories') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center mt-4">
                <i class="fas fa-plus-circle text-blue-600 mr-3"></i>
                Créer une Catégorie
            </h1>
        </div>

        <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="name">
                        <i class="fas fa-tag text-blue-500 mr-2"></i>Nom *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="slug">
                        <i class="fas fa-link text-blue-500 mr-2"></i>Slug *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="slug" type="text" name="slug" value="{{ old('slug') }}" required>
                    @error('slug') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="description">
                    <i class="fas fa-align-left text-blue-500 mr-2"></i>Description
                </label>
                <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                          id="description" name="description" rows="4">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="image">
                    <i class="fas fa-image text-blue-500 mr-2"></i>Image
                </label>
                <div class="mt-1 flex items-center space-x-5">
                    <label for="image" class="cursor-pointer flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez
                            </p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, WEBP (MAX. 2MB)</p>
                        </div>
                        <input type="file" id="image" name="image" class="hidden" accept="image/*" onchange="previewImage(this)">
                    </label>
                </div>
                <div id="imagePreview" class="mt-4 hidden">
                    <img id="previewImg" src="" alt="Aperçu" class="max-w-xs rounded-lg shadow-md">
                </div>
                @error('image') 
                    <p class="text-red-500 text-xs mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p> 
                @enderror
            </div>

            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} 
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="is_active" class="ml-3 text-sm font-medium text-gray-700">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>Catégorie active
                </label>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    Créer la catégorie
                </button>
                <a href="{{ route('admin.categories') }}" class="flex items-center px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('hidden');
    }
}
</script>
@endsection
