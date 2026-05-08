export const BtnSize = {
  SM: 'sm',
  MD: 'md',
  LG: 'lg',
} as const;

export type BtnSize = (typeof BtnSize)[keyof typeof BtnSize];
