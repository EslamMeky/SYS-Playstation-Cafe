<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    use GeneralTrait;

    public function add(Request $request)
    {
        try{
        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:bonus,deduction',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string',
            'date' => 'nullable|date'
        ];
        $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
        $tx = Transaction::create([
            'employee_id' => $request->employee_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'date' => $request->date ?? now()->toDateString(),
        ]);

        // return $this->ReturnSuccess('200','saved successfully');
        return $this->ReturnData('transaction',$tx,'saved Successfully');
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
            'to' => 'nullable|date'
        ];
         $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

        $q = Transaction::with('employee');

        if ($request->employee_id) $q->where('employee_id', $request->employee_id);
        if ($request->from) $q->where('date', '>=', $request->from);
        if ($request->to) $q->where('date', '<=', $request->to);

        $list = $q->orderBy('date','desc')->paginate(20);
        return $this->ReturnData('data',$list,'20 transaction successfully');
    }
     catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function monthlySummary(Request $request)
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
        $from = now()->setDate($request->year, $request->month, 1)->startOfMonth()->toDateString();
        $to = now()->setDate($request->year, $request->month, 1)->endOfMonth()->toDateString();

        $bonuses = Transaction::where('employee_id',$request->employee_id)
            ->where('type','bonus')
            ->whereBetween('date', [$from,$to])->sum('amount');

        $deductions = Transaction::where('employee_id',$request->employee_id)
            ->where('type','deduction')
            ->whereBetween('date', [$from,$to])->sum('amount');

        $data=[
            'employee_id' => $request->employee_id,
            'month' => $request->month,
            'year' => $request->year,
            'bonuses' => round($bonuses,2),
            'deductions' => round($deductions,2),
            'net_adjustments' => round($bonuses - $deductions,2),
        ];
        return $this->ReturnData('data',$data,'Monthly Summary');
    }
     catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }



}
