import { User } from '../types';

// Seed initial test accounts for the team
export const MOCK_USERS: User[] = [
  {
    id: 'usr_001',
    employeeId: 'EMP001',
    email: 'staff@insite.com',
    passwordHash: 'password123',
    firstName: 'Alex',
    lastName: 'Morgan',
    role: 'staff',
    createdAt: new Date().toISOString()
  },
  {
    id: 'usr_002',
    employeeId: 'ADM001',
    email: 'admin@insite.com',
    passwordHash: 'admin123',
    firstName: 'Sarah',
    lastName: 'Connor',
    role: 'admin',
    createdAt: new Date().toISOString()
  }
];

export const findUserByEmployeeId = (employeeId: string): User | undefined => {
  return MOCK_USERS.find(u => u.employeeId.toUpperCase() === employeeId.toUpperCase());
};

export const findUserById = (id: string): User | undefined => {
  return MOCK_USERS.find(u => u.id === id);
};