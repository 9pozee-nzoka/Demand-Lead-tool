import { Pipe, PipeTransform } from '@angular/core';

/** Formats numbers as KSh 1,234,567 */
@Pipe({ name: 'currencyKe', pure: true })
export class CurrencyKePipe implements PipeTransform {
  transform(value: number | null | undefined, symbol = 'KSh'): string {
    if (value == null) return '—';
    return `${symbol} ${value.toLocaleString('en-KE', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
  }
}
