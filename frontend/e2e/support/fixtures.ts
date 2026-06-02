export const linkedRestaurant = {
  uuid: '11111111-1111-4111-8111-111111111111',
  name: 'Bar Manolo',
  legalName: 'Bar Manolo S.L.',
  taxId: 'B12345678',
  email: 'barmanolo@gmail.com',
};

export const quickUsersResponse = {
  users: [
    {
      user_uuid: '22222222-2222-4222-8222-222222222222',
      name: 'Carlos',
      role: 'operator',
      restaurant_uuid: linkedRestaurant.uuid,
      restaurant_name: linkedRestaurant.name,
      last_login_at: '2026-06-02T09:00:00Z',
    },
    {
      user_uuid: '33333333-3333-4333-8333-333333333333',
      name: 'Manolo',
      role: 'admin',
      restaurant_uuid: linkedRestaurant.uuid,
      restaurant_name: linkedRestaurant.name,
      last_login_at: '2026-06-02T09:05:00Z',
    },
  ],
};
