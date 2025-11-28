@extends('admin.layout')

@section('title', 'Créer une FAQ')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Créer une FAQ</h1>

    <form action="{{ route('admin.faqs.store') }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="question">Question *</label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                   id="question" type="text" name="question" value="{{ old('question') }}" required>
            @error('question') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="answer">Réponse *</label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                      id="answer" name="answer" rows="6" required>{{ old('answer') }}</textarea>
            @error('answer') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="order">Ordre</label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                   id="order" type="number" name="order" value="{{ old('order') }}">
        </div>

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" 
                       id="is_active" 
                       name="is_active" 
                       value="1" 
                       {{ old('is_active', true) ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="ml-2 text-gray-700 font-semibold">
                    <i class="fas fa-toggle-on text-green-500 mr-1"></i>Active
                </span>
            </label>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Créer
            </button>
            <a href="{{ route('admin.faqs') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection

