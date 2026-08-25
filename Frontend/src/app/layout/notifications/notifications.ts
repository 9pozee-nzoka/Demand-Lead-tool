import { Component } from '@angular/core';
import { NgClass } from '@angular/common';
import { NotificationService } from '../../core/services/notification.service';

@Component({
  selector: 'app-notifications',
  imports: [NgClass],
  templateUrl: './notifications.html',
  styleUrl: './notifications.scss',
})
export class Notifications {
  constructor(public notify: NotificationService) {}
}
