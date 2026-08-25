import { Component, OnInit, signal, computed } from '@angular/core';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DatePipe } from '@angular/common';
import { LandingPageService } from '../../core/services/landing-page.service';
import { ProjectService } from '../../core/services/project.service';
import { NotificationService } from '../../core/services/notification.service';
import { LandingPage, LandingPageStatus, LandingPageTemplate, Paginated, Project } from '../../core/models/api.models';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { Badge } from '../../shared/components/badge/badge';
import { ConfirmModal } from '../../shared/components/confirm-modal/confirm-modal';

type FilterStatus = 'all' | LandingPageStatus;

const TEMPLATES: { value: LandingPageTemplate; label: string; desc: string; icon: string }[] = [
  { value: 'minimal',   label: 'Minimal',   desc: 'Clean form, headline and CTA.',          icon: 'crop_square' },
  { value: 'hero',      label: 'Hero',       desc: 'Large hero image with form below.',      icon: 'panorama' },
  { value: 'form_only', label: 'Form Only',  desc: 'Pure lead capture form, no distractions.',icon: 'assignment' },
  { value: 'split',     label: 'Split',      desc: 'Two-column: content left, form right.',  icon: 'view_column' },
  { value: 'video',     label: 'Video',      desc: 'Video hero with lead form overlay.',     icon: 'play_circle' },
];

@Component({
  selector: 'app-landing-pages',
  imports: [FormsModule, ReactiveFormsModule, DatePipe, PageHeader, EmptyState,
            LoadingSpinner, Badge, ConfirmModal],
  templateUrl: './landing-pages.html',
  styleUrl: './landing-pages.scss',
})
export class LandingPages implements OnInit {
  loading    = signal(true);
  saving     = signal(false);
  page       = signal<Paginated<LandingPage> | null>(null);
  projects   = signal<Project[]>([]);
  showForm   = signal(false);
  editingId  = signal<number | null>(null);
  deleteId   = signal<number | null>(null);
  statusFilter = signal<FilterStatus>('all');
  formStep   = signal<1 | 2>(1); // step 1: details, step 2: SEO meta

  items = computed(() => this.page()?.data ?? []);
  templates = TEMPLATES;

  form: FormGroup;

  constructor(
    private lpService: LandingPageService,
    private projectService: ProjectService,
    private notify: NotificationService,
    private fb: FormBuilder,
  ) {
    this.form = this.fb.group({
      project_id:      [null, Validators.required],
      title:           ['', [Validators.required, Validators.minLength(3)]],
      slug:            ['', [Validators.pattern(/^[a-z0-9-]+$/)]],
      template:        ['minimal', Validators.required],
      content:         [''],
      meta_description:[''],
      meta_keywords:   [''],
    });
  }

  ngOnInit(): void {
    this.projectService.getAll().subscribe(p => this.projects.set(p));
    this.load();
  }

  load(): void {
    this.loading.set(true);
    const status = this.statusFilter() !== 'all' ? (this.statusFilter() as LandingPageStatus) : undefined;
    this.lpService.getAll({ status }).subscribe({
      next: (p) => { this.page.set(p); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  setFilter(f: string): void { this.statusFilter.set(f as FilterStatus); this.load(); }

  openCreate(): void {
    this.editingId.set(null);
    this.formStep.set(1);
    this.form.reset({ template: 'minimal' });
    this.showForm.set(true);
  }

  openEdit(lp: LandingPage): void {
    this.editingId.set(lp.id);
    this.formStep.set(1);
    this.form.patchValue({
      project_id:       lp.project_id,
      title:            lp.title,
      slug:             lp.slug,
      template:         lp.template,
      content:          lp.content ?? '',
      meta_description: lp.meta?.description ?? '',
      meta_keywords:    lp.meta?.keywords ?? '',
    });
    this.showForm.set(true);
  }

  save(): void {
    if (this.form.invalid || this.saving()) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const val = this.form.value;
    const payload: Partial<LandingPage> = {
      project_id: val.project_id,
      title:      val.title,
      slug:       val.slug || undefined,
      template:   val.template,
      content:    val.content || undefined,
      meta: {
        description: val.meta_description || undefined,
        keywords:    val.meta_keywords    || undefined,
      },
    };

    const id = this.editingId();
    const req = id ? this.lpService.update(id, payload) : this.lpService.create(payload);

    req.subscribe({
      next: (lp) => {
        if (id) {
          this.page.update(p => p ? { ...p, data: p.data.map(x => x.id === id ? lp : x) } : p);
          this.notify.success(`"${lp.title}" updated.`);
        } else {
          this.page.update(p => p ? { ...p, data: [lp, ...p.data] } : p);
          this.notify.success(`"${lp.title}" created.`);
        }
        this.showForm.set(false);
        this.saving.set(false);
      },
      error: () => this.saving.set(false),
    });
  }

  togglePublish(lp: LandingPage): void {
    const req = lp.status === 'published'
      ? this.lpService.unpublish(lp.id)
      : this.lpService.publish(lp.id);

    req.subscribe({
      next: (updated) => {
        this.page.update(p => p ? { ...p, data: p.data.map(x => x.id === lp.id ? updated : x) } : p);
        this.notify.success(updated.status === 'published' ? 'Page published.' : 'Page unpublished.');
      },
    });
  }

  confirmDelete(id: number): void { this.deleteId.set(id); }

  remove(): void {
    const id = this.deleteId();
    if (!id) return;
    this.lpService.remove(id).subscribe({
      next: () => {
        this.page.update(p => p ? { ...p, data: p.data.filter(x => x.id !== id) } : p);
        this.notify.success('Landing page deleted.');
        this.deleteId.set(null);
      },
    });
  }

  captureUrl(slug: string): string {
    return `/api/v1/capture/${slug}`;
  }

  copyUrl(slug: string): void {
    navigator.clipboard.writeText(window.location.origin + this.captureUrl(slug));
    this.notify.success('Capture URL copied to clipboard.');
  }

  statusVariant(s: LandingPageStatus): 'success' | 'warning' | 'neutral' {
    return s === 'published' ? 'success' : s === 'draft' ? 'warning' : 'neutral';
  }

  get titleField()   { return this.form.get('title')!; }
  get projectField() { return this.form.get('project_id')!; }
  get slugField()    { return this.form.get('slug')!; }

  selectedTemplate = computed(() =>
    TEMPLATES.find(t => t.value === this.form.value.template) ?? TEMPLATES[0]
  );
}
