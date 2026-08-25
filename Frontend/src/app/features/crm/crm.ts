import { Component, OnInit, signal, computed } from '@angular/core';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { TitleCasePipe, DatePipe } from '@angular/common';
import { CrmService } from '../../core/services/crm.service';
import { NotificationService } from '../../core/services/notification.service';
import { Deal, DealStage, Task, Note, Paginated } from '../../core/models/api.models';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { Badge } from '../../shared/components/badge/badge';
import { CurrencyKePipe } from '../../shared/pipes/currency-ke.pipe';
import { TimeAgoPipe } from '../../shared/pipes/time-ago.pipe';
import { ConfirmModal } from '../../shared/components/confirm-modal/confirm-modal';

const STAGES: DealStage[] = ['new','contacted','qualified','quotation','negotiation','won','lost'];

@Component({
  selector: 'app-crm',
  imports: [FormsModule, ReactiveFormsModule, TitleCasePipe, DatePipe,
            PageHeader, LoadingSpinner, EmptyState, Badge, CurrencyKePipe, TimeAgoPipe, ConfirmModal],
  templateUrl: './crm.html',
  styleUrl: './crm.scss',
})
export class Crm implements OnInit {
  loading   = signal(true);
  view      = signal<'pipeline' | 'list'>('pipeline');
  page      = signal<Paginated<Deal> | null>(null);
  stages    = STAGES;

  // Deal drawer
  activeDeal     = signal<Deal | null>(null);
  drawerTab      = signal<'details' | 'tasks' | 'notes'>('details');
  dealTasks      = signal<Task[]>([]);
  dealNotes      = signal<Note[]>([]);
  savingDeal     = signal(false);
  savingTask     = signal(false);
  savingNote     = signal(false);
  deleteDealId   = signal<number | null>(null);
  dragOverStage  = signal<DealStage | null>(null);
  dragDealId     = signal<number | null>(null);

  // Forms
  dealForm: FormGroup;
  taskForm: FormGroup;
  noteBody = signal('');
  showDealForm = signal(false);

  deals = computed(() => this.page()?.data ?? []);

  dealsByStage = computed(() => {
    const grouped = Object.fromEntries(STAGES.map(s => [s, [] as Deal[]]));
    this.deals().forEach(d => (grouped[d.stage] ??= []).push(d));
    return grouped as Record<DealStage, Deal[]>;
  });

  stageRevenue = (stage: DealStage) =>
    this.dealsByStage()[stage].reduce((s, d) => s + (d.value ?? 0), 0);

  constructor(
    private crm: CrmService,
    private notify: NotificationService,
    private fb: FormBuilder,
  ) {
    this.dealForm = this.fb.group({
      title:             ['', [Validators.required, Validators.minLength(2)]],
      value:             [null],
      stage:             ['new'],
      expected_close_at: [''],
    });

    this.taskForm = this.fb.group({
      title: ['', Validators.required],
      type:  ['call'],
      due_at:[''],
    });
  }

  ngOnInit(): void { this.loadDeals(); }

