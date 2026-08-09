<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use App\Models\QueueItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseController
{
    public function daily(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $date = $request->get('date', now()->toDateString());

        $totalPatients = QueueItem::where('clinic_id', $clinicId)
            ->whereDate('queue_date', $date)
            ->count();

        $completedQueues = QueueItem::where('clinic_id', $clinicId)
            ->whereDate('queue_date', $date)
            ->where('status', 'done')
            ->count();

        $revenue = Invoice::where('clinic_id', $clinicId)
            ->where('status', 'paid')
            ->whereDate('paid_at', $date)
            ->sum('total');

        $byDoctor = QueueItem::where('clinic_id', $clinicId)
            ->whereDate('queue_date', $date)
            ->select('doctor_id', DB::raw('count(*) as total'))
            ->groupBy('doctor_id')
            ->with('doctor:id,name')
            ->get();

        return $this->success([
            'date' => $date,
            'total_patients' => $totalPatients,
            'completed_queues' => $completedQueues,
            'pending_queues' => $totalPatients - $completedQueues,
            'revenue' => (float) $revenue,
            'by_doctor' => $byDoctor,
        ]);
    }

    public function bpjs(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $month = $request->get('month', now()->format('Y-m'));

        $records = DB::select("
            SELECT
                mr.id,
                p.nik,
                p.name as patient_name,
                p.dob,
                d.code as icd10_code,
                d.name_id as diagnosis_name,
                mr.created_at as visit_date
            FROM medical_records mr
            JOIN patients p ON mr.patient_id = p.id
            JOIN medical_record_diagnoses mrd ON mrd.medical_record_id = mr.id
            JOIN diagnoses d ON mrd.diagnosis_id = d.id
            WHERE mr.clinic_id = ?
            AND DATE_FORMAT(mr.created_at, '%Y-%m') = ?
            AND mrd.type = 'primary'
            ORDER BY mr.created_at
        ", [$clinicId, $month]);

        return $this->success([
            'month' => $month,
            'total_records' => count($records),
            'records' => $records,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $invoices = Invoice::where('clinic_id', $clinicId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->with(['patient', 'items'])
            ->get();

        $totalRevenue = $invoices->sum('total');

        return $this->success([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_invoices' => $invoices->count(),
            'total_revenue' => (float) $totalRevenue,
            'invoices' => $invoices,
        ]);
    }
}
