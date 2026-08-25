import { Component, OnInit, signal, computed } from '@angular/core';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DecimalPipe, UpperCasePipe, DatePipe, TitleCasePipe } from '@angular/common';
import { LeadService } from '../../core/services/lead.service';
import { NotificationService } from '../../core/services/notification.service';
import { Lead, LeadScoreLabel, LeadStatus, Paginated } from '../../core/models/api.models';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { Badge } from '../../shared/components/badge/badge';
import { TimeAgoPipe } from '../../shared/pipes/time-ago.pipe';

@Component({
  selector: 'app-leads',
  imports: [FormsModule, ReactiveFormsModule, PageHeader, EmptyState, LoadingSpinner,
            Badge, TimeAgoPipe, DecimalPipe, UpperCasePipe, DatePipe, TitleCasePipe],
  templateUrl: './leads.html',
  styleUrl: './leads.scss',
})
export class Leads implements OnInit {
  loading      = signal(true);
  page         = signal<Paginated<Lead> | null>(null);
  scoreFilter  = signal<LeadScoreLabel | 'all'>('all');
  activeLead   = signal<Lead | null>(null);
  actionPanel  = signal<'qualify' | 'assign' | 'convert' | null>(null);
  actioning    = signal(false);

  items = computed(() => this.page()?.data ?? []);

  qualifyForm: FormGroup;
  assignForm:  FormGroup;
  convertForm: FormGroup;

  constructor(
    private leadService: LeadService,
    private notify: NotificationService,
    private fb: FormBuilder,
  ) {
    this.qualifyForm = this.fb.group({
      qualification_summary: ['', Validators.required],
      lead_score:            [null, [Validators.min(0), Validators.max(100)]],
    });

    this.assignForm = this.fb.group({
      user_id: [null, Validators.required],
    });

    this.convertForm = this.fb.group({
      deal_title: [''],
      deal_value: [null, Validators.min(0)],
    });
  }

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    const score_label = this.scoreFilter() !== 'all' ? (this.scoreFilter() as LeadScoreLabel) : undefined;
    this.leadService.getAll({ score_label }).subscribe({
      next: (p) => { this.page.set(p); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  setFilter(f: string): void {
    this.scoreFilter.set(f as LeadScoreLabel | 'all');
    this.load();
  }

  openLead(lead: Lead): void {
    this.activeLead.set(lead);
    this.actionPanel.set(null);
    // Fetch full lead detail if not already loaded
    if (!lead.events) {
      this.leadService.getOne(lead.id).subscribe({
        next: (full) => this.activeLead.set(full),
      });
    }
  }

  openAction(panel: 'qualify' | 'assign' | 'convert'): void {
    this.actionPanel.set(panel);
    if (panel === 'qualify') {
      this.qualifyForm.reset({
        qualification_summary: this.activeLead()?.qualification_summary ?? '',
        lead_score: this.activeLead()?.lead_score ?? null,
      });
    }
    if (panel === 'convert') {
      this.convertForm.reset({
        deal_title: `Deal from ${this.activeLead()?.name ?? 'Lead'}`,
      });
    }
  }

  qualify(): void {
    if (this.qualifyForm.invalid || this.actioning()) return;
    const lead = this.activeLead();
    if (!lead) return;
    this.actioning.set(true);
    this.leadService.qualify(lead.id, this.qualifyForm.value).subscribe({
      next: (updated) => {
        this.activeLead.set(updated);
        this.updateInList(updated);
        this.notify.success('Lead qualified.');
        this.actionPanel.set(null);
        this.actioning.set(false);
      },
      error: () => this.actioning.set(false),
    });
  }

  assign(): void {
    if (this.assignForm.invalid || this.actioning()) return;
    const lead = this.activeLead();
    if (!lead) return;
    this.actioning.set(true);
    this.leadService.assign(lead.id, this.assignForm.value.user_id).subscribe({
      next: (updated) => {
        this.activeLead.set(updated);
        this.updateInList(updated);
        this.notify.success('Lead assigned.');
        this.actionPanel.set(null);
        this.actioning.set(false);
      },
      error: () => this.actioning.set(false),
    });
  }

  convert(): void {
    if (this.actioning()) return;
    const lead = this.activeLead();
    if (!lead) return;
    this.actioning.set(true);
    const val = this.convertForm.value;
    this.leadService.convert(lead.id, {
      deal_title: val.deal_title || undefined,
      deal_value: val.deal_value || undefined,
    }).subscribe({
      next: (res) => {
        this.activeLead.set(res.lead);
        this.updateInList(res.lead);
        this.notify.success(`Deal "${res.deal.title}" created.`);
        this.actionPanel.set(null);
        this.actioning.set(false);
      },
      error: () => this.actioning.set(false),
    });
  }

  private updateInList(updated: Lead): void {
    this.page.update(p =>
      p ? { ...p, data: p.data.map(l => l.id === updated.id ? { ...l, ...updated } : l) } : p
    );
  }

  scoreBadgeVariant(label: string): 'danger' | 'warning' | 'primary' | 'neutral' {
    const map: Record<string, any> = { hot:'danger', warm:'warning', potential:'primary', low:'neutral' };
    return map[label] ?? 'neutral';
  }

  statusBadgeVariant(status: string): 'success' | 'primary' | 'warning' | 'neutral' {
    const map: Record<string, any> = { won:'success', qualified:'primary', new:'warning', contacted:'primary', lost:'neutral' };
    return map[status] ?? 'neutral';
  }

  eventIcon(type: string): string {
    const map: Record<string, string> = {
      lead_created:'person_add', lead_qualified:'verified', lead_assigned:'assignment_ind',
      lead_converted:'monetization_on', lead_captured:'download',
    };
    return map[type] ?? 'circle';
  }
}
