import { Component, input } from '@angular/core';
import { CommonModule } from '@angular/common';

export type GuestIconName =
  | 'user'
  | 'pencil'
  | 'bolt'
  | 'cart'
  | 'clipboard'
  | 'check-circle'
  | 'receipt'
  | 'clock'
  | 'star'
  | 'gift'
  | 'arrow-left'
  | 'x-mark'
  | 'fork-knife'
  | 'plus'
  | 'trash'
  | 'chevron-right'
  | 'lock-closed'
  | 'envelope'
  | 'sparkles'
  | 'ban';

@Component({
  selector: 'app-guest-icon',
  standalone: true,
  imports: [CommonModule],
  template: `
    <svg
      [attr.width]="size()"
      [attr.height]="size()"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.75"
      stroke-linecap="round"
      stroke-linejoin="round"
      [style.display]="'block'"
    >
      @switch (name()) {
        @case ('user') {
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        }
        @case ('pencil') {
          <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
        }
        @case ('bolt') {
          <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
        }
        @case ('cart') {
          <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
          <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
        }
        @case ('clipboard') {
          <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
          <path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>
        }
        @case ('check-circle') {
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <path d="m9 11 3 3L22 4"/>
        }
        @case ('receipt') {
          <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/>
          <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v1.5"/><path d="M12 5v1.5"/>
        }
        @case ('clock') {
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        }
        @case ('star') {
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        }
        @case ('gift') {
          <polyline points="20 12 20 22 4 22 4 12"/>
          <rect width="20" height="5" x="2" y="7"/>
          <line x1="12" x2="12" y1="22" y2="7"/>
          <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/>
          <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
        }
        @case ('arrow-left') {
          <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
        }
        @case ('x-mark') {
          <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
        }
        @case ('fork-knife') {
          <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
          <path d="M7 2v20"/>
          <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
        }
        @case ('plus') {
          <path d="M5 12h14"/><path d="M12 5v14"/>
        }
        @case ('trash') {
          <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
          <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
        }
        @case ('chevron-right') {
          <path d="m9 18 6-6-6-6"/>
        }
        @case ('lock-closed') {
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        }
        @case ('envelope') {
          <rect width="20" height="16" x="2" y="4" rx="2"/>
          <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
        }
        @case ('sparkles') {
          <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
          <path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/>
        }
        @case ('ban') {
          <circle cx="12" cy="12" r="10"/>
          <path d="m4.9 4.9 14.2 14.2"/>
        }
        @default {
          <circle cx="12" cy="12" r="10"/>
        }
      }
    </svg>
  `,
})
export class GuestIconComponent {
  readonly name = input.required<GuestIconName>();
  readonly size = input<number>(20);
}
