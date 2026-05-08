export const BtnVariant = {
  FILL: 'fill',
  OUTLINE: 'outline',
  GHOST: 'ghost',
  GRAY: 'gray',
  SUCCESS: 'success',
  DANGER: 'danger',
} as const;

export type BtnVariant = (typeof BtnVariant)[keyof typeof BtnVariant];
