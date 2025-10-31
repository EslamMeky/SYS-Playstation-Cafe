<?php

namespace App\Http\Controllers\API;

use Exception;
use Carbon\Carbon;
use App\Models\employees;
use App\Models\Attendance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class EmployeesController extends Controller
{
    use GeneralTrait;

    public function index(Request $request)
    {
        try
        {
            $employee=employees::with(['user','branch'])->latest()->paginate(20);
            return $this->ReturnData('employee',$employee,'Get 20 Employee');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function store(Request $request)
    {
        try
        {
            $rules = [
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required_without:user_id|string',
            'position' => 'nullable|string',
            'phone' => 'required|string',
            'salary' => 'nullable|numeric',
            'join_date' => 'nullable|date',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

             $employee = employees::create([
                'branch_id'=>$request->branch_id,
                'user_id' => $request->user_id,
                'name' => $request->name, // may be null if we created user and want user->name
                'position' => $request->position,
                'phone' => $request->phone,
                'salary' => $request->salary ?? 0,
                'join_date' => $request->join_date,
                ]);
            return $this->ReturnSuccess('200','تم اضافه الموظف بنجاح');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function show($id){
        try
        {
         $employee = employees::with('user','attendances','transactions','branch')->find($id);
        //  $employee = employees::with('user')->find($id);
        if (!$employee) return $this->ReturnError('Error', 'Not found', 404);
        // include display_name
        $data = $employee->toArray();
        $data['display_name'] = $employee->display_name;
        return $this->ReturnData('employee',$data,'done');

        }
        catch(Exception $ex){
            return $this->ReturnError($ex->getCode(),$ex->getMessage());

        }
    }

    public function update(Request $request,$id)
    {
        try
        {

            $empolyee=employees::find($id);
            if(!$empolyee) return $this->ReturnError('Error','Not Found ',404);
            $rules = [
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required_without:user_id|string',
            'position' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'phone' => 'required|string',
            'join_date' => 'nullable|date',
            'active' => 'nullable|boolean',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

             $empolyee->update([
                'branch_id'=>$request->branch_id,
                'user_id' => $request->user_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'position' => $request->position,
                'salary' => $request->salary ?? 0,
                'join_date' => $request->join_date,
                'active'=>$request->active,
]);
            return $this->ReturnSuccess('200','تم تحديث الموظف بنجاح');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function destroy($id)
    {
        try{
        $employee = employees::find($id);
        if (!$employee) return $this->ReturnError('Error','Not Found ',404);

        $employee->delete();
        return $this->ReturnSuccess('200' , 'تم حذف الموظف بنجاح');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function getMonthlySalary(Request $request, $employee_id)
    {
        try {
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;

        $employee = employees::find($employee_id);
        if (!$employee) return $this->ReturnError('404', 'Employee not found');

        $base_salary = $employee->salary ?? 0;
        $working_days = $request->working_days ?? 30; // ممكن تعدلها لاحقًا حسب نظامك

        // نجلب كل سجلات الحضور للشهر
        $records = Attendance::where('employee_id', $employee_id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // عدد الأيام اللي حضرها (سواء كان طبيعي أو متأخر)
        $days_present = $records->whereIn('status', ['present', 'late'])->count();

        // 👇 هنا التعديل الأساسي: نحسب الغياب تلقائيًا
        $absent_days = max(0, $working_days - $days_present);

        // الأجر اليومي
        $daily_rate = $working_days > 0 ? round($base_salary / $working_days, 2) : 0;

        // خصم الغياب
        $absence_deduction = round($absent_days * $daily_rate, 2);

        // تأخير = خصم بسيط (اختياري لو عندك late_penalty)
        $late_count = $records->where('status', 'late')->count();
        $late_penalty_days = $late_count * 0.25; // كل تأخير يعادل ربع يوم مثلاً
        $late_penalty_amount = round($late_penalty_days * $daily_rate, 2);

        // إجمالي الساعات المحسوبة (لو بتسجّلها)
        $total_hours = round($records->sum('hours_worked'), 2);

        // العلاوات والخصومات من جدول المعاملات
        $bonuses = Transaction::where('employee_id', $employee_id)
            ->where('type', 'bonus')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');

        $deductions = Transaction::where('employee_id', $employee_id)
            ->where('type', 'deduction')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');

        // الإجمالي
        $total_deductions = $deductions + $absence_deduction + $late_penalty_amount;
        $net_salary = round($base_salary - $total_deductions + $bonuses, 2);

        // ✅ الناتج النهائي في object مفيد وسهل الاختبار
        $salary_summary = [
            'employee_id' => $employee->id,
            'employee_name' => $employee->display_name ?? $employee->name,
            'year' => $year,
            'month' => $month,
            'base_salary' => $base_salary,
            'working_days' => $working_days,
            'days_present' => $days_present,
            'absent_days' => $absent_days,
            'late_count' => $late_count,
            'total_hours' => $total_hours,
            'daily_rate' => $daily_rate,
            'absence_deduction' => $absence_deduction,
            'late_penalty_days' => $late_penalty_days,
            'late_penalty_amount' => $late_penalty_amount,
            'other_deductions' => $deductions,
            'bonuses' => $bonuses,
            'total_deductions' => $total_deductions,
            'net_salary' => $net_salary,
        ];

            return $this->ReturnData('salary_summary', $salary_summary, 'Monthly salary calculated successfully');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode() ?: 500, $ex->getMessage());
        }
    }




 public function monthlyReport(Request $request)
{
    try {
        $month = $request->query('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();
        $workingDays = 30; // تقدر تخصصها أو تحسبها ديناميكيًا

        $employees = Employees::with([
            'attendances' => function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('date', [$startOfMonth, $endOfMonth]);
            },
            'transactions' => function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            },
            'user'
        ])->get();

        $report = $employees->map(function($employee) use ($workingDays, $startOfMonth, $endOfMonth) {
            $baseSalary = $employee->salary ?? 0;
            $dailyRate = $workingDays > 0 ? round($baseSalary / $workingDays, 2) : 0;

            // ✅ حساب العلاوات والخصومات
            $bonus = $employee->transactions->where('type', 'bonus')->sum('amount');
            $deduction = $employee->transactions->where('type', 'deduction')->sum('amount');

            // ✅ حساب الحضور والغياب
            $daysPresent = $employee->attendances->whereIn('status', ['present', 'late'])->count();
            $absentDays = max(0, $workingDays - $daysPresent);

            // ✅ حساب الخصومات بسبب الغياب
            $absenceDeduction = round($absentDays * $dailyRate, 2);

            // ✅ تأخيرات بسيطة لو حبيت تخصمها
            $lateCount = $employee->attendances->where('status', 'late')->count();
            $latePenaltyDays = $lateCount * 0.25;
            $latePenaltyAmount = round($latePenaltyDays * $dailyRate, 2);

            // ✅ صافي المرتب النهائي
            $totalDeductions = $deduction + $absenceDeduction + $latePenaltyAmount;
            $netSalary = round($baseSalary - $totalDeductions + $bonus, 2);

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->user->name ?? $employee->name,
                'base_salary' => $baseSalary,
                'working_days' => $workingDays,
                'days_present' => $daysPresent,
                'absent_days' => $absentDays,
                'bonus_total' => $bonus,
                'deduction_total' => $deduction,
                'absence_deduction' => $absenceDeduction,
                'late_penalty_amount' => $latePenaltyAmount,
                'net_salary' => $netSalary,
            ];
        });

        $data = [
            'month' => $month,
            'report' => $report,
        ];

        return $this->ReturnData('data', $data, 'Monthly Report Generated Successfully');
    }
    catch (Exception $ex) {
        return $this->ReturnError($ex->getCode(), $ex->getMessage());
    }
}


}
