import { z } from 'zod'

export const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
})

export const patientLoginSchema = z.object({
  nik: z.string().min(16).max(16),
  password: z.string().min(8),
})

export const queueRegisterSchema = z.object({
  patient_id: z.number().optional(),
  patient_name: z.string().min(2).optional(),
  patient_phone: z.string().min(10).optional(),
  doctor_id: z.number(),
  chief_complaint: z.string().min(3),
})
