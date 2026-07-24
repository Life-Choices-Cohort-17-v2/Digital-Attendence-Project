export interface Profile {
  id: string
  first_name: string
  last_name: string
  employee_id: string
  role: "admin" | "staff"
  is_active: boolean
  email: string
  pasword: string
}
