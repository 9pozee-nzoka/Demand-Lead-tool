<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $landingPage->title }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        .preview-banner {
            background: #ffc107;
            color: #000;
            padding: 15px 0;
            text-align: center;
            font-weight: 600;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        body {
            padding-top: 60px;
        }
        
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-section .lead {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .cta-button {
            font-size: 1.25rem;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .section {
            padding: 80px 0;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            text-align: center;
        }
        
        .benefit-card, .feature-card {
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .benefit-card:hover, .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .testimonial-card {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .form-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        
        .lead-form {
            background: white;
            border-radius: 10px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .lead-form label {
            color: #333;
            font-weight: 600;
        }
        
        .lead-form .form-control {
            padding: 12px;
            border-radius: 8px;
        }
        
        .faq-item {
            margin-bottom: 1.5rem;
        }
        
        .faq-question {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        footer {
            background: #343a40;
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            .hero-section .lead {
                font-size: 1.1rem;
            }
        }
        
        {{ $landingPage->custom_css ?? '' }}
    </style>
</head>
<body>
    <!-- Preview Banner -->
    <div class="preview-banner">
        <i class="bi bi-eye"></i> PREVIEW MODE - This page is not yet published
        <a href="{{ route('landing-pages.edit', $landingPage) }}" class="btn btn-sm btn-dark ms-3">
            <i class="bi bi-pencil"></i> Edit Page
        </a>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>{{ $landingPage->headline }}</h1>
            <p class="lead">{{ $landingPage->subheadline }}</p>
            <p class="mb-4">{{ $landingPage->hero_content }}</p>
            <a href="#contact-form" class="btn btn-light btn-lg cta-button">
                {{ $landingPage->cta_text }}
            </a>
            @if($landingPage->cta_subtext)
                <p class="mt-3 small" style="opacity: 0.8;">{{ $landingPage->cta_subtext }}</p>
            @endif
        </div>
    </section>

    <!-- Benefits Section -->
    @if($landingPage->benefits)
        @php
            $benefits = json_decode($landingPage->benefits, true);
        @endphp
        @if(is_array($benefits) && count($benefits) > 0)
            <section class="section">
                <div class="container">
                    <h2 class="section-title">Why Choose Us?</h2>
                    <div class="row g-4">
                        @foreach($benefits as $benefit)
                            <div class="col-md-4">
                                <div class="card benefit-card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <i class="bi bi-check-circle-fill text-success fs-1 mb-3"></i>
                                        <p class="mb-0">{{ $benefit }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endif

    <!-- Features Section -->
    @if($landingPage->features)
        @php
            $features = json_decode($landingPage->features, true);
        @endphp
        @if(is_array($features) && count($features) > 0)
            <section class="section bg-light">
                <div class="container">
                    <h2 class="section-title">Key Features</h2>
                    <div class="row g-4">
                        @foreach($features as $feature)
                            <div class="col-md-6">
                                <div class="card feature-card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <h5 class="card-title text-primary mb-3">
                                            <i class="bi bi-star-fill me-2"></i>
                                            {{ $feature['title'] ?? '' }}
                                        </h5>
                                        <p class="card-text">{{ $feature['description'] ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endif

    <!-- Social Proof Section -->
    @if($landingPage->social_proof)
        @php
            $testimonials = json_decode($landingPage->social_proof, true);
        @endphp
        @if(is_array($testimonials) && count($testimonials) > 0)
            <section class="section">
                <div class="container">
                    <h2 class="section-title">What Our Customers Say</h2>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            @foreach($testimonials as $testimonial)
                                <div class="testimonial-card">
                                    <p class="mb-3 fst-italic">"{{ $testimonial['quote'] ?? '' }}"</p>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 50px; height: 50px;">
                                            <strong>{{ substr($testimonial['name'] ?? 'A', 0, 1) }}</strong>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $testimonial['name'] ?? 'Anonymous' }}</div>
                                            <div class="small text-muted">{{ $testimonial['role'] ?? '' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endif

    <!-- Lead Capture Form -->
    <section class="form-section" id="contact-form">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="lead-form">
                        <h3 class="text-center mb-4 text-dark">{{ $landingPage->cta_text }}</h3>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i> Form is disabled in preview mode
                        </div>

                        <form id="leadForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>

                            <div class="mb-3">
                                <label for="company" class="form-label">Company</label>
                                <input type="text" class="form-control" id="company" name="company">
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="4"></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg cta-button">
                                    {{ $landingPage->cta_text }}
                                </button>
                            </div>

                            @if($landingPage->cta_subtext)
                                <p class="text-center text-muted mt-3 small">{{ $landingPage->cta_subtext }}</p>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    @if($landingPage->faq)
        @php
            $faqs = json_decode($landingPage->faq, true);
        @endphp
        @if(is_array($faqs) && count($faqs) > 0)
            <section class="section">
                <div class="container">
                    <h2 class="section-title">Frequently Asked Questions</h2>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            @foreach($faqs as $faq)
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <i class="bi bi-question-circle me-2"></i>
                                        {{ $faq['question'] ?? '' }}
                                    </div>
                                    <p class="ms-4">{{ $faq['answer'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endif

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @if($landingPage->custom_js)
        <script>
            {!! $landingPage->custom_js !!}
        </script>
    @endif

    <script>
        // Disable form submission in preview
        document.getElementById('leadForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Form submission is disabled in preview mode. Publish the page to enable lead capture.');
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
