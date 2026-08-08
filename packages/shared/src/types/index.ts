export interface ApiResponse<T = unknown> {
  success: boolean
  data: T | null
  error: {
    code: string
    message: string
  } | null
  meta: {
    page: number
    per_page: number
    total: number
    last_page: number
  } | null
}

export type ClinicType = 'praktek_mandiri' | 'klinik_pratama' | 'klinik_utama'

export interface Clinic {
  id: number
  name: string
  slug: string
  clinic_type: ClinicType
  phone: string
  address: string
}

export interface Patient {
  id: number
  clinic_id: number
  name: string
  phone: string
  nik: string | null
  dob: string | null
  gender: 'M' | 'F' | null
}

export interface QueueItem {
  id: number
  clinic_id: number
  queue_number: string
  token: string
  position: number
  status: 'waiting' | 'called' | 'in_progress' | 'done' | 'skipped'
  patient_name: string
  patient_phone: string
  estimated_wait_minutes: number | null
}
