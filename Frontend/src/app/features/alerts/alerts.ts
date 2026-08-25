import { Component, OnInit, signal, computed } from '@angular/core';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DecimalPipe } from '@angular/common';
import { AlertService } from '../../core/services/alert.service';
import { NotificationService } from '../../core/services/notification.service';
import { Alert, AlertRule, AlertChannel, Paginated } from '../../core/models/api.models';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { Badge } from '../../shared/components/badge/badge';
import { ConfirmModal } from '../../shared/components/confirm-modal/confirm-modal';
import { TimeAgoPipe } from '../../shared/pipes/time-ago.pipe';

type Tab = 'history' | 'rules';

@Component({
  selector: 'app-alerts',
  imports: [FormsModule, ReactiveFormsModule, DecimalPipe, PageHeader, LoadingSpinner, EmptyState,
            Badge, ConfirmModal, TimeAgoPipe],
  templateUrl: './alerts.html',
  styleUrl: './alerts.scss',
})
export class AlertsPage implements OnInit {
  tab         = signal<Tab>('history');
  loading     = signal(true);
  savingRule  = signal(false);
  deletingId  = signal<number | null>(null);
  showRuleForm= signal(false);
  editingRule = signal<AlertRule | null>(null);

  alertPage   = signal<Paginated<Alert> | null>(null);
  rules       = signal<AlertRule[]>([]);

  unreadCount = computed(() =>
    this.alertPage()?.data.filter(a => a.status !== 'read').length ?? 0
  );

  ruleForm: FormGroup;

  readonly channels: { value: AlertChannel; label: string; icon: string }[] = [
    { value: 'dashboard', label: 'Dashboard',  icon: 'dashboard' },
    { value: 'email',     label: 'Email',       icon: 'mail' },
    { value: 'sms',       label: 'SMS',         icon: 'sms' },
    { value: 'whatsapp',  label: 'WhatsApp',    icon: 'chat' },
    { value: 'webhook',   label: 'Webhook',     icon: 'webhook' },
  ];

  readonly intents = [
    { value: 'any',           label: 'Any' },
    { value: 'transactional', label: 'Transactional' },
    { value: 'commercial',    label: 'Commercial' },
    { value: 'local',         label: 'Local' },
  ];

  constructor(
    private alertService: AlertService,
    private notify: NotificationService,
    private fb: FormBuilder,
  ) {
    this.ruleForm = this.fb.group({
      name:           ['', [Validators.required, Validators.minLength(2)]],
      minimum_score:  [60, [Validators.min(0), Validators.max(100)]],
      minimum_growth: [20, [Validators.min(0)]],
      intent:         ['any'],
      cooldown:       [24, [Validators.min(1)]],
      channels:       [['dashboard']],
      recipients:     [''],
    });
  }

  ngOnInit(): void { this.loadHistory(); this.loadRules(); }

  // ── History ──────────────────────────────────────────────────────────────

  loadHistory(): void {
    this.loading.set(true);
    this.alertService.getAlerts().subscribe({
      next: (p) => { this.alertPage.set(p); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  markRead(alert: Alert): void {
    if (alert.status === 'read') return;
    this.alertService.markRead(alert.id).subscribe({
      next: (updated) => this.alertPage.update(p =>
        p ? { ...p, data: p.data.map(a => a.id === alert.id ? updated : a) } : p
      ),
    });
  }

  markAllRead(): void {
    this.alertService.markAllRead().subscribe({
      next: () => {
        this.alertPage.update(p =>
          p ? { ...p, data: p.data.map(a => ({ ...a, status: 'read' as const })) } : p
        );
        this.notify.success('All alerts marked as read.');
      },
    });
  }

  // ── Rules ────────────────────────────────────────────────────────────────

  loadRules(): void {
    this.alertService.getRules().subscribe({
      next: (r) => this.rules.set(r),
    });
  }

  openCreateRule(): void {
    this.editingRule.set(null);
    this.ruleForm.reset({
      name: '', minimum_score: 60, minimum_growth: 20,
      intent: 'any', cooldown: 24, channels: ['dashboard'], recipients: '',
    });
    this.showRuleForm.set(true);
  }

  openEditRule(rule: AlertRule): void {
    this.editingRule.set(rule);
    this.ruleForm.patchValue({
      name:           rule.name,
      minimum_score:  rule.minimum_score,
      minimum_growth: rule.minimum_growth,
      intent:         rule.intent,
      cooldown:       rule.cooldown,
      channels:       rule.channels ?? ['dashboard'],
      recipients:     (rule.recipients ?? []).join(', '),
    });
    this.showRuleForm.set(true);
  }

  saveRule(): void {
    if (this.ruleForm.invalid || this.savingRule()) {
      this.ruleForm.markAllAsTouched();
      return;
    }
    this.savingRule.set(true);
    const val = this.ruleForm.value;
    const payload = {
      name:           val.name,
      minimum_score:  val.minimum_score,
      minimum_growth: val.minimum_growth,
      intent:         val.intent,
      cooldown:       val.cooldown,
      channels:       val.channels,
      recipients:     val.recipients
        ? val.recipients.split(',').map((r: string) => r.trim()).filter(Boolean)
        : [],
    };

    const id = this.editingRule()?.id;
    const req = id
      ? this.alertService.updateRule(id, payload)
      : this.alertService.createRule(payload);

    req.subscribe({
      next: (rule) => {
        if (id) {
          this.rules.update(rs => rs.map(r => r.id === id ? rule : r));
          this.notify.success(`Rule "${rule.name}" updated.`);
        } else {
          this.rules.update(rs => [rule, ...rs]);
          this.notify.success(`Rule "${rule.name}" created.`);
        }
        this.showRuleForm.set(false);
        this.savingRule.set(false);
      },
      error: () => this.savingRule.set(false),
    });
  }

  confirmDelete(id: number): void { this.deletingId.set(id); }

  deleteRule(): void {
    const id = this.deletingId();
    if (!id) return;
    this.alertService.deleteRule(id).subscribe({
      next: () => {
        this.rules.update(rs => rs.filter(r => r.id !== id));
        this.notify.success('Rule deleted.');
        this.deletingId.set(null);
      },
    });
  }

  toggleRuleStatus(rule: AlertRule): void {
    const next = rule.status === 'active' ? 'paused' : 'active';
    this.alertService.updateRule(rule.id, { status: next }).subscribe({
      next: (updated) => this.rules.update(rs => rs.map(r => r.id === rule.id ? updated : r)),
    });
  }

  toggleChannel(channel: AlertChannel): void {
    const current: AlertChannel[] = this.ruleForm.value.channels ?? [];
    const updated = current.includes(channel)
      ? current.filter(c => c !== channel)
      : [...current, channel];
    this.ruleForm.patchValue({ channels: updated });
  }

  isChannelSelected(channel: AlertChannel): boolean {
    return (this.ruleForm.value.channels ?? []).includes(channel);
  }

  // ── Display helpers ───────────────────────────────────────────────────────

  channelIcon(ch: string): string {
    const map: Record<string, string> = {
      sms: 'sms', email: 'mail', whatsapp: 'chat', push: 'notifications',
      dashboard: 'dashboard', webhook: 'webhook',
    };
    return map[ch] ?? 'notifications';
  }

  statusVariant(s: string): 'success' | 'warning' | 'danger' | 'neutral' {
    const map: Record<string, any> = {
      sent: 'success', pending: 'warning', failed: 'danger', read: 'neutral',
    };
    return map[s] ?? 'neutral';
  }

  get nameField() { return this.ruleForm.get('name')!; }
}
