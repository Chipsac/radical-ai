<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $payslips = $user->isAdmin()
            ? Payslip::with(['employee.user'])->orderByDesc('pay_date')->get()
            : Payslip::where('employee_id', $user->employee?->id)->orderByDesc('pay_date')->get();

        return view('hr.payslips', [
            'payslips' => $payslips,
            'employees' => $user->isAdmin()
                ? Employee::with('user')->get()->sortBy(fn ($e) => $e->user->name)->values()
                : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period_label' => ['required', 'string', 'max:100'],
            'pay_date' => ['required', 'date'],
            'gross' => ['required', 'numeric', 'min:0'],
            'net' => ['required', 'numeric', 'min:0'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $path = $request->file('file')->store('payslips');

        Payslip::create([
            'employee_id' => $data['employee_id'],
            'period_label' => $data['period_label'],
            'pay_date' => $data['pay_date'],
            'gross' => $data['gross'],
            'net' => $data['net'],
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Payslip uploaded.');
    }

    public function download(Request $request, Payslip $payslip)
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin() || $payslip->employee_id === $user->employee?->id,
            403,
            'You may only download your own payslips.'
        );

        return response()->download(
            storage_path('app/'.$payslip->file_path),
            'payslip-'.str_replace(' ', '-', strtolower($payslip->period_label)).'.pdf'
        );
    }
}
