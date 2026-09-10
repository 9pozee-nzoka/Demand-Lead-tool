<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\RunSourceScrape;
use App\Models\SourceScraper;
use App\Services\Sources\SourceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SourcesController extends Controller
{
    public function __construct(
        protected SourceManager $sourceManager
    ) {}

    /**
     * Display sources list
     */
    public function index(Request $request)
    {
        // New dashboard view - uses Alpine.js + API calls
        return view('sources.dashboard');
    }

    /**
     * Show create source form
     */
    public function create()
    {
        $availableProviders = $this->sourceManager->getAvailableProviders();
        
        return view('sources.create', compact('availableProviders'));
    }

    /**
     * Store new source
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:rss,tender,webhook,api,scraper',
            'base_url' => 'nullable|url',
            'schedule' => 'nullable|string',
            'configuration' => 'nullable|array',
            'credentials' => 'nullable|string',
        ]);

        try {
            $source = $this->sourceManager->createSource(
                auth()->user()->organization,
                $validated
            );

            return redirect()
                ->route('sources.show', $source)
                ->with('success', 'Source created successfully');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show source details
     */
    public function show(SourceScraper $source)
    {
        $this->authorize('view', $source);

        $source->load([
            'scrapeJobs' => function ($q) {
                $q->latest()->limit(10);
            },
            'events' => function ($q) {
                $q->latest()->limit(20);
            }
        ]);

        $stats = $this->sourceManager->getSourceStats($source);

        $recentItems = $source->scrapedItems()
            ->latest()
            ->limit(20)
            ->get();

        return view('sources.show', compact('source', 'stats', 'recentItems'));
    }

    /**
     * Show edit form
     */
    public function edit(SourceScraper $source)
    {
        $this->authorize('update', $source);

        return view('sources.edit', compact('source'));
    }

    /**
     * Update source
     */
    public function update(Request $request, SourceScraper $source)
    {
        $this->authorize('update', $source);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'nullable|url',
            'schedule' => 'nullable|string',
            'configuration' => 'nullable|array',
            'credentials' => 'nullable|string',
        ]);

        try {
            $this->sourceManager->updateSource($source, $validated);

            return redirect()
                ->route('sources.show', $source)
                ->with('success', 'Source updated successfully');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Test source connection
     */
    public function test(SourceScraper $source)
    {
        $this->authorize('update', $source);

        try {
            $result = $this->sourceManager->testSource($source);

            if ($result['success']) {
                return back()->with('success', 'Connection test successful: ' . $result['message']);
            } else {
                return back()->withErrors(['error' => 'Connection test failed: ' . $result['message']]);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Run source manually
     */
    public function run(SourceScraper $source)
    {
        $this->authorize('update', $source);

        try {
            RunSourceScrape::dispatch($source);

            return back()->with('success', 'Source scrape job dispatched to queue');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Pause source
     */
    public function pause(SourceScraper $source)
    {
        $this->authorize('update', $source);

        $this->sourceManager->pauseSource($source);

        return back()->with('success', 'Source paused successfully');
    }

    /**
     * Activate source
     */
    public function activate(SourceScraper $source)
    {
        $this->authorize('update', $source);

        $this->sourceManager->activateSource($source);

        return back()->with('success', 'Source activated successfully');
    }

    /**
     * Delete source
     */
    public function destroy(SourceScraper $source)
    {
        $this->authorize('delete', $source);

        try {
            $this->sourceManager->deleteSource($source);

            return redirect()
                ->route('sources.index')
                ->with('success', 'Source deleted successfully');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * View scraped items
     */
    public function items(SourceScraper $source)
    {
        $this->authorize('view', $source);

        $items = $source->scrapedItems()
            ->with(['opportunity', 'lead'])
            ->latest()
            ->paginate(50);

        return view('sources.items', compact('source', 'items'));
    }

    /**
     * View source events/logs
     */
    public function events(SourceScraper $source)
    {
        $this->authorize('view', $source);

        $events = $source->events()
            ->with('user')
            ->latest()
            ->paginate(50);

        return view('sources.events', compact('source', 'events'));
    }
}
