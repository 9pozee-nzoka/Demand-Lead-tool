import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/services/api.service';
import { User } from '../../core/models/api.models';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { Badge } from '../../shared/components/badge/badge';

@Component({
  selector: 'app-team',
  imports: [FormsModule, PageHeader, LoadingSpinner, EmptyState, Badge],
  templateUrl: './team.html',
  styleUrl: './team.scss',
})
export class Team implements OnInit {
  loading = signal(true);
  users   = signal<User[]>([]);

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    this.api['get']<User[]>('/api/v1/users').subscribe({
      next: (u) => { this.users.set(u); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  roleVariant(role: string): 'danger' | 'warning' | 'primary' | 'success' | 'neutral' {
    const map: Record<string, any> = {
      owner: 'danger', admin: 'warning', analyst: 'primary', sales: 'success',
    };
    return map[role] ?? 'neutral';
  }
}
