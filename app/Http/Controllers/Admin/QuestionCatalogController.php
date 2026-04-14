<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionCatalog;
use Illuminate\Http\Request;

class QuestionCatalogController extends Controller
{
 public function index(Request $request)
{
    $search = $request->string('search')->toString();
    $status = $request->string('status')->toString();

    $catalogs = QuestionCatalog::query()
        ->withCount('questions')
        ->when($search, function ($query) use ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        })
        ->when($status !== '', function ($query) use ($status) {
            if ($status === 'active') {
                $query->where('is_active', true);
            }

            if ($status === 'inactive') {
                $query->where('is_active', false);
            }
        })
        ->latest()
        ->paginate(15)
        ->withQueryString();

    return view('admin.question-catalogs.index', compact('catalogs'));
}

    public function create()
    {
        return view('admin.question-catalogs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        QuestionCatalog::create($validated);

        return redirect()
            ->route('admin.question-catalogs.index')
            ->with('success', 'Fragenkatalog wurde erstellt.');
    }

    public function show(QuestionCatalog $questionCatalog)
    {
        $questionCatalog->load(['questions' => function ($query) {
            $query->latest();
        }]);

        return view('admin.question-catalogs.show', compact('questionCatalog'));
    }

    public function edit(QuestionCatalog $questionCatalog)
    {
        return view('admin.question-catalogs.edit', compact('questionCatalog'));
    }

    public function update(Request $request, QuestionCatalog $questionCatalog)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $questionCatalog->update($validated);

        return redirect()
            ->route('admin.question-catalogs.index')
            ->with('success', 'Fragenkatalog wurde aktualisiert.');
    }

    public function destroy(QuestionCatalog $questionCatalog)
    {
        $questionCatalog->delete();

        return redirect()
            ->route('admin.question-catalogs.index')
            ->with('success', 'Fragenkatalog wurde gelöscht.');
    }
}