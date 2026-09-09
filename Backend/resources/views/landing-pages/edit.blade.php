@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Landing Page</h1>
            <p class="text-muted">Customize your AI-generated landing page</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('landing-pages.preview', $landingPage) }}" 
               class="btn btn-outline-secondary"
               target="_blank">
                <i class="bi bi-eye"></i> Preview
            </a>
            @if($landingPage->status === 'draft')
                <form action="{{ route('landing-pages.publish', $landingPage) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Publish
                    </button>
                </form>
            @else
                <form action="{{ route('landing-pages.unpublish', $landingPage) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-x-circle"></i> Unpublish
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Editor Form -->
        <div class="col-lg-8">
            <form action="{{ route('landing-pages.update', $landingPage) }}" method="POST" id="pageForm">
                @csrf
                @method('PATCH')

                <!-- Basic Info -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Page Title</label>
                            <input type="text" 
                                   name="title" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   value="{{ old('title', $landingPage->title) }}"
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">URL Slug</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ url('/lp/') }}/</span>
                                <input type="text" 
                                       class="form-control" 
                                       value="{{ $landingPage->slug }}"
                                       disabled>
                            </div>
                            <small class="text-muted">URL slug is set automatically and cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Template</label>
                            <select name="template" class="form-select">
                                <option value="default" {{ $landingPage->template === 'default' ? 'selected' : '' }}>Default</option>
                                <option value="modern" {{ $landingPage->template === 'modern' ? 'selected' : '' }}>Modern</option>
                                <option value="minimal" {{ $landingPage->template === 'minimal' ? 'selected' : '' }}>Minimal</option>
                                <option value="bold" {{ $landingPage->template === 'bold' ? 'selected' : '' }}>Bold</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Hero Section -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Hero Section</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="regenerate('headline')">
                            <i class="bi bi-arrow-clockwise"></i> Regenerate
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Headline</label>
                            <input type="text" 
                                   name="headline" 
                                   class="form-control @error('headline') is-invalid @enderror" 
                                   value="{{ old('headline', $landingPage->headline) }}"
                                   required>
                            @error('headline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subheadline</label>
                            <input type="text" 
                                   name="subheadline" 
                                   class="form-control @error('subheadline') is-invalid @enderror" 
                                   value="{{ old('subheadline', $landingPage->subheadline) }}"
                                   required>
                            @error('subheadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hero Content</label>
                            <textarea name="hero_content" 
                                      rows="4" 
                                      class="form-control @error('hero_content') is-invalid @enderror"
                                      required>{{ old('hero_content', $landingPage->hero_content) }}</textarea>
                            @error('hero_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Call to Action</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">CTA Button Text</label>
                            <input type="text" 
                                   name="cta_text" 
                                   class="form-control @error('cta_text') is-invalid @enderror" 
                                   value="{{ old('cta_text', $landingPage->cta_text) }}"
                                   maxlength="50"
                                   required>
                            @error('cta_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">CTA Subtext (optional)</label>
                            <input type="text" 
                                   name="cta_subtext" 
                                   class="form-control" 
                                   value="{{ old('cta_subtext', $landingPage->cta_subtext) }}"
                                   placeholder="e.g., No credit card required">
                        </div>
                    </div>
                </div>

                <!-- Benefits -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Benefits</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="regenerate('benefits')">
                            <i class="bi bi-arrow-clockwise"></i> Regenerate
                        </button>
                    </div>
                    <div class="card-body">
                        <textarea name="benefits" 
                                  rows="6" 
                                  class="form-control font-monospace"
                                  placeholder='["Benefit 1", "Benefit 2", "Benefit 3"]'>{{ old('benefits', $landingPage->benefits) }}</textarea>
                        <small class="text-muted">JSON array format</small>
                    </div>
                </div>

                <!-- Features -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Features</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="regenerate('features')">
                            <i class="bi bi-arrow-clockwise"></i> Regenerate
                        </button>
                    </div>
                    <div class="card-body">
                        <textarea name="features" 
                                  rows="8" 
                                  class="form-control font-monospace"
                                  placeholder='[{"title": "Feature 1", "description": "..."}]'>{{ old('features', $landingPage->features) }}</textarea>
                        <small class="text-muted">JSON array of objects with title and description</small>
                    </div>
                </div>

                <!-- Social Proof -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Social Proof / Testimonials</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="social_proof" 
                                  rows="8" 
                                  class="form-control font-monospace"
                                  placeholder='[{"name": "John D.", "role": "CEO", "quote": "..."}]'>{{ old('social_proof', $landingPage->social_proof) }}</textarea>
                        <small class="text-muted">JSON array of testimonials</small>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">FAQ Section</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="regenerate('faq')">
                            <i class="bi bi-arrow-clockwise"></i> Regenerate
                        </button>
                    </div>
                    <div class="card-body">
                        <textarea name="faq" 
                                  rows="10" 
                                  class="form-control font-monospace"
                                  placeholder='[{"question": "...", "answer": "..."}]'>{{ old('faq', $landingPage->faq) }}</textarea>
                        <small class="text-muted">JSON array of Q&A objects</small>
                    </div>
                </div>

                <!-- SEO -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">SEO Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Meta Title (50-60 characters)</label>
                            <input type="text" 
                                   name="meta_title" 
                                   class="form-control @error('meta_title') is-invalid @enderror" 
                                   value="{{ old('meta_title', $landingPage->meta_title) }}"
                                   maxlength="60"
                                   required>
                            <small class="text-muted">
                                <span id="metaTitleCount">{{ strlen($landingPage->meta_title) }}</span>/60 characters
                            </small>
                            @error('meta_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Meta Description (150-160 characters)</label>
                            <textarea name="meta_description" 
                                      rows="3" 
                                      class="form-control @error('meta_description') is-invalid @enderror"
                                      maxlength="160"
                                      required>{{ old('meta_description', $landingPage->meta_description) }}</textarea>
                            <small class="text-muted">
                                <span id="metaDescCount">{{ strlen($landingPage->meta_description) }}</span>/160 characters
                            </small>
                            @error('meta_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Advanced -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Advanced Customization</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Custom CSS</label>
                            <textarea name="custom_css" 
                                      rows="6" 
                                      class="form-control font-monospace"
                                      placeholder="/* Your custom CSS */">{{ old('custom_css', $landingPage->custom_css) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Custom JavaScript</label>
                            <textarea name="custom_js" 
                                      rows="6" 
                                      class="form-control font-monospace"
                                      placeholder="// Your custom JS">{{ old('custom_js', $landingPage->custom_js) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-between mb-4">
                    <a href="{{ route('landing-pages.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Status</h6>
                    @if($landingPage->status === 'published')
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle"></i> Published
                            <div class="small mt-1">
                                {{ $landingPage->published_at->diffForHumans() }}
                            </div>
                        </div>
                    @else
                        <div class="alert alert-secondary mb-0">
                            <i class="bi bi-file-earmark"></i> Draft
                        </div>
                    @endif
                </div>
            </div>

            <!-- SEO Score -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">SEO Score</h6>
                    @php
                        $seoClass = $landingPage->seo_score >= 80 ? 'success' : ($landingPage->seo_score >= 60 ? 'warning' : 'danger');
                    @endphp
                    <div class="text-center">
                        <h2 class="display-4 text-{{ $seoClass }}">{{ $landingPage->seo_score }}</h2>
                        <p class="text-muted mb-0">out of 100</p>
                    </div>
                </div>
            </div>

            <!-- Performance -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Performance</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Views:</span>
                        <strong>{{ number_format($landingPage->views) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Conversions:</span>
                        <strong>{{ number_format($landingPage->conversions) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Rate:</span>
                        @php
                            $rate = $landingPage->views > 0 ? round(($landingPage->conversions / $landingPage->views) * 100, 1) : 0;
                        @endphp
                        <strong class="text-{{ $rate >= 5 ? 'success' : 'muted' }}">{{ $rate }}%</strong>
                    </div>
                </div>
            </div>

            <!-- Linked Items -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Linked Items</h6>
                    <div class="mb-2">
                        <small class="text-muted">Keyword:</small><br>
                        <strong>{{ $landingPage->keyword->keyword ?? 'N/A' }}</strong>
                    </div>
                    <div>
                        <small class="text-muted">Opportunity Score:</small><br>
                        <strong>{{ $landingPage->opportunity->opportunity_score ?? 'N/A' }}/100</strong>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <form action="{{ route('landing-pages.duplicate', $landingPage) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-files"></i> Duplicate
                            </button>
                        </form>
                        <a href="{{ route('landing-pages.analytics', $landingPage) }}" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-graph-up"></i> View Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character counters
document.querySelector('[name="meta_title"]').addEventListener('input', function() {
    document.getElementById('metaTitleCount').textContent = this.value.length;
});

document.querySelector('[name="meta_description"]').addEventListener('input', function() {
    document.getElementById('metaDescCount').textContent = this.value.length;
});

// Regenerate section
async function regenerate(section) {
    if (!confirm(`Regenerate ${section} with AI? This will provide new options.`)) return;
    
    // Show loading
    event.target.disabled = true;
    event.target.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating...';
    
    try {
        const response = await fetch(`{{ route('landing-pages.regenerate', $landingPage) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ section })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('New options generated! Check console for details.');
            console.log(data.options);
        } else {
            alert('Failed: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        event.target.disabled = false;
        event.target.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Regenerate';
    }
}
</script>
@endsection
