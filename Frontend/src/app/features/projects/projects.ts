import { Component, OnInit, signal, computed } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ProjectService } from '../../core/services/project.service';
import { NotificationService } from '../../core/services/notification.service';
import { Project } from '../../core/models/api.models';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { Badge } from '../../shared/components/badge/badge';
import { ConfirmModal } from '../../shared/components/confirm-modal/confirm-modal';

const INDUSTRIES = [
  'Solar', 'Microfinance', 'Real Estate', 'ISP', 'Insurance',
  'Retail', 'Construction', 'Hotels', 'Healthcare', 'Education', 'Other',
];

const COUNTRIES = [
  { code: 'KE', name: 'Kenya' }, { code: 'UG', name: 'Uganda' },
  { code: 'TZ', name: 'Tanzania' }, { code: 'NG', name: 'Nigeria' },
  { code: 'GH', name: 'Ghana' }, { code: 'ZA', name: 'South Africa' },
];

@Component({
  selector: 'app-projects',
  imports: [RouterLink, FormsModule, ReactiveFormsModule, PageHeader, EmptyState, LoadingSpinner, Badge, ConfirmModal],
  templateUrl: './projects.html',
  styleUrl: './projects.scss',
})
export class Projects implements OnInit {
  loading    = signal(true);
  saving     = signal(false);
  projects   = signal<Project[]>([]);
  showForm   = signal(false);
  editingId  = signal<number | null>(null);
  archiveId  = signal<number | null>(null);

  form: FormGroup;
  industries = INDUSTRIES;
  countries  = COUNTRIES;

  searchQuery = signal('');

  filtered = computed(() => {
    const q = this.searchQuery().toLowerCase();
    return q
      ? this.projects().filter(p => p.name.toLowerCase().includes(q) || p.industry?.toLowerCase().includes(q))
      : this.projects();
  });

  constructor(
    private projectService: ProjectService,
    private notify: NotificationService,
    private fb: FormBuilder,
  ) {
    this.form = this.fb.group({
      name:             ['', [Validators.required, Validators.minLength(2)]],
      industry:         [''],
      country:          ['KE'],
      default_location: [''],
    });
  }

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.projectService.getAll().subscribe({
      next: (p) => { this.projects.set(p); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  openCreate(): void {
    this.form.reset({ country: 'KE', industry: '', name: '', default_location: '' });
    this.editingId.set(null);
    this.showForm.set(true);
  }

  openEdit(p: Project): void {
    this.form.patchValue({
      name:             p.name,
      industry:         p.industry ?? '',
      country:          p.country ?? 'KE',
      default_location: p.default_location ?? '',
    });
    this.editingId.set(p.id);
    this.showForm.set(true);
  }

  save(): void {
    if (this.form.invalid || this.saving()) { this.form.markAllAsTouched(); return; }
    this.saving.set(true);
    const payload = this.form.value;
    const id = this.editingId();

    const req = id
      ? this.projectService.update(id, payload)
      : this.projectService.create(payload);

    req.subscribe({
      next: (p) => {
        if (id) {
          this.projects.update(list => list.map(x => x.id === id ? p : x));
          this.notify.success(`"${p.name}" updated.`);
        } else {
          this.projects.update(list => [p, ...list]);
          this.notify.success(`"${p.name}" created.`);
        }
        this.showForm.set(false);
        this.saving.set(false);
      },
      error: () => this.saving.set(false),
    });
  }

  confirmArchive(id: number): void { this.archiveId.set(id); }

  archive(): void {
    const id = this.archiveId();
    if (!id) return;
    this.projectService.remove(id).subscribe({
      next: () => {
        this.projects.update(list => list.filter(p => p.id !== id));
        this.notify.success('Project archived.');
        this.archiveId.set(null);
      },
    });
  }

  statusVariant(s: string): 'success' | 'warning' | 'neutral' {
    return s === 'active' ? 'success' : s === 'paused' ? 'warning' : 'neutral';
  }

  get name() { return this.form.get('name')!; }
}
