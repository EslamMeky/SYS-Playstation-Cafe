<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Branche;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    use GeneralTrait;

    public function index()
    {
        try
        {
            $branches=Branche::with('tables')->latest()->paginate(20);
            return $this->ReturnData('branches',$branches,'get 20 branches..');
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
                'name'=>'required|string',
                'type'=>'nullable|in:cafe,playstation,other',
                'location'=>'nullable|string',
                'phone'=>'nullable|string',
                'settings'=>'nullable|array'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $branch = Branche::create($request->only(['name','type','location','phone','settings']));
             return $this->ReturnData('branch',$branch,'تم حفظ الفرع بنجاح ');

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
            $b=Branche::with('tables')->find($id);
            if(!$b) return $this->ReturnError('Eroor','لا يوجد هذا الفرع ');
            return $this->ReturnData('branch',$b,'get this branch successfully');
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
           $b=Branche::find($id);
           if(!$b) return $this->ReturnError('Eroor','لا يوجد هذا الفرع ');
           $b->update($request->only(['name','type','location','phone','settings']));
           return $this->ReturnData('branch',$b,'تم تعديل الفرع بنجاح ');

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
            $b=Branche::find($id);
           if(!$b) return $this->ReturnError('Eroor','لا يوجد هذا الفرع ');
           $b->delete();
            return $this->ReturnData('branch',$b,'تم مسح الفرع بنجاح ');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

}
