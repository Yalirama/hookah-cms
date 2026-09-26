<?php

namespace App\Http\Controllers;

use App\Models\WikiPage;
use Illuminate\Http\Request;
use App\Models\WikiPageView;

class WikiController extends Controller
{
    public function index()
    {
        $pages = WikiPage::where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return view('wiki.index', compact('pages'));
    }

   public function show(WikiPage $wikiPage)
    {
        if (!$wikiPage->is_published && !auth()->user()->hasRole('admin')) {
            abort(404);
        }

        // Записываем просмотр только для мастеров
        if (!auth()->user()->hasRole('admin')) {
            WikiPageView::create([
                'wiki_page_id' => $wikiPage->id,
                'user_id'      => auth()->id(),
                'viewed_at'    => now(),
            ]);
        }

        // Для админа загружаем статистику просмотров
        $views = null;
        if (auth()->user()->hasRole('admin')) {
            $views = $wikiPage->views()->with('user')->latest('viewed_at')->limit(50)->get();
        }

        return view('wiki.show', compact('wikiPage', 'views'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $maxSort = WikiPage::max('sort_order') ?? 0;

        return view('wiki.create', compact('maxSort'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'sort_order'   => 'nullable|integer',
            'is_published' => 'boolean',
        ]);

        WikiPage::create([
            'title'        => $request->title,
            'description'  => $request->description,
            'content'      => $request->content,
            'sort_order'   => $request->sort_order ?? 0,
            'is_published' => $request->boolean('is_published', true),
            'created_by'   => auth()->id(),
        ]);

        return redirect()->route('wiki.index')->with('success', 'Страница создана');
    }

    public function edit(WikiPage $wikiPage)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        return view('wiki.edit', compact('wikiPage'));
    }

    public function update(Request $request, WikiPage $wikiPage)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'sort_order'   => 'nullable|integer',
            'is_published' => 'boolean',
        ]);

        $wikiPage->update([
            'title'        => $request->title,
            'description'  => $request->description,
            'content'      => $request->content,
            'sort_order'   => $request->sort_order ?? $wikiPage->sort_order,
            'is_published' => $request->boolean('is_published', true),
            'updated_by'   => auth()->id(),
        ]);

        return redirect()->route('wiki.show', $wikiPage)->with('success', 'Страница обновлена');
    }

    public function uploadImage(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'upload' => 'required|image|max:5120', // 5MB
        ]);

        $path = $request->file('upload')->store('wiki', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }

    public function reorder(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'pages'   => 'required|array',
            'pages.*' => 'integer|exists:wiki_pages,id',
        ]);

        foreach ($request->pages as $order => $id) {
            WikiPage::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['success' => true]);
    }

    public function createSheet()
    {
        if (!auth()->user()->hasRole('admin')) abort(403);
        $maxSort = WikiPage::max('sort_order') ?? 0;

        return view('settings.partials._wiki-form', ['page' => null, 'maxSort' => $maxSort]);
    }

    public function editSheet(WikiPage $wikiPage)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        return view('settings.partials._wiki-form', ['page' => $wikiPage, 'maxSort' => null]);
    }

    public function viewSheet(WikiPage $wikiPage)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $views = $wikiPage->views()->with('user')->latest('viewed_at')->limit(50)->get();

        return view('settings.partials._wiki-view', compact('wikiPage', 'views'));
    }

    public function storeAjax(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'sort_order'   => 'nullable|integer',
            'is_published' => 'boolean',
        ]);

        $page = WikiPage::create([
            'title'        => $request->title,
            'description'  => $request->description,
            'content'      => $request->content,
            'sort_order'   => $request->sort_order ?? 0,
            'is_published' => $request->boolean('is_published', true),
            'created_by'   => auth()->id(),
        ]);

        return response()->json(['row' => view('settings.partials._wiki-row', ['page' => $page])->render()]);
    }

    public function updateAjax(Request $request, WikiPage $wikiPage)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:500',
            'content'      => 'nullable|string',
            'sort_order'   => 'nullable|integer',
            'is_published' => 'boolean',
        ]);

        $wikiPage->update([
            'title'        => $request->title,
            'description'  => $request->description,
            'content'      => $request->content,
            'sort_order'   => $request->sort_order ?? $wikiPage->sort_order,
            'is_published' => $request->boolean('is_published', true),
            'updated_by'   => auth()->id(),
        ]);

        return response()->json(['row' => view('settings.partials._wiki-row', ['page' => $wikiPage])->render()]);
    }

    public function togglePublish(WikiPage $wikiPage)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $wikiPage->update(['is_published' => !$wikiPage->is_published]);

        return response()->json(['row' => view('settings.partials._wiki-row', ['page' => $wikiPage])->render()]);
    }

    public function showSheet(WikiPage $wikiPage)
    {
        if (!$wikiPage->is_published && !auth()->user()->hasRole('admin')) {
            abort(404);
        }

        if (!auth()->user()->hasRole('admin')) {
            WikiPageView::create([
                'wiki_page_id' => $wikiPage->id,
                'user_id'      => auth()->id(),
                'viewed_at'    => now(),
            ]);
        }

        $views = null;
        if (auth()->user()->hasRole('admin')) {
            $views = $wikiPage->views()->with('user')->latest('viewed_at')->limit(50)->get();
        }

        return view('wiki.partials._article-content', compact('wikiPage', 'views'));
    }
}