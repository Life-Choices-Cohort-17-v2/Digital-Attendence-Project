import { describe, it, expect, vi, beforeEach } from "vitest";

// create mock/fake functions once

const mockSingle = vi.fn()
const mockEq = vi.fn().mockReturnThis()
const mockSelect = vi.fn().mockReturnThis()
const mockInsert = vi.fn().mockReturnThis()
const mockUpdate = vi.fn().mockReturnThis()
const mockDelete = vi.fn().mockReturnThis()

// mock Supabase, pretend that you are really targeting the database on Supabase

vi.mock('../src/config/supabase.js', () => ({
    supabase: {
        from: vi.fn(() => ({
            select: mockSelect,
            insert: mockInsert,
            update: mockUpdate,
            delete: mockDelete,
            eq: mockEq,
            single: mockSingle,
        }))
    }
}))

import {getProfilesDb, createProfileDb, deleteProfileDb, updateProfileDb, resetPasswordDb} from '../../src/models/profileDb.js'

// reset mocks before each test
beforeEach(() => {
    vi.clearAllMocks()
    //Reapply mockReturnThis() after clearAllMocks
    mockSelect.mockReturnThis()
    mockInsert.mockReturnThis()
    mockUpdate.mockReturnThis()
    mockDelete.mockReturnThis()
    mockEq.mockReturnThis()
})

//GET ALL

describe('getProfilesDb', () => {

 it('should return all profiles successfully', async () => {
    const mockData = [
      { id: '14271887-48ea-48c8-9890-6cb196afa0Gc', first_name: 'Joshua', last_name: 'Jacobs', employee_id: 'A-005', role: 'admin', is_active: true, email: 'jodam@gmail.com', password: 'joh123' },
      { id: '14361887-48ea-48c8-9890-6cb196afa0Gc', first_name: 'Charlton', last_name: 'Poole', employee_id: 'S-006', role: 'staff', is_active: true, email: 'charlton@gmail.com', password: 'charlton123' }
    ]

    mockSelect.mockResolvedValueOnce({ data: mockData, error: null })

    const result = await getProfilesDb()

    expect(result.success).toBe(true)
    expect(result.data).toHaveLength(2)
 })

 it('should return profiles with all required fields', async () => {
    const mockData = [
        { id: '14271887-48ea-48c8-9890-6cb196afa0Gc', first_name: 'Joshua', last_name: 'Jacobs', employee_id: 'A-005', role: 'admin', is_active: true, email: 'jodam@gmail.com', password: 'joh123' }
    ]

    mockSelect.mockResolvedValueOnce({ data: mockData, error: null })

    const result = await getProfilesDb()

    // Check the first record has all required fields
    const profile = result.data?.[0]

    expect(profile).toHaveProperty('first_name')
    expect(profile).toHaveProperty('last_name')
    expect(profile).toHaveProperty('email')
    expect(profile).toHaveProperty('employee_id')
    expect(profile).toHaveProperty('role')
    expect(profile).toHaveProperty('is_active')

    // Check the actual values
    expect(profile?.first_name).toBe('Joshua')
    expect(profile?.last_name).toBe('Jacobs')
    expect(profile?.email).toBe('jodam@gmail.com')
    expect(profile?.employee_id).toBe('A-005')
    expect(profile?.role).toBe('admin')
    expect(profile?.is_active).toBe(true)
  })

  it('should return error if supabase fails', async () => {
    mockSelect.mockResolvedValueOnce({
      data: null,
      error: { message: 'Database error' }
    })

    const result = await getProfilesDb()

    expect(result.success).toBe(false)
    expect(result.error).toBe('Database error')
  })
})

