import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'opportunityScore', pure: true })
export class OpportunityScorePipe implements PipeTransform {
  transform(score: number): string {
    if (score >= 80) return 'VERY HIGH';
    if (score >= 60) return 'HIGH';
    if (score >= 40) return 'MODERATE';
    return 'LOW';
  }
}
