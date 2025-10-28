<?php

namespace App\Http\Controllers\API;

use Exception;
use Carbon\Carbon;
use App\Models\employees;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    use GeneralTrait;

    public function checkIn(Request $request)
    {

        try{

            $rules = [
                'employee_id' => 'required|exists:employees,id',
                // optional: expected_check_in e.g. "09:00" to determine late
                'expected_check_in' => 'nullable|date_format:H:i',
                'notes' => 'nullable|string'
            ];
             $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $employee = employees::find($request->employee_id);
            $date = Carbon::now()->toDateString();

            // prevent duplicate check-in for same day
            $attendance = Attendance::firstOrNew(['employee_id' => $employee->id, 'date' => $date]);

            if ($attendance->check_in) {
                return $this->ReturnData('attendance',$attendance,'Already Check in for today');
            }

            $now = Carbon::now();
            $attendance->check_in = $now;
            $attendance->notes = $request->notes ?? $attendance->notes;

            // determine late status if expected_check_in provided (default 09:00)
            $expected = $request->expected_check_in ? Carbon::parse($date . ' ' . $request->expected_check_in) : Carbon::parse($date . ' 09:00');
            $attendance->status = $now->greaterThan($expected) ? 'late' : 'present';

            $attendance->save();

            return $this->ReturnData('attendance',$attendance,'Check in Successfully');

     }
        catch(Exception $ex)
            {
                return $this->ReturnError($ex->getCode(),$ex->getMessage());
            }
    }



    public function checkOut(Request $request)
    {
        try{

        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'notes' => 'nullable|string'
        ];
         $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

        $employee = employees::find($request->employee_id);
        $date = Carbon::now()->toDateString();

        $attendance = Attendance::where('employee_id', $employee->id)->where('date', $date)->first();
        if (!$attendance || !$attendance->check_in) {
            return $this->ReturnError('Error','No check-in found for today',404);
        }
        if ($attendance->check_out) {
            return $this->ReturnData('attendance',$attendance,'Already Check out for today');
        }

        $now = Carbon::now();
        $attendance->check_out = $now;
        $attendance->work_hours = Attendance::calculateHours($attendance->check_in, $attendance->check_out);
        $attendance->notes = $request->notes ?? $attendance->notes;

        // if previously marked absent but now has check_in -> set present/late preserved
        if ($attendance->status === 'absent') {
            $attendance->status = $attendance->check_in ? ($attendance->check_in > Carbon::parse($date . ' 09:00') ? 'late' : 'present') : 'present';
        }

        $attendance->save();

        return $this->ReturnData('attendance',$attendance,'Check out successfully');
    }
     catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

     public function manualEntry(Request $request)
    {
        try{
        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:Y-m-d H:i:s',
            'check_out' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:check_in',
            'status' => 'nullable|in:present,absent,late',
            'notes' => 'nullable|string'
        ];
         $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

        $attendance = Attendance::firstOrNew([
            'employee_id' => $request->employee_id,
            'date' => $request->date
        ]);

        $attendance->check_in = $request->check_in;
        $attendance->check_out = $request->check_out;
        $attendance->notes = $request->notes ?? $attendance->notes;
        $attendance->status = $request->status ?? ($attendance->check_in ? 'present' : 'absent');

        if ($attendance->check_in && $attendance->check_out) {
            $attendance->work_hours = Attendance::calculateHours($attendance->check_in, $attendance->check_out);
        }

        $attendance->save();

        return $this->ReturnData('attendance',$attendance,'Attendance Saved');
    }
     catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

     public function index(Request $request)
    {
        try{
        $rules = [
            'employee_id' => 'nullable|exists:employees,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'page' => 'nullable|integer'
        ];
         $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
        $q = Attendance::with('employee.user')->orderBy('date','desc');

        if ($request->employee_id) $q->where('employee_id', $request->employee_id);
        if ($request->from) $q->where('date','>=',$request->from);
        if ($request->to) $q->where('date','<=',$request->to);

        $list = $q->paginate(20);

       return $this->ReturnData('attendance',$list,'20 Attendance paginate done');
    }
     catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }


     public function monthlyReport(Request $request)
    {
        try{
        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12'
        ];
        $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

        $from = Carbon::create($request->year, $request->month, 1)->startOfMonth()->toDateString();
        $to = Carbon::create($request->year, $request->month, 1)->endOfMonth()->toDateString();

        $records = Attendance::with('employee')
        ->where('employee_id', $request->employee_id)
            ->whereBetween('date', [$from, $to])
            ->get();

        $totalHours = $records->sum('work_hours');
        $daysPresent = $records->whereIn('status', ['present','late'])->count();
        $lateCount = $records->where('status','late')->count();
        $absentCount = $records->where('status','absent')->count();

        $data=[
            'employee_id' => $request->employee_id,
            'month' => $request->month,
            'year' => $request->year,
            'total_hours' => round($totalHours, 2),
            'days_present' => $daysPresent,
            'late_count' => $lateCount,
            'absent_count' => $absentCount,
            'records_count' => $records->count(),
        ];
       return $this->ReturnData('data',$data,'attendance person');
    }
     catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
   }


}