describe('createProfileDb', () => {

  it('should create a staff profile successfully with auto-generated 8 char password', async () => {
    const mockData = {
      id: '14271887-48ea-48c8-9890-6cb196afa0Gc',
      first_name: 'Joshua',
      last_name: 'Jacobs',
      employee_id: 'S-005',
      role: 'staff',
      is_active: true,
      email: 'jodam@gmail.com',
      password: 'Xk9mP2qR'  // auto-generated 8 char plain text
    }

    mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

    const result = await createProfileDb('Joshua', 'Jacobs', 'S-005', 'staff', 'jodam@gmail.com')

    expect(result.success).toBe(true)
    expect(result.data?.first_name).toBe('Joshua')
    expect(result.data?.last_name).toBe('Jacobs')
    expect(result.data?.employee_id).toBe('S-005')
    expect(result.data?.role).toBe('staff')
    expect(result.data?.is_active).toBe(true)
    expect(result.data?.email).toBe('jodam@gmail.com')

    // Password must be auto-generated, 8 chars, plain text — returned to admin
    expect(result.data?.password).toBeDefined()
    expect(result.data?.password).toHaveLength(8)
    expect(typeof result.data?.password).toBe('string')
  })

  it('should create an admin profile successfully with auto-generated 8 char password', async () => {
    // Admin can create OTHER admins too
    const mockData = {
      id: '24271887-48ea-48c8-9890-6cb196afa0Gc',
      first_name: 'Sarah',
      last_name: 'Johnson',
      employee_id: 'A-010',
      role: 'admin',           // role is admin this time
      is_active: true,
      email: 'sarah@company.com',
      password: 'Nq7rT2mX'   // auto-generated 8 char plain text
    }

    mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

    const result = await createProfileDb('Sarah', 'Johnson', 'A-010', 'admin', 'sarah@company.com')

    expect(result.success).toBe(true)
    expect(result.data?.role).toBe('admin')          // confirms admin can create admins
    expect(result.data?.password).toBeDefined()
    expect(result.data?.password).toHaveLength(8)
    expect(typeof result.data?.password).toBe('string')
  })

  it('should auto-generate a different password each time', async () => {
    // Two creates should not return the same password
    const mockData1 = {
      id: '14271887-48ea-48c8-9890-6cb196afa0Gc',
      first_name: 'Joshua',
      last_name: 'Jacobs',
      employee_id: 'S-005',
      role: 'staff',
      is_active: true,
      email: 'jodam@gmail.com',
      password: 'Xk9mP2qR'
    }

    const mockData2 = {
      id: '24271887-48ea-48c8-9890-6cb196afa0Gc',
      first_name: 'Sarah',
      last_name: 'Johnson',
      employee_id: 'S-006',
      role: 'staff',
      is_active: true,
      email: 'sarah@company.com',
      password: 'Nq7rT2mX'   // different password
    }

    mockSingle.mockResolvedValueOnce({ data: mockData1, error: null })
    mockSingle.mockResolvedValueOnce({ data: mockData2, error: null })

    const result1 = await createProfileDb('Joshua', 'Jacobs', 'S-005', 'staff', 'jodam@gmail.com')
    const result2 = await createProfileDb('Sarah', 'Johnson', 'S-006', 'staff', 'sarah@company.com')

    // Passwords must be different — not the same every time
    expect(result1.data?.password).not.toBe(result2.data?.password)
  })

  it('should return the password in plain text so admin can share it with staff', async () => {
    const mockData = {
      id: '14271887-48ea-48c8-9890-6cb196afa0Gc',
      first_name: 'Joshua',
      last_name: 'Jacobs',
      employee_id: 'S-005',
      role: 'staff',
      is_active: true,
      email: 'jodam@gmail.com',
      password: 'Xk9mP2qR'
    }

    mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

    const result = await createProfileDb('Joshua', 'Jacobs', 'S-005', 'staff', 'jodam@gmail.com')

    // Password must be in the response — admin needs to see it and share with staff
    expect(result.data?.password).toBeDefined()

    // Must not be hashed — hashed passwords look like '$2b$10$...' (bcrypt format)
    expect(result.data?.password).not.toMatch(/^\$2[ab]\$/)

    // Must be exactly 8 characters — not a hash which is 60 chars
    expect(result.data?.password).toHaveLength(8)
  })

  it('should only accept role of staff or admin', async () => {
    // Invalid role should fail validation before hitting Supabase
    const result = await createProfileDb('Joshua', 'Jacobs', 'S-005', 'superuser', 'jodam@gmail.com')

    expect(result.success).toBe(false)
    expect(result.error).toBe('Role must be either staff or admin')
  })

  it('should return error if creation fails', async () => {
    mockSingle.mockResolvedValueOnce({
      data: null,
      error: { message: 'Creation failed' }
    })

    const result = await createProfileDb('Joshua', 'Jacobs', 'A-005', 'admin', 'jodam@gmail.com')

    expect(result.success).toBe(false)
    expect(result.error).toBe('Creation failed')
  })
})

