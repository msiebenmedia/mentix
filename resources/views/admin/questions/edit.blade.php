@extends('layouts.dashboard')

@section('title', 'Frage bearbeiten')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-4xl mx-auto">

        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Frage bearbeiten</h1>
                <p class="text-base-content/70 mt-1">
                    Hier kannst du eine bestehende Frage bearbeiten.
                </p>
            </div>

            <a href="{{ route('admin.questions.index') }}" class="btn btn-ghost">
                Zurück
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-6">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error mb-6">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error mb-6">
                <div>
                    <h3 class="font-bold">Es gibt Fehler im Formular:</h3>
                    <ul class="list-disc ml-5 mt-2 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @php
            $choiceTypes = [
                \App\Models\Question::TYPE_SINGLE_CHOICE,
                \App\Models\Question::TYPE_IMAGE_CHOICE,
            ];

            $currentCorrectOptionIndex = $question->options->search(function ($option) {
                return (bool) $option->is_correct;
            });

            if ($currentCorrectOptionIndex === false) {
                $currentCorrectOptionIndex = null;
            }

            $choiceOptions = old('options');
            if (!is_array($choiceOptions)) {
                $choiceOptions = $question->type !== \App\Models\Question::TYPE_SORTING
                    ? $question->options->map(function ($option) {
                        return [
                            'label' => $option->label,
                            'option_text' => $option->option_text,
                        ];
                    })->values()->all()
                    : [];
            }

            if (empty($choiceOptions)) {
                $choiceOptions = [
                    ['label' => 'A', 'option_text' => ''],
                    ['label' => 'B', 'option_text' => ''],
                    ['label' => 'C', 'option_text' => ''],
                    ['label' => 'D', 'option_text' => ''],
                ];
            }

            $sortingOptions = old('options');
            if (!is_array($sortingOptions)) {
                $sortingOptions = $question->type === \App\Models\Question::TYPE_SORTING
                    ? $question->options
                        ->sortBy('sort_order')
                        ->values()
                        ->map(function ($option, $index) {
                            return [
                                'label' => $option->label ?? (string) ($index + 1),
                                'option_text' => $option->option_text,
                                'sort_order' => (int) $option->sort_order,
                            ];
                        })->all()
                    : [];
            }

            if (empty($sortingOptions)) {
                $sortingOptions = [
                    ['label' => '1', 'option_text' => '', 'sort_order' => 1],
                    ['label' => '2', 'option_text' => '', 'sort_order' => 2],
                    ['label' => '3', 'option_text' => '', 'sort_order' => 3],
                    ['label' => '4', 'option_text' => '', 'sort_order' => 4],
                ];
            }
        @endphp

        <div
            x-data="questionForm()"
            class="card bg-base-100 shadow-xl border border-base-300"
        >
            <div class="card-body">
                <h2 class="card-title mb-4">Fragendaten</h2>

                <form method="POST" action="{{ route('admin.questions.update', $question) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Fragenkatalog</span>
                            </label>
                            <select name="question_catalog_id" class="select select-bordered w-full" required>
                                <option value="">Bitte Katalog wählen</option>
                                @foreach($catalogs as $catalog)
                                    <option value="{{ $catalog->id }}" @selected(old('question_catalog_id', $question->question_catalog_id) == $catalog->id)>
                                        {{ $catalog->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Fragetyp</span>
                            </label>
                            <select
                                name="type"
                                x-model="type"
                                class="select select-bordered w-full"
                                required
                            >
                                <option value="">Bitte Typ wählen</option>
                                @foreach($types as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $question->type) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Fragetext</span>
                        </label>
                        <textarea
                            name="question"
                            rows="4"
                            class="textarea textarea-bordered w-full"
                            placeholder="Frage eingeben..."
                            required
                        >{{ old('question', $question->question) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Punkte</span>
                            </label>
                            <input
                                type="number"
                                name="points"
                                value="{{ old('points', $question->points) }}"
                                min="1"
                                class="input input-bordered w-full"
                                required
                            >
                        </div>

                        <div class="form-control w-full">
                            <label class="label cursor-pointer justify-start gap-3 mt-8 md:mt-10">
                                <input type="hidden" name="is_active" value="0">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    class="toggle toggle-primary"
                                    @checked(old('is_active', $question->is_active))
                                >
                                <span class="label-text">Frage ist aktiviert</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Erklärung / Zusatzinfo</span>
                        </label>
                        <textarea
                            name="explanation"
                            rows="3"
                            class="textarea textarea-bordered w-full"
                            placeholder="Optional, z. B. Erklärung nach dem Auflösen"
                        >{{ old('explanation', $question->explanation) }}</textarea>
                    </div>

                    {{-- Bildfrage --}}
                    <template x-if="type === 'image_choice'">
                        <div>
                            <div class="divider">Bild</div>

                            @if($question->image_path)
                                <div class="mb-4">
                                    <p class="text-sm font-medium mb-2">Aktuelles Bild</p>
                                    <div class="rounded-xl border border-base-300 bg-base-200 p-3 inline-block">
                                        <img
                                            src="{{ asset('storage/' . $question->image_path) }}"
                                            alt="Fragenbild"
                                            class="max-h-64 rounded-lg object-contain"
                                        >
                                    </div>
                                </div>

                                <div class="form-control w-full mb-4">
                                    <label class="label cursor-pointer justify-start gap-3">
                                        <input type="hidden" name="remove_image" value="0">
                                        <input
                                            type="checkbox"
                                            name="remove_image"
                                            value="1"
                                            class="checkbox checkbox-error"
                                            @checked(old('remove_image'))
                                        >
                                        <span class="label-text">Vorhandenes Bild entfernen</span>
                                    </label>
                                </div>
                            @endif

                            <div class="form-control w-full">
                                <label class="label">
                                    <span class="label-text">
                                        {{ $question->image_path ? 'Bild ersetzen' : 'Bild hochladen' }}
                                    </span>
                                </label>
                                <input
                                    type="file"
                                    name="image"
                                    accept="image/*"
                                    class="file-input file-input-bordered w-full"
                                >
                                <label class="label">
                                    <span class="label-text-alt">Max. 5 MB</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    {{-- Single Choice + Bildfrage --}}
                    <template x-if="type === 'single_choice' || type === 'image_choice'">
                        <div>
                            <div class="divider">Antwortmöglichkeiten</div>

                            <div class="space-y-4">
                                @foreach($choiceOptions as $index => $option)
                                    <div class="grid grid-cols-1 md:grid-cols-[80px_1fr_140px] gap-4 items-end">
                                        <div class="form-control w-full">
                                            <label class="label">
                                                <span class="label-text">Label</span>
                                            </label>
                                            <input
                                                type="text"
                                                name="options[{{ $index }}][label]"
                                                value="{{ $option['label'] ?? '' }}"
                                                class="input input-bordered w-full"
                                                readonly
                                            >
                                        </div>

                                        <div class="form-control w-full">
                                            <label class="label">
                                                <span class="label-text">Antwort {{ $option['label'] ?? $index + 1 }}</span>
                                            </label>
                                            <input
                                                type="text"
                                                name="options[{{ $index }}][option_text]"
                                                value="{{ $option['option_text'] ?? '' }}"
                                                class="input input-bordered w-full"
                                                placeholder="Antwortmöglichkeit eingeben"
                                            >
                                        </div>

                                        <div class="form-control w-full">
                                            <label class="label">
                                                <span class="label-text">Richtig?</span>
                                            </label>
                                            <label class="label cursor-pointer justify-start gap-3 border border-base-300 rounded-lg px-4 py-3">
                                                <input
                                                    type="radio"
                                                    name="correct_option"
                                                    value="{{ $index }}"
                                                    class="radio radio-primary"
                                                    @checked(
                                                        (string) old('correct_option', $currentCorrectOptionIndex) === (string) $index
                                                    )
                                                >
                                                <span class="label-text">Korrekt</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </template>

                    {{-- Schätzfrage --}}
                    <template x-if="type === 'estimate'">
                        <div>
                            <div class="divider">Schätzfrage</div>

                            <div class="form-control w-full max-w-md">
                                <label class="label">
                                    <span class="label-text">Richtige Zahl</span>
                                </label>
                                <input
                                    type="number"
                                    step="1"
                                    name="correct_numeric_answer"
                                    value="{{ old('correct_numeric_answer', $question->correct_numeric_answer) }}"
                                    class="input input-bordered w-full"
                                    placeholder="z. B. 1234"
                                >
                            </div>
                        </div>
                    </template>

                    {{-- Datumfrage --}}
                    <template x-if="type === 'date_guess'">
                        <div>
                            <div class="divider">Datumfrage</div>

                            <div class="form-control w-full max-w-md">
                                <label class="label">
                                    <span class="label-text">Richtiges Datum</span>
                                </label>
                                <input
                                    type="date"
                                    name="correct_date_answer"
                                    value="{{ old('correct_date_answer', optional($question->correct_date_answer)->format('Y-m-d')) }}"
                                    class="input input-bordered w-full"
                                >
                            </div>
                        </div>
                    </template>

                    {{-- Sortierfrage --}}
                    <template x-if="type === 'sorting'">
                        <div>
                            <div class="divider">Sortierfrage</div>

                            <div class="space-y-4">
                                @foreach($sortingOptions as $index => $option)
                                    <div class="grid grid-cols-1 md:grid-cols-[1fr_140px] gap-4 items-end">
                                        <div class="form-control w-full">
                                            <label class="label">
                                                <span class="label-text">Antwort {{ $index + 1 }}</span>
                                            </label>
                                            <input
                                                type="text"
                                                name="options[{{ $index }}][option_text]"
                                                value="{{ $option['option_text'] ?? '' }}"
                                                class="input input-bordered w-full"
                                                placeholder="Eintrag eingeben"
                                            >
                                        </div>

                                        <div class="form-control w-full">
                                            <label class="label">
                                                <span class="label-text">Reihenfolge</span>
                                            </label>
                                            <select
                                                name="options[{{ $index }}][sort_order]"
                                                class="select select-bordered w-full"
                                            >
                                                @for($i = 1; $i <= 4; $i++)
                                                    <option value="{{ $i }}" @selected((int) ($option['sort_order'] ?? ($index + 1)) === $i)>
                                                        {{ $i }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>

                                        <input
                                            type="hidden"
                                            name="options[{{ $index }}][label]"
                                            value="{{ $option['label'] ?? ($index + 1) }}"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </template>

                    <div class="card-actions justify-end pt-4">
                        <button type="submit" class="btn btn-primary">
                            Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    function questionForm() {
        return {
            type: @js(old('type', $question->type))
        }
    }
</script>
@endsection