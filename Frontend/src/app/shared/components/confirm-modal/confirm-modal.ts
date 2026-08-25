import { Component, input, output } from '@angular/core';

@Component({
  selector: 'app-confirm-modal',
  templateUrl: './confirm-modal.html',
  styleUrl:    './confirm-modal.scss',
})
export class ConfirmModal {
  title       = input('Confirm action');
  message     = input('Are you sure you want to proceed?');
  confirmLabel = input('Confirm');
  cancelLabel  = input('Cancel');
  danger       = input(false);

  confirmed = output<void>();
  cancelled = output<void>();
}
