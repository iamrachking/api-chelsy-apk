@extends('admin.layout')

@section('title', 'Modifier une FAQ')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.faqs') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center mt-4">
                <i class="fas fa-edit text-blue-600 mr-3"></i>
                Modifier une FAQ
            </h1>
        </div>

        <form action="{{ route('admin.faqs.update', $faq->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="question">
                    <i class="fas fa-question-circle text-blue-500 mr-2"></i>Question *
                </label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                       id="question" type="text" name="question" value="{{ old('question', $faq->question) }}" required>
                @error('question') 
                    <p class="text-red-500 text-xs mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p> 
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="answer">
                    <i class="fas fa-comment-alt text-blue-500 mr-2"></i>Réponse *
                </label>
                <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                          id="answer" name="answer" rows="6" required>{{ old('answer', $faq->answer) }}</textarea>
                @error('answer') 
                    <p class="text-red-500 text-xs mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p> 
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="order">
                    <i class="fas fa-sort-numeric-down text-blue-500 mr-2"></i>Ordre
                </label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                       id="order" type="number" name="order" value="{{ old('order', $faq->order ?? 0) }}" min="0">
                @error('order') 
                    <p class="text-red-500 text-xs mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p> 
                @enderror
            </div>

            <div class="flex items-center">
                <input type="checkbox" 
                       id="is_active" 
                       name="is_active" 
                       value="1" 
                       {{ old('is_active', $faq->is_active) ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="is_active" class="ml-2 text-gray-700 font-semibold">
                    <i class="fas fa-toggle-on text-green-500 mr-1"></i>Active
                </label>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer les modifications
                </button>
                <a href="{{ route('admin.faqs') }}" class="flex items-center px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

