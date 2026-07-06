@extends('layouts.app')
@section('title', 'تعديل مريض مؤمّن')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('insured-patients.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
            <h2 class="text-xl font-bold text-gray-800">تعديل المريض — {{ $insuredPatient->name }}</h2>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-red-600 text-sm">
            @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
        @endif

        <form action="{{ route('insured-patients.update', $insuredPatient) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            @include('insured-patients._form', ['insuredPatient' => $insuredPatient])
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-save"></i> حفظ التعديلات
                </button>
                <a href="{{ route('insured-patients.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
