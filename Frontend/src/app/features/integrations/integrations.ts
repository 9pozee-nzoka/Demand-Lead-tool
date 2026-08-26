import { Component, OnInit, signal } from '@angular/core';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { TitleCasePipe } from '@angular/common';
import { ApiService } from '../../core/services/api.service';
import { NotificationService } from '../../core/services/notification.service';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { Badge } from '../../shared/components/badge/badge';
import { ConfirmModal } from '../../shared/components/confirm-modal/confirm-modal';
import { TimeAgoPipe } from '../../shared/pipes/time-ago.pipe';

interface DataSource {
  id: number;
  name: string;
  type: string;
  status: string;
  last_sync_at?: string;
  sync_meta?: Record<string, any>;
  has_credentials: boolean;
}

interface ProviderDef {
  type: string;
  label: string;
  icon: string;
  desc: string;
}

@Component({
  selector: 'app-integrations',
  imports: [FormsModule, ReactiveFormsModule, TitleCasePipe, PageHeader, LoadingSpinner, Badge, ConfirmModal, TimeAgoPipe],
  templateUrl: './integrations.html',
  styleUrl: './integrations.scss',
})
export class Integrations implements OnInit {
  loading     = signal(true);
  sources     = signal<DataSource[]>([]);
  available   = signal<ProviderDef[]>([]);
  testing     = signal<number | null>(null);
  deleteId    = signal<number | null>(null);
  showForm    = signal(false);
  credFields  = signal<{ key: string; label: string; type: string }[]>([]);

  form: FormGroup;

  private readonly credSchema: Record<string, { key: string; label: string; type: string }[]> = {
    google_ads:      [{ key: 'client_id', label: 'Client ID', type: 'text' }, { key: 'client_secret', label: 'Client Secret', type: 'password' }, { key: 'refresh_token', label: 'Refresh Token', type: 'password' }],
    search_console:  [{ key: 'client_email', label: 'Service Account Email', type: 'text' }, { key: 'private_key', label: 'Private Key', type: 'textarea' }],
    africas_talking: [{ key: 'api_key', label: 'API Key', type: 'password' }, { key: 'username', label: 'Username', type: 'text' }, { key: 'sender_id', label: 'Sender ID', type: 'text' }],
    webhook:         [{ key: 'url', label: 'Webhook URL', type: 'text' }, { key: 'secret', label: 'Signing Secret (optional)', type: 'password' }],
    google_trends:   [],
  };

  constructor(
    private api: ApiService,
    private notify: NotificationService,
    private fb: FormBuilder,
  ) {
    this.form = this.fb.group({
      name:  ['', Validators.required],
      type:  ['', Validators.required],
      creds: this.fb.group({}),
    });
  }

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.api['get']<{ data: DataSource[]; available: ProviderDef[] }>('/api/v1/integrations').subscribe({
      next: (r) => { this.sources.set(r.data); this.available.set(r.available); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  openAdd(provider: ProviderDef): void {
    this.form.reset();
    this.form.patchValue({ type: provider.type, name: provider.label });
    const fields = this.credSchema[provider.type] ?? [];
    this.credFields.set(fields);

    // Rebuild creds group with correct keys
    const credsGroup = this.fb.group({});
    fields.forEach(f => credsGroup.addControl(f.key, this.fb.control('')));
    this.form.setControl('creds', credsGroup);
    this.showForm.set(true);
  }

  connect(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    const val = this.form.value;
    const payload: any = { name: val.name, type: val.type };

    const creds = val.creds ?? {};
    const hasCredValues = Object.values(creds).some(v => !!v);
    if (hasCredValues) payload.credentials = creds;

    this.api['post']<DataSource>('/api/v1/integrations', payload).subscribe({
      next: (s) => {
        this.sources.update(list => [s, ...list]);
        this.notify.success(`${s.name} connected.`);
        this.showForm.set(false);
      },
    });
  }

  testConnection(source: DataSource): void {
    this.testing.set(source.id);
    this.api['post']<{ ok: boolean; message: string }>(`/api/v1/integrations/${source.id}/test`, {}).subscribe({
      next: (r) => {
        if (r.ok) {
          this.notify.success(r.message);
          this.sources.update(list => list.map(s => s.id === source.id ? { ...s, status: 'active' } : s));
        } else {
          this.notify.error(r.message);
          this.sources.update(list => list.map(s => s.id === source.id ? { ...s, status: 'error' } : s));
        }
        this.testing.set(null);
      },
      error: (err) => {
        this.notify.error(err.error?.message ?? 'Connection test failed.');
        this.testing.set(null);
      },
    });
  }

  toggleStatus(source: DataSource): void {
    const next = source.status === 'active' ? 'paused' : 'active';
    this.api['patch']<DataSource>(`/api/v1/integrations/${source.id}`, { status: next }).subscribe({
      next: (s) => this.sources.update(list => list.map(x => x.id === s.id ? s : x)),
    });
  }

  confirmRemove(id: number): void { this.deleteId.set(id); }

  remove(): void {
    const id = this.deleteId();
    if (!id) return;
    this.api['delete'](`/api/v1/integrations/${id}`).subscribe({
      next: () => {
        this.sources.update(list => list.filter(s => s.id !== id));
        this.notify.success('Integration removed.');
        this.deleteId.set(null);
      },
    });
  }

  statusVariant(s: string): 'success' | 'danger' | 'warning' | 'neutral' {
    const map: Record<string, any> = { active: 'success', error: 'danger', paused: 'warning' };
    return map[s] ?? 'neutral';
  }

  isConnected(type: string): boolean {
    return this.sources().some(s => s.type === type);
  }

  get nameField() { return this.form.get('name')!; }
  get credsGroup() { return this.form.get('creds') as FormGroup; }
}
