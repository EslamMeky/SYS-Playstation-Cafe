<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Item;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    use GeneralTrait;

     public function index(Request $request)
    {
        try {
            $q = Item::with('branch');

            if ($request->branch_id)
                $q->where('branch_id', $request->branch_id);

            if ($request->category)
                $q->where('category', $request->category);

            $items = $q->latest()->paginate(20);
            return $this->ReturnData('items', $items, 'get 20 items successfully');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function store(Request $request)
    {
        try {
            $rules = [
                'branch_id' => 'required|exists:branches,id',
                'name' => 'required|string',
                'category' => 'nullable|in:drink,food,service,other',
                'price' => 'required|numeric|min:0',
                'stock' => 'nullable|integer|min:0',
                'available' => 'boolean',
                'notes' => 'nullable|string',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $item = Item::create($request->only([
                'branch_id', 'name', 'category', 'price', 'stock', 'available', 'notes'
            ]));

            return $this->ReturnData('item', $item, 'تم حفظ الصنف بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $item = Item::with('branch')->find($id);
            if (!$item) return $this->ReturnError('404', 'لا يوجد هذا الصنف');
            return $this->ReturnData('item', $item, 'تم جلب الصنف بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }


     public function update(Request $request, $id)
    {
        try {
            $item = Item::find($id);
            if (!$item) return $this->ReturnError('404', 'لا يوجد هذا الصنف');

            $item->update($request->only([
                'name', 'category', 'price', 'stock', 'available', 'notes'
            ]));

            return $this->ReturnData('item', $item, 'تم تعديل الصنف بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }


     public function destroy($id)
    {
        try {
            $item = Item::find($id);
            if (!$item) return $this->ReturnError('404', 'لا يوجد هذا الصنف');
            $item->delete();
            return $this->ReturnData('item', $item, 'تم حذف الصنف بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }

}
