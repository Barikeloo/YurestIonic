export const restaurant = {
  name: 'Bar Manolo',
  legalName: 'Bar Manolo Restauración S.L.',
  taxId: 'B12345678',
  email: 'barmanolo@gmail.com',
};

export const adminCredentials = {
  email: restaurant.email,
  password: '12345678',
  pin: '1234',
};

export interface Employee {
  name: string;
  pin: string;
  role: 'admin' | 'supervisor' | 'operator';
}

export const employees: Record<string, Employee> = {
  admin: { name: 'Manolo Pérez', pin: '1234', role: 'admin' },
  supervisor: { name: 'María García', pin: '2345', role: 'supervisor' },
  carlos: { name: 'Carlos Ruiz', pin: '3456', role: 'operator' },
  laura: { name: 'Laura Martínez', pin: '4567', role: 'operator' },
  javier: { name: 'Javier López', pin: '5678', role: 'operator' },
  sofia: { name: 'Sofía Romero', pin: '6789', role: 'operator' },
};