//UPDATE
describe('updateProfileDb', () => {

  it('should update a staff profile successfully by employee_id', async () => {
    const mockData = {
      id: '18741887-48ea-48c8-9890-6cb196afa0Gc',
      first_name: 'Siza',
      last_name: 'Mpafa',
      employee_id: 'S-007',
      role: 'staff',
      is_active: true,
      email: 'siza@gmail.com'
    }

    mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

    // Target by employee_id not uuid
    const result = await updateProfileDb('S-007',{
      first_name: 'Siza',
      last_name: 'Mpafa',
      role: 'staff',
      is_active: true,
      email: 'siza@gmail.com'
    })

    expect(result.success).toBe(true)
    expect(result.data?.first_name).toBe('Siza')
    expect(result.data?.last_name).toBe('Mpafa')
    expect(result.data?.employee_id).toBe('S-007')
    expect(result.data?.role).toBe('staff')       // lowercase — not 'Staff'
    expect(result.data?.is_active).toBe(true)     // boolean not string
    expect(result.data?.email).toBe('siza@gmail.com')
  })

  it('should update an admin profile successfully by employee_id', async () => {
    // Admin can be updated too — not just staff
    const mockData = {
      id: '24271887-48ea-48c8-9890-6cb196afa0Gc',
      first_name: 'Sarah',
      last_name: 'Johnson',
      employee_id: 'A-010',
      role: 'admin',
      is_active: true,
      email: 'sarah@company.com'
    }

    mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

    const result = await updateProfileDb('A-010', { 
    first_name: 'Sarah',
      email: 'sarah@company.com'
    })

    expect(result.success).toBe(true)
    expect(result.data?.role).toBe('admin')
    expect(result.data?.employee_id).toBe('A-010')
  })

  it('should update is_active to false — disabling a user via edit', async () => {
  const mockData = {
    id: '18741887-48ea-48c8-9890-6cb196afa0Gc',
    first_name: 'Siza',
    last_name: 'Mpafa',
    employee_id: 'S-007',
    role: 'staff',
    is_active: false,    // changed from true to false
    email: 'siza@gmail.com'
  }

  mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

  const result = await updateProfileDb('S-007', { is_active: false })

  expect(result.success).toBe(true)
  expect(result.data?.is_active).toBe(false)  // confirms it was disabled
})

  it('should update only one field without affecting others — Partial update', async () => {
    // Only updating role — other fields stay the same
    const mockData = {
      id: '18741887-48ea-48c8-9890-6cb196afa0Gc',
      first_name: 'Siza',
      last_name: 'Mpafa',
      employee_id: 'S-007',
      role: 'admin',       // only this changed
      is_active: true,
      email: 'siza@gmail.com'
    }

    mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

    // Partial<Profile> — only passing one field
    const result = await updateProfileDb('S-007', { role: 'admin' })

    expect(result.success).toBe(true)
    expect(result.data?.role).toBe('admin')
    // Other fields untouched
    expect(result.data?.first_name).toBe('Siza')
    expect(result.data?.email).toBe('siza@gmail.com')
  })

  it('should return error if update fails', async () => {
    mockSingle.mockResolvedValueOnce({
      data: null,
      error: { message: 'Update failed' }
    })

    const result = await updateProfileDb('S-007', { first_name: 'Ghost' })

    expect(result.success).toBe(false)
    expect(result.error).toBe('Update failed')
  })

  it('should return error if no fields provided', async () => {
    // Partial<Profile> with empty object — nothing to update
    const result = await updateProfileDb('S-007', {})

    expect(result.success).toBe(false)
    expect(result.error).toBe('No fields provided for update')
  })

  it('should return error if employee_id does not exist', async () => {
    mockSingle.mockResolvedValueOnce({
      data: null,
      error: { message: 'Profile not found' }
    })

    const result = await updateProfileDb('X-999', { first_name: 'Ghost' })

    expect(result.success).toBe(false)
    expect(result.error).toBe('Profile not found')
  })
})

