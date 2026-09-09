<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Opportunity;
use App\Models\Lead;
use App\Services\LandingPages\LandingPageGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingPageController extends Controller
{
    protected LandingPageGenerator $generator;

    public function __construct(LandingPageGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Display all landing pages
     */
    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $pages = LandingPage::where('organization_id', $organizationId)
            ->with(['opportunity'])
            ->withCount('leads')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculate stats
        $stats = [
            'total_pages' => LandingPage::where('organization_id', $organizationId)->count(),
            'published_pages' => LandingPage::where('organization_id', $organizationId)
                ->where('status', 'published')
                ->count(),
            'total_views' => LandingPage::where('organization_id', $organizationId)
                ->sum('views'),
            'total_conversions' => Lead::whereHas('landingPage', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })->count(),
        ];

        return view('landing-pages.index', compact('pages', 'stats'));
    }

    /**
     * Show form to generate landing page from opportunity
     */
    public function create(Request $request)
    {
        $opportunities = Opportunity::where('organization_id', $request->user()->organization_id)
            ->where('status', 'open')
            ->with('keyword')
            ->orderBy('opportunity_score', 'desc')
            ->get();

        return view('landing-pages.create', compact('opportunities'));
    }

    /**
     * Generate landing page from opportunity
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
        ]);

        $opportunity = Opportunity::where('organization_id', $request->user()->organization_id)
            ->findOrFail($validated['opportunity_id']);

        try {
            $landingPage = $this->generator->generateFromOpportunity($opportunity);

            return redirect()
                ->route('landing-pages.edit', $landingPage)
                ->with('success', 'Landing page generated successfully! Review and customize before publishing.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to generate landing page: ' . $e->getMessage());
        }
    }

    /**
     * Show landing page editor
     */
    public function edit(LandingPage $landingPage)
    {
        $landingPage->load(['keyword', 'opportunity']);

        return view('landing-pages.edit', compact('landingPage'));
    }

    /**
     * Update landing page content
     */
    public function update(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'headline' => 'required|string|max:255',
            'subheadline' => 'required|string|max:500',
            'hero_content' => 'required|string',
            'benefits' => 'nullable|json',
            'features' => 'nullable|json',
            'social_proof' => 'nullable|json',
            'cta_text' => 'required|string|max:50',
            'cta_subtext' => 'nullable|string|max:255',
            'faq' => 'nullable|json',
            'meta_title' => 'required|string|max:60',
            'meta_description' => 'required|string|max:160',
            'template' => 'required|in:default,modern,minimal,bold',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
        ]);

        $landingPage->update($validated);

        // Recalculate SEO score
        $this->generator->optimizeForSeo($landingPage);

        return back()->with('success', 'Landing page updated successfully.');
    }

    /**
     * Publish landing page
     */
    public function publish(LandingPage $landingPage)
    {
        $landingPage->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', 'Landing page published successfully!');
    }

    /**
     * Unpublish landing page
     */
    public function unpublish(LandingPage $landingPage)
    {
        $landingPage->update(['status' => 'draft']);

        return back()->with('success', 'Landing page unpublished.');
    }

    /**
     * Delete landing page
     */
    public function destroy(LandingPage $landingPage)
    {
        $landingPage->delete();

        return redirect()
            ->route('landing-pages.index')
            ->with('success', 'Landing page deleted successfully.');
    }

    /**
     * Preview landing page
     */
    public function preview(LandingPage $landingPage)
    {
        return view('landing-pages.preview', compact('landingPage'));
    }

    /**
     * Public landing page view
     */
    public function show(string $slug)
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Increment views
        $landingPage->increment('views');

        return view('landing-pages.public', compact('landingPage'));
    }

    /**
     * Handle lead capture from landing page
     */
    public function capture(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        // Create lead
        $lead = Lead::create([
            'organization_id' => $landingPage->organization_id,
            'landing_page_id' => $landingPage->id,
            'keyword_id' => $landingPage->keyword_id,
            'opportunity_id' => $landingPage->opportunity_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'message' => $validated['message'] ?? null,
            'source' => 'landing_page',
            'status' => 'new',
            'lead_score' => 50, // Default score, will be calculated later
        ]);

        // Increment conversions
        $landingPage->increment('conversions');

        // Return success response
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! We will contact you soon.',
            ]);
        }

        return back()->with('success', 'Thank you! We will contact you soon.');
    }

    /**
     * Regenerate specific section
     */
    public function regenerateSection(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'section' => 'required|in:headline,benefits,features,faq',
        ]);

        try {
            $options = $this->generator->regenerateSection(
                $landingPage,
                $validated['section']
            );

            return response()->json([
                'success' => true,
                'options' => $options,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get analytics for landing page
     */
    public function analytics(LandingPage $landingPage)
    {
        $landingPage->load(['keyword', 'opportunity']);

        // Get conversion data
        $conversionRate = $landingPage->views > 0
            ? round(($landingPage->conversions / $landingPage->views) * 100, 2)
            : 0;

        // Get leads over time (last 30 days)
        $leadsOverTime = Lead::where('landing_page_id', $landingPage->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Lead quality distribution
        $leadQuality = Lead::where('landing_page_id', $landingPage->id)
            ->select('quality', DB::raw('COUNT(*) as count'))
            ->groupBy('quality')
            ->get();

        $stats = [
            'views' => $landingPage->views,
            'conversions' => $landingPage->conversions,
            'conversion_rate' => $conversionRate,
            'seo_score' => $landingPage->seo_score,
            'leads_today' => Lead::where('landing_page_id', $landingPage->id)
                ->whereDate('created_at', today())
                ->count(),
            'leads_this_week' => Lead::where('landing_page_id', $landingPage->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'leads_this_month' => Lead::where('landing_page_id', $landingPage->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        return view('landing-pages.analytics', compact(
            'landingPage',
            'stats',
            'leadsOverTime',
            'leadQuality'
        ));
    }

    /**
     * Duplicate landing page
     */
    public function duplicate(LandingPage $landingPage)
    {
        $newPage = $landingPage->replicate();
        $newPage->title = $landingPage->title . ' (Copy)';
        $newPage->slug = $this->generator->generateUniqueSlug($newPage->title);
        $newPage->status = 'draft';
        $newPage->views = 0;
        $newPage->conversions = 0;
        $newPage->published_at = null;
        $newPage->save();

        return redirect()
            ->route('landing-pages.edit', $newPage)
            ->with('success', 'Landing page duplicated successfully.');
    }
}
