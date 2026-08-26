import { Component, OnInit, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Title, Meta } from '@angular/platform-browser';
import { DecimalPipe } from '@angular/common';

interface CapturePageData {
  slug:        string;
  title:       string;
  content?:    string;
  template:    string;
  meta?:       { description?: string; keywords?: string; og_image?: string };
  project?:    { id: number; name: string; country: string };
  opportunity?:{ id: number; opportunity_score: number; trend_state: string };
}

@Component({
  selector: 'app-capture-page',
  imports: [FormsModule, ReactiveFormsModule, DecimalPipe],
  templateUrl: './capture-page.html',
  styleUrl: './capture-page.scss',
})
export class CapturePage implements OnInit {
  loading   = signal(true);
  submitting = signal(false);
  submitted  = signal(false);
  error      = signal<string | null>(null);
  page       = signal<CapturePageData | null>(null);

  form: FormGroup;

  constructor(
    private route:  ActivatedRoute,
    private http:   HttpClient,
    private fb:     FormBuilder,
    private title:  Title,
    private meta:   Meta,
  ) {
    this.form = this.fb.group({
      name:    [''],
      email:   ['', Validators.email],
      phone:   [''],
      company: [''],
      message: [''],
    });
  }

  ngOnInit(): void {
    const slug = this.route.snapshot.paramMap.get('slug') ?? '';

    // Pass UTM params from query string through to form
    const params = this.route.snapshot.queryParams;
    this['utm'] = {
      utm_source:   params['utm_source']   ?? null,
      utm_medium:   params['utm_medium']   ?? null,
      utm_campaign: params['utm_campaign'] ?? null,
    };

    this.http.get<CapturePageData>(`/api/v1/capture/${slug}`).subscribe({
      next: (data) => {
        this.page.set(data);
        this.loading.set(false);

        // Set page title + SEO meta
        this.title.setTitle(data.title);
        if (data.meta?.description) {
          this.meta.updateTag({ name: 'description', content: data.meta.description });
        }
        if (data.meta?.keywords) {
          this.meta.updateTag({ name: 'keywords', content: data.meta.keywords });
        }
        if (data.meta?.og_image) {
          this.meta.updateTag({ property: 'og:image', content: data.meta.og_image });
        }
        this.meta.updateTag({ property: 'og:title', content: data.title });
      },
      error: (err) => {
        this.loading.set(false);
        this.error.set(
          err.status === 404
            ? 'This page is not available.'
            : 'Failed to load page. Please try again.'
        );
      },
    });
  }

  private utm: Record<string, string | null> = {};

  submit(): void {
    if (this.submitting()) return;

    // Require at least one contact field
    const val = this.form.value;
    if (!val.name && !val.email && !val.phone) {
      this.form.get('name')!.setErrors({ required: true });
      this.form.get('name')!.markAsTouched();
      return;
    }

    const slug = this.page()?.slug;
    if (!slug) return;

    this.submitting.set(true);

    this.http.post(`/api/v1/capture/${slug}`, { ...val, ...this.utm }).subscribe({
      next: () => {
        this.submitted.set(true);
        this.submitting.set(false);
      },
      error: (err) => {
        const msg = err.error?.message ?? 'Submission failed. Please try again.';
        this.error.set(msg);
        this.submitting.set(false);
      },
    });
  }
}