describe('deleteProfileDb', () => {

  it('should delete a staff profile successfully by employee_id', async () => {
    mockEq.mockResolvedValueOnce({ data: null, error: null })

    // Target by employee_id not uuid
    const result = await deleteProfileDb('S-007')

    expect(result.success).toBe(true)
    expect(result.message).toBe('profile deleted successfully')
  })

  it('should delete an admin profile successfully by employee_id', async () => {
    mockEq.mockResolvedValueOnce({ data: null, error: null })

    const result = await deleteProfileDb('A-010')

    expect(result.success).toBe(true)
    expect(result.message).toBe('profile deleted successfully')
  })

  it('should soft disable the user — sets is_active to false not hard delete', async () => {
  const mockData = {
    id: '18741887-48ea-48c8-9890-6cb196afa0Gc',
    employee_id: 'S-007',
    is_active: false    // confirms soft disable happened
  }

  mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

  const result = await deleteProfileDb('S-007')

  expect(result.success).toBe(true)
  // Record still exists — just disabled
  expect(result.data?.is_active).toBe(false)
})    

  it('should return error if employee_id does not exist', async () => {
    mockEq.mockResolvedValueOnce({
      data: null,
      error: { message: 'Profile not found' }
    })

    const result = await deleteProfileDb('X-999')

    expect(result.success).toBe(false)
    expect(result.error).toBe('Profile not found')
  })

  it('should return error if delete fails', async () => {
    mockEq.mockResolvedValueOnce({
      data: null,
      error: { message: 'Delete failed' }
    })

    const result = await deleteProfileDb('S-007')

    expect(result.success).toBe(false)
    expect(result.error).toBe('Delete failed')
  })
})

//RESET PASSWORD
describe('resetPasswordDb', () => {

  it('should generate a new 8 char password by employee_id and return it to admin', async () => {
    const mockData = {
      id: '18741667-48ea-48c8-9890-6cb196adc0Gc',
      employee_id: 'S-007',
      password: 'Nq7rT2mX'  // new auto-generated 8 char password
    }

    mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

    // Target by employee_id not uuid
    const result = await resetPasswordDb('S-007')

    expect(result.success).toBe(true)
    // Must return new password so admin can share it with staff
    expect(result.data?.password).toBeDefined()
    expect(result.data?.password).toHaveLength(8)
    // Must be plain text — not hashed
    expect(result.data?.password).not.toMatch(/^\$2[ab]\$/)
    expect(typeof result.data?.password).toBe('string')
  })

  it('should generate a new password for admin by employee_id', async () => {
    const mockData = {
      id: '24271887-48ea-48c8-9890-6cb196afa0Gc',
      employee_id: 'A-010',
      password: 'Xk9mP2qR'
    }

    mockSingle.mockResolvedValueOnce({ data: mockData, error: null })

    const result = await resetPasswordDb('A-010')

    expect(result.success).toBe(true)
    expect(result.data?.password).toHaveLength(8)
    expect(typeof result.data?.password).toBe('string')
  })

  it('should return error if employee_id does not exist', async () => {
    mockSingle.mockResolvedValueOnce({
      data: null,
      error: { message: 'Profile not found' }
    })

    const result = await resetPasswordDb('X-999')

    expect(result.success).toBe(false)
    expect(result.error).toBe('Profile not found')
  })

  it('should return error if reset fails', async () => {
    mockSingle.mockResolvedValueOnce({
      data: null,
      error: { message: 'Reset failed' }
    })

    const result = await resetPasswordDb('S-007')

    expect(result.success).toBe(false)
    expect(result.error).toBe('Reset failed')  // lowercase 'f' — consistent casing
  })
})