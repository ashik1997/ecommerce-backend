<?php

namespace App\Http\Controllers;

use App\Models\BreakingNews;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class BreakingNewsController extends Controller
{
    public function create()
    {
        return view('backend.breaking_news.create');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = BreakingNews::query()->orderByRaw('serial IS NULL, serial ASC')->orderByDesc('id');

            return DataTables::of($data)
                ->editColumn('status', function (BreakingNews $row) {
                    if ((int) $row->status === 1) {
                        return '<span class="btn btn-sm btn-success rounded" style="padding: 0.1rem .5rem;">Active</span>';
                    }

                    return '<span class="btn btn-sm btn-warning rounded" style="padding: 0.1rem .5rem;">Inactive</span>';
                })
                ->editColumn('url', function (BreakingNews $row) {
                    if (!$row->url) {
                        return '';
                    }
                    $escaped = e($row->url);
                    $short = e(Str::limit($row->url, 48));

                    return '<a href="' . $escaped . '" target="_blank" rel="noopener">' . $short . '</a>';
                })
                ->addIndexColumn()
                ->addColumn('action', function (BreakingNews $row) {
                    $btn = ' <a href="' . url('edit/breaking-news/' . $row->id) . '" class="mb-1 btn-sm btn-warning rounded"><i class="fas fa-edit"></i></a>';
                    $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $row->id . '" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';

                    return $btn;
                })
                ->rawColumns(['action', 'status', 'url'])
                ->make(true);
        }

        return view('backend.breaking_news.view');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'serial' => ['nullable', 'integer'],
            'url' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', 'in:0,1'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = time().uniqid();

        BreakingNews::create([
            'title' => $validated['title'],
            'serial' => $validated['serial'] ?? null,
            'url' => $validated['url'] ?? null,
            'status' => (int) $request->input('status', 1),
            'creator' => Auth::user()?->name,
            'slug' => $slug,
        ]);

        Toastr::success('Breaking news has been added', 'Success');

        return redirect()->route('ViewAllBreakingNews');
    }

    public function edit($id)
    {
        $data = BreakingNews::findOrFail($id);

        return view('backend.breaking_news.update', compact('data'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:breaking_news,id'],
            'title' => ['required', 'string', 'max:500'],
            'serial' => ['nullable', 'integer'],
            'url' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', 'in:0,1'],
            'slug' => ['nullable', 'string', 'max:255'],
            'creator' => ['nullable', 'string', 'max:255'],
        ]);

        $row = BreakingNews::findOrFail($validated['id']);
        $slug = time().uniqid();

        $row->update([
            'title' => $validated['title'],
            'serial' => $validated['serial'] ?? null,
            'url' => $validated['url'] ?? null,
            'status' => $request->has('status')
                ? (int) $request->input('status')
                : (int) ($row->status ?? 1),
            'creator' => $validated['creator'] ?? $row->creator,
            'slug' => $slug,
        ]);

        Toastr::success('Breaking news updated', 'Success');

        return redirect()->route('ViewAllBreakingNews');
    }

    public function destroy($id)
    {
        $row = BreakingNews::findOrFail($id);
        $row->delete();

        return response()->json(['success' => 'Breaking news deleted successfully.']);
    }

    private function resolveSlug(?string $slugInput, string $title, ?int $ignoreId = null): ?string
    {
        $slugInput = $slugInput !== null ? trim($slugInput) : null;
        $title = trim($title);

        $base = $slugInput !== null && $slugInput !== ''
            ? Str::slug($slugInput)
            : Str::slug($title);

        if ($base === '') {
            return null;
        }

        $slug = $base;
        $i = 1;
        while (BreakingNews::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
