<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Table;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TableController extends Controller
{
    use GeneralTrait;

    public function index(Request $request)
    {
         try
        {
            $q = Table::with('branch');
            if ($request->branch_id) $q->where('branch_id', $request->branch_id);
            if ($request->type) $q->where('type', $request->type);
            $q->orderBy('name')->paginate(20);
            return $this->ReturnData('tables',$q,'get 20 tables successfully');
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
                'branch_id'=>'required|exists:branches,id',
                'name'=>'required|string',
                'type'=>'nullable|string',
                'capacity'=>'nullable|integer|min:1',
                'price_per_hour'=>'nullable|numeric|min:0',
                'min_hours'=>'nullable|numeric|min:0',
                'notes'=>'nullable|string'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

             $table = Table::create($request->only(['branch_id','name','type','capacity','price_per_hour','min_hours','notes']));
             return $this->ReturnData('table',$table,'تم حفظ الترابيزه بنجاح ',);

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
            $t=Table::with('branch')->find($id);
            if(!$t) return $this->ReturnError('Eroor','لا يوجد هذه الترابيزه ');
            return $this->ReturnData('table',$t,'get this branch successfully');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function update(Request $request,$id)
    {
        try
        {
           $t=Table::find($id);
           if(!$t) return $this->ReturnError('Eroor','لا يوجد هذه التربيزات ');
           $t->update($request->only(['name','type','capacity','price_per_hour','min_hours','status','notes']));
           return $this->ReturnData('table',$t,'تم تعديل الترابيزه بنجاح ');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }


    public function destroy($id)
    {
        try
        {
            $t = Table::find($id);
            if(!$t) return  $this->ReturnError('Eroor','لا يوجد هذه الترابيزه ');
            $t->delete();
            return $this->ReturnData('table',$t,'تم مسح الترابيزه بنجاح ');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }
}
