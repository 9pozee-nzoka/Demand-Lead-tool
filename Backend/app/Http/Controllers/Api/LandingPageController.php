<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LandingPageController extends Controller
{
    /**
     * GET /api/v1/landing-pages
     */
    public function index(Request $request): JsonResponse
    {
        $pages = LandingPage::whereHas('project', fn ($q) =>
                $q->where('organization_id', $request->user()->organization_id))
            ->with(['project:id,name', 'opportunity:id,opportunity_score,trend_state'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'),     fn ($q) => $q->where('status', $request->status))
            ->withCount('leads')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($pages);
    }

    /**
     * POST /api/v1/landing-pages
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'     => ['required', 'integer', 'exists:projects,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'],
            'title'          => ['required', 'string', 'max:255'],
            'slug'           => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:landing_pages,slug'],
            'template'       => ['nullable', 'string', Rule::in(['minimal', 'hero', 'form_only', 'split', 'video'])],
            'content'        => ['nullable', 'string'],
            'meta'           => ['nullable', 'array'],
            'meta.description'  => ['nullable', 'string', 'max:300'],
            'meta.keywords'     => ['nullable', 'string', 'max:255'],
            'meta.og_image'     => ['nullable', 'url'],
        ]);

        // Verify project belongs to org
        $project = Project::where('id', $data['project_id'])
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        // Auto-generate slug from title if not provided
        $data['slug'] ??= $this->generateSlug($data['title']);
        $data['status']   = 'draft';
        $data['template'] ??= 'minimal';

        $page = LandingPage::create($data);

        return response()->json($page->load('project', 'opportunity'), 201);
    }

    /**
     * GET /api/v1/landing-pages/{page}
     */
    public function show(Request $request, LandingPage $landingPage): JsonResponse
    {
        $this->authorize($request, $landingPage);

        return response()->json(
            $landingPage->loadCount('leads')
                ->load(['project', 'opportunity.keyword'])
        );
    }

    /**
     * PATCH /api/v1/landing-pages/{page}
     */
    public function update(Request $request, LandingPage $landingPage): JsonResponse
    {
        $this->authorize($request, $landingPage);

        $data = $request->validate([
            'title'    => ['sometimes', 'string', 'max:255'],
            'slug'     => ['sometimes', 'string', 'max:100', 'alpha_dash',
                           Rule::unique('landing_pages', 'slug')->ignore($landingPage->id)],
            'template' => ['sometimes', 'string', Rule::in(['minimal','hero','form_only','split','video'])],
            'content'  => ['nullable', 'string'],
            'meta'     => ['nullable', 'array'],
            'status'   => ['sometimes', Rule::in(['draft','published','archived'])],
        ]);

        if (($data['status'] ?? null) === 'published' && ! $landingPage->published_at) {
            $data['published_at'] = now();
        }

        $landingPage->update($data);

        return response()->json($landingPage->fresh()->load('project', 'opportunity'));
    }

    /**
     * DELETE /api/v1/landing-pages/{page}
     */
    public function destroy(Request $request, LandingPage $landingPage): JsonResponse
    {
        $this->authorize($request, $landingPage);
        $landingPage->delete();

        return response()->json(['message' => 'Landing page deleted.']);
    }

    /**
     * GET /api/v1/capture/{slug}  (public — no auth)
     * Returns landing page data for the Angular capture form to render.
     */
    public function captureView(string $slug): JsonResponse
    {
        $page = LandingPage::where('slug', $slug)
            ->published()
            ->with(['project:id,name,country', 'opportunity:id,opportunity_score,trend_state'])
            ->firstOrFail();

        return response()->json([
            'slug'        => $page->slug,
            'title'       => $page->title,
            'content'     => $page->content,
            'template'    => $page->template,
            'meta'        => $page->meta,
            'project'     => $page->project,
            'opportunity' => $page->opportunity,
        ]);
    }

    /**
     * POST /api/v1/capture/{slug}  (public — no auth)
     * Receives lead submissions from the published landing page form.
     */
    public function capture(Request $request, string $slug): JsonResponse
    {
        $page = LandingPage::where('slug', $slug)->published()->firstOrFail();

        $data = $request->validate([
            'name'    => ['nullable', 'string', 'max:255'],
            'email'   => ['nullable', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'utm_source'   => ['nullable', 'string', 'max:100'],
            'utm_medium'   => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
        ]);

        $lead = Lead::create([
            'organization_id' => $page->project->organization_id,
            'project_id'      => $page->project_id,
            'opportunity_id'  => $page->opportunity_id,
            'name'            => $data['name'] ?? null,
            'email'           => $data['email'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'company'         => $data['company'] ?? null,
            'source'          => $page->slug,
            'status'          => 'new',
            'intent'          => 'unknown',
        ]);

        LeadEvent::create([
            'lead_id'     => $lead->id,
            'type'        => 'lead_captured',
            'metadata'    => [
                'landing_page' => $page->slug,
                'utm_source'   => $data['utm_source'] ?? null,
                'utm_medium'   => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'message'      => $data['message'] ?? null,
            ],
            'occurred_at' => now(),
        ]);

        // Queue AI qualification
        \App\Jobs\QualifyLead::dispatch($lead->id);

        return response()->json([
            'message' => 'Thank you! We will be in touch shortly.',
            'lead_id' => $lead->id,
        ], 201);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function generateSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;
        while (LandingPage::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    private function authorize(Request $request, LandingPage $page): void
    {
        $orgId = $page->project->organization_id;
        if ((int) $orgId !== (int) $request->user()->organization_id) {
            abort(403);
        }
    }
}
