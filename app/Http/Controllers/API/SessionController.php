<?php

namespace App\Http\Controllers\API;

use Exception;
use Carbon\Carbon;
use App\Models\Table;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    use GeneralTrait;

     public function store(Request $request)
    {
        try
        {
            $rules = [
               'table_id' => 'required|exists:tables,id',
                'customer_name' => 'required|string',
                'price_per_hour' => 'required|numeric',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }


        $table = Table::findOrFail($request->table_id);

        if (in_array($table->status, ['busy', 'reserved', 'maintenance'])) {
            return $this->ReturnError('error','الترابيزة غير متاحة حالياً');
        }

        $session = Session::create([
            'table_id' => $table->id,
            'customer_name' => $request->customer_name,
            'start_time' => now(),
            'price_per_hour' => $request->price_per_hour,
            'status' => 'ongoing',
        ]);

        $table->update(['status' => 'busy']);

        return $this->ReturnData('session',$session,'تم بدء الجلسة بنجاح');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }

    public function pause($id)
    {

        try
        {
            $session = Session::findOrFail($id);
            $session->update(['status' => 'paused']);
            return $this->ReturnSuccess('200','تم إيقاف الجلسة مؤقتاً');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }


    public function resume($id)
    {
        try
        {

        $session = Session::findOrFail($id);
        $session->update(['status' => 'ongoing']);
        return $this->ReturnSuccess('200','تم استئناف الجلسة');

        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }


    }


    public function end($id)
    {
         try
        {
            $session = Session::findOrFail($id);
            $endTime = Carbon::now();
            $hours = $endTime->diffInMinutes(Carbon::parse($session->start_time)) / 60;
            $total = $hours * $session->price_per_hour;

            $session->update([
                'end_time' => $endTime,
                'total_hours' => round($hours, 2),
                'total_amount' => round($total, 2),
                'status' => 'ended',
            ]);

            $session->table->update(['status' => 'free']);
            return $this->ReturnData('session',$session,'تم إنهاء الجلسة بنجاح');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }


     public function index()
    {
     try
        {
            $session=Session::with('table')->latest()->get();
            return $this->ReturnData('session',$session,'get all sessions');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }

    public function show($id)
    {

        try
        {
             $session=Session::with('table')->find($id);
            if(!$session) return $this->ReturnError('Eroor','لا يوجد هذه الجلسه ');
            return $this->ReturnData('session',$session,'get session');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }

}
