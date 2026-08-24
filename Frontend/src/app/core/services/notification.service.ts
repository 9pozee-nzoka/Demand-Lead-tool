import { Injectable, signal } from '@angular/core';

export type NotificationType = 'success' | 'error' | 'info' | 'warning';

export interface Notification {
  id: string;
  type: NotificationType;
  message: string;
  duration?: number;
}

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly notifications = signal<Notification[]>([]);
  readonly items = this.notifications.asReadonly();

  success(message: string, duration = 5000): void {
    this.show('success', message, duration);
  }

  error(message: string, duration = 7000): void {
    this.show('error', message, duration);
  }

  info(message: string, duration = 5000): void {
    this.show('info', message, duration);
  }

  warning(message: string, duration = 6000): void {
    this.show('warning', message, duration);
  }

  remove(id: string): void {
    this.notifications.update(items => items.filter(n => n.id !== id));
  }

  private show(type: NotificationType, message: string, duration: number): void {
    const id = `${Date.now()}-${Math.random()}`;
    const notification: Notification = { id, type, message, duration };

    this.notifications.update(items => [...items, notification]);

    if (duration > 0) {
      setTimeout(() => this.remove(id), duration);
    }
  }
}
