import { Component, OnInit, signal, computed } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, FormArray, Validators } from '@angular/forms';
import { TitleCasePipe } from '@angular/common';
import { KeywordService } from '../../core/services/keyword.service';
import { ProjectService } from '../../core/services/project.service';
import { NotificationService } from '../../core/services/notification.service';
import { Keyword, KeywordIntent, KeywordPriority, Project } from '../../core/models/api.models';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { Badge } from '../../shared/components/badge/badge';
import { ConfirmModal } from '../../shared/components/confirm-modal/confirm-modal';

type AddMode = 'single' | 'bulk';

@Component({
  selector: 'app-keywords',
  imports: [FormsModule, ReactiveFormsModule, TitleCasePipe, PageHeader, EmptyState, LoadingSpinner, Badge, ConfirmModal],
  templateUrl: './keywords.html',
  styleUrl: './keywords.scss',
})
export class Keywords implements OnInit {
  loading   = signal(true);
  saving    = signal(false);
  keywords  = signal<Keyword[]>([]);
  projects  = signal<Project[]>([]);
  projectId = signal<number | null>(null);
  showForm  = signal(false);
  addMode   = signal<AddMode>('single');
  deleteId  = signal<number | null>(null);
  deleteKw  = signal('');
  searchQ   = signal('');

  singleForm: FormGroup;
  bulkText  = signal('');

  intentOptions: KeywordIntent[]  = ['informational','commercial','transactional','local','unknown'];
  priorityOptions: KeywordPriority[] = ['low','medium','high'];

  filtered = computed(() => {
    const q = this.searchQ().toLowerCase();
    return q
      ? this.keywords().filter(k => k.keyword.toLowerCase().includes(q) || k.category?.toLowerCase().includes(q))
      : this.keywords();
  });

  intentCounts = computed(() => {
    const counts: Record<string, number> = {};
    this.keywords().forEach(k => counts[k.intent] = (counts[k.intent] ?? 0) + 1);
    return counts;
  });

  constructor(
    private keywordService: KeywordService,
    private projectService: ProjectService,
    private notify: NotificationService,
    private route: ActivatedRoute,
    private fb: FormBuilder,
  ) {
    this.singleForm = this.fb.group({
      keyword:   ['', [Validators.required, Validators.minLength(2)]],
      category:  [''],
      intent:    ['unknown'],
      priority:  ['medium'],
      locations: this.fb.array([this.makeLocation()]),
    });
  }

  ngOnInit(): void {
    // Load projects for switcher
    this.projectService.getAll().subscribe(p => this.projects.set(p));

    const pid = this.route.snapshot.queryParamMap.get('project_id');
    if (pid) {
      this.projectId.set(+pid);
      this.load(+pid);
    } else {
      this.loading.set(false);
    }
  }

  load(pid: number): void {
    this.loading.set(true);
    this.keywordService.getAll(pid).subscribe({
      next: (k) => { this.keywords.set(k); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  switchProject(id: number): void {
    this.projectId.set(id);
    this.load(id);
  }

  // ── Locations FormArray helpers ──────────────────────────────────────────

  makeLocation(): FormGroup {
    return this.fb.group({
      country: ['KE', Validators.required],
      region:  [''],
      city:    [''],
    });
  }

  get locationsArray(): FormArray {
    return this.singleForm.get('locations') as FormArray;
  }

  addLocation(): void { this.locationsArray.push(this.makeLocation()); }

  removeLocation(i: number): void {
    if (this.locationsArray.length > 1) this.locationsArray.removeAt(i);
  }

  // ── Create / Bulk ────────────────────────────────────────────────────────

  openAdd(): void {
    this.singleForm.reset({ intent: 'unknown', priority: 'medium', category: '' });
    this.locationsArray.clear();
    this.locationsArray.push(this.makeLocation());
    this.bulkText.set('');
    this.showForm.set(true);
  }

  saveSingle(): void {
    if (this.singleForm.invalid || this.saving()) { this.singleForm.markAllAsTouched(); return; }
    const pid = this.projectId();
    if (!pid) return;

    this.saving.set(true);
    const val = this.singleForm.value;
    const payload = {
      project_id: pid,
      keyword:    val.keyword,
      category:   val.category || undefined,
      intent:     val.intent,
      priority:   val.priority,
      locations:  val.locations.filter((l: any) => l.country),
    };

    this.keywordService.create(payload).subscribe({
      next: (k) => {
        this.keywords.update(list => [k, ...list]);
        this.singleForm.reset({ intent: 'unknown', priority: 'medium', category: '' });
        this.locationsArray.clear();
        this.locationsArray.push(this.makeLocation());
        this.notify.success(`"${k.keyword}" added.`);
        this.saving.set(false);
      },
      error: () => this.saving.set(false),
    });
  }

  saveBulk(): void {
    const pid = this.projectId();
    if (!pid) return;

    const lines = this.bulkText()
      .split('\n')
      .map(l => l.trim())
      .filter(l => l.length > 1);

    if (!lines.length) { this.notify.warning('Enter at least one keyword.'); return; }
    if (lines.length > 100) { this.notify.error('Maximum 100 keywords at a time.'); return; }

    this.saving.set(true);
    this.keywordService.bulkCreate(pid, lines).subscribe({
      next: (res) => {
        this.load(pid);
        this.bulkText.set('');
        this.showForm.set(false);
        this.notify.success(`${res.created} keyword${res.created === 1 ? '' : 's'} added.`);
        this.saving.set(false);
      },
      error: () => this.saving.set(false),
    });
  }

  // ── Delete ───────────────────────────────────────────────────────────────

  confirmDelete(id: number, keyword: string): void {
    this.deleteId.set(id);
    this.deleteKw.set(keyword);
  }

  remove(): void {
    const id = this.deleteId();
    if (!id) return;
    this.keywordService.remove(id).subscribe({
      next: () => {
        this.keywords.update(list => list.filter(k => k.id !== id));
        this.notify.success(`"${this.deleteKw()}" removed.`);
        this.deleteId.set(null);
      },
    });
  }

  // ── Update status / priority ─────────────────────────────────────────────

  toggleStatus(k: Keyword): void {
    const next = k.status === 'active' ? 'paused' : 'active';
    this.keywordService.update(k.id, { status: next }).subscribe({
      next: (updated) => this.keywords.update(list => list.map(x => x.id === k.id ? updated : x)),
    });
  }

  // ── Badge helpers ────────────────────────────────────────────────────────

  intentVariant(intent: string): 'success' | 'primary' | 'warning' | 'danger' | 'neutral' {
    const map: Record<string, any> = {
      transactional: 'success', commercial: 'primary',
      local: 'warning', informational: 'neutral', unknown: 'neutral',
    };
    return map[intent] ?? 'neutral';
  }

  priorityVariant(p: string): 'danger' | 'warning' | 'neutral' {
    return p === 'high' ? 'danger' : p === 'medium' ? 'warning' : 'neutral';
  }

  get kwField() { return this.singleForm.get('keyword')!; }

  currentProject = computed(() => this.projects().find(p => p.id === this.projectId()));
}