  loadDeals(): void {
    this.loading.set(true);
    this.crm.getDeals().subscribe({
      next: (p) => { this.page.set(p); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  // ── Deal form ──────────────────────────────────────────────────────────────

  openCreateDeal(): void {
    this.dealForm.reset({ stage: 'new' });
    this.showDealForm.set(true);
  }

  saveDeal(): void {
    if (this.dealForm.invalid || this.savingDeal()) { this.dealForm.markAllAsTouched(); return; }
    this.savingDeal.set(true);
    const val = this.dealForm.value;
    this.crm.createDeal({ ...val, value: val.value || undefined }).subscribe({
      next: (d) => {
        this.page.update(p => p ? { ...p, data: [d, ...p.data] } : p);
        this.notify.success(`Deal "${d.title}" created.`);
        this.showDealForm.set(false);
        this.savingDeal.set(false);
      },
      error: () => this.savingDeal.set(false),
    });
  }

  // ── Kanban drag ────────────────────────────────────────────────────────────

  onDragStart(dealId: number): void { this.dragDealId.set(dealId); }

  onDragOver(stage: DealStage, event: DragEvent): void {
    event.preventDefault();
    this.dragOverStage.set(stage);
  }

  onDragLeave(): void { this.dragOverStage.set(null); }

  onDrop(stage: DealStage, event: DragEvent): void {
    event.preventDefault();
    this.dragOverStage.set(null);
    const dealId = this.dragDealId();
    if (!dealId) return;
    const deal = this.deals().find(d => d.id === dealId);
    if (!deal || deal.stage === stage) return;

    // Optimistic update
    this.page.update(p => p
      ? { ...p, data: p.data.map(d => d.id === dealId ? { ...d, stage } : d) }
      : p);

    this.crm.updateDeal(dealId, { stage }).subscribe({
      error: () => {
        // Rollback on failure
        this.page.update(p => p
          ? { ...p, data: p.data.map(d => d.id === dealId ? { ...d, stage: deal.stage } : d) }
          : p);
        this.notify.error('Failed to move deal.');
      },
    });

    this.dragDealId.set(null);
  }

  // ── Deal drawer ────────────────────────────────────────────────────────────

  openDeal(deal: Deal): void {
    this.activeDeal.set(deal);
    this.drawerTab.set('details');
    this.dealTasks.set([]);
    this.dealNotes.set([]);
    this.loadDealTasks(deal.id);
    this.loadDealNotes(deal.id);
  }

  loadDealTasks(dealId: number): void {
    this.crm.getTasks({ deal_id: dealId }).subscribe({
      next: (p) => this.dealTasks.set(p.data),
    });
  }

  loadDealNotes(dealId: number): void {
    this.crm.getNotes({ deal_id: dealId }).subscribe({
      next: (p) => this.dealNotes.set(p.data),
    });
  }

  updateDealStage(stage: DealStage): void {
    const deal = this.activeDeal();
    if (!deal) return;
    const status = stage === 'won' ? 'won' : stage === 'lost' ? 'lost' : 'open';
    this.crm.updateDeal(deal.id, { stage, status }).subscribe({
      next: (updated) => {
        this.activeDeal.set(updated);
        this.page.update(p => p
          ? { ...p, data: p.data.map(d => d.id === updated.id ? updated : d) }
          : p);
        this.notify.success(`Moved to ${stage}.`);
      },
    });
  }

  closeDeal(outcome: 'won' | 'lost'): void {
    this.updateDealStage(outcome);
  }

  // ── Tasks ──────────────────────────────────────────────────────────────────

  addTask(): void {
    if (this.taskForm.invalid || this.savingTask()) return;
    const deal = this.activeDeal();
    if (!deal) return;
    this.savingTask.set(true);
    const val = this.taskForm.value;
    this.crm.createTask({ deal_id: deal.id, ...val, due_at: val.due_at || undefined }).subscribe({
      next: (t) => {
        this.dealTasks.update(ts => [t, ...ts]);
        this.taskForm.reset({ type: 'call' });
        this.savingTask.set(false);
      },
      error: () => this.savingTask.set(false),
    });
  }

  completeTask(task: Task): void {
    this.crm.updateTask(task.id, { status: 'completed' }).subscribe({
      next: (updated) => this.dealTasks.update(ts => ts.map(t => t.id === task.id ? updated : t)),
    });
  }

  deleteTask(id: number): void {
    this.crm.deleteTask(id).subscribe({
      next: () => this.dealTasks.update(ts => ts.filter(t => t.id !== id)),
    });
  }

  // ── Notes ──────────────────────────────────────────────────────────────────

  addNote(): void {
    const body = this.noteBody().trim();
    const deal = this.activeDeal();
    if (!body || !deal || this.savingNote()) return;
    this.savingNote.set(true);
    this.crm.createNote({ deal_id: deal.id, body }).subscribe({
      next: (n) => {
        this.dealNotes.update(ns => [n, ...ns]);
        this.noteBody.set('');
        this.savingNote.set(false);
      },
      error: () => this.savingNote.set(false),
    });
  }

  deleteNote(id: number): void {
    this.crm.deleteNote(id).subscribe({
      next: () => this.dealNotes.update(ns => ns.filter(n => n.id !== id)),
    });
  }

  // ── Helpers ────────────────────────────────────────────────────────────────

  confirmDeleteDeal(id: number): void { this.deleteDealId.set(id); }

  removeDeal(): void {
    const id = this.deleteDealId();
    if (!id) return;
    this.crm.deleteDeal(id).subscribe({
      next: () => {
        this.page.update(p => p ? { ...p, data: p.data.filter(d => d.id !== id) } : p);
        this.notify.success('Deal deleted.');
        this.deleteDealId.set(null);
        if (this.activeDeal()?.id === id) this.activeDeal.set(null);
      },
    });
  }

  stageVariant(stage: string): 'success' | 'primary' | 'warning' | 'danger' | 'neutral' {
    const map: Record<string, any> = {
      won: 'success', qualified: 'primary', quotation: 'warning',
      negotiation: 'primary', lost: 'danger',
    };
    return map[stage] ?? 'neutral';
  }

  setDrawerTab(key: string): void {
    this.drawerTab.set(key as 'details' | 'tasks' | 'notes');
  }

  taskTypeIcon(type: string): string {    const map: Record<string, string> = {
      call: 'call', email: 'mail', meeting: 'groups',
      follow_up: 'reply', other: 'task_alt',
    };
    return map[type] ?? 'task_alt';
  }
}
