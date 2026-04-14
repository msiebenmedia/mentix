@php
    $catalog = $catalog ?? null;
@endphp

<div class="grid grid-cols-1 gap-6">

    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body space-y-5">

            <div class="form-control">
                <label class="label" for="title">
                    <span class="label-text font-medium">Titel</span>
                </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $catalog?->title) }}"
                    class="input input-bordered w-full @error('title') input-error @enderror"
                    placeholder="z. B. Allgemeinwissen"
                    required
                >
                @error('title')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label" for="description">
                    <span class="label-text font-medium">Beschreibung</span>
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    class="textarea textarea-bordered w-full @error('description') textarea-error @enderror"
                    placeholder="Optionale Beschreibung für den Fragenkatalog..."
                >{{ old('description', $catalog?->description) }}</textarea>
                @error('description')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-3">
                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="toggle toggle-primary"
                        @checked(old('is_active', $catalog?->is_active ?? true))
                    >
                    <span class="label-text font-medium">Katalog aktiv</span>
                </label>
                @error('is_active')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.question-catalogs.index') }}" class="btn btn-ghost">
            Abbrechen
        </a>

        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy"></i>
            Speichern
        </button>
    </div>

</div>