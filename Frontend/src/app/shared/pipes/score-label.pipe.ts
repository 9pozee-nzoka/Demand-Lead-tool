import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'scoreLabel', pure: true })
export class ScoreLabelPipe implements PipeTransform {
  transform(score: number): string {
    if (score >= 90) return 'HOT';
    if (score >= 70) return 'WARM';
    if (score >= 40) return 'POTENTIAL';
    return 'LOW';
  }
}
