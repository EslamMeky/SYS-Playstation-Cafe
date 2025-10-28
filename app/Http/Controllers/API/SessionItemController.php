<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Item;
use App\Models\SessionItem;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SessionItemController extends Controller
{
    use GeneralTrait;

    public function index(Request $request)
    {
        try {
            $q = SessionItem::with(['item'
            , 'session'
        ]);

            if ($request->session_id)
                $q->where('session_id', $request->session_id);

            $sessionItems = $q->latest()->paginate(20);
            return $this->ReturnData('session_items', $sessionItems, 'Get 20 session items successfully');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                'session_id' => 'required|exists:sessions,id',
                'item_id' => 'required|exists:items,id',
                'quantity' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $item = Item::find($request->item_id);
            $price = $item->price;

            $sessionItem = SessionItem::create([
                'session_id' => $request->session_id,
                'item_id' => $item->id,
                'quantity' => $request->quantity,
                'price' => $price,
            ]);

            return $this->ReturnData('session_item', $sessionItem, 'تم إضافة الطلب بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sessionItem = SessionItem::find($id);
            if (!$sessionItem) return $this->ReturnError('404', 'هذا الطلب غير موجود');

            $sessionItem->update([
                'quantity' => $request->quantity ?? $sessionItem->quantity,
                'price' => $request->price ?? $sessionItem->price,
            ]);
             $sessionItem->refresh();

            return $this->ReturnData('session_item', $sessionItem, 'تم تعديل الطلب بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }

     public function destroy($id)
    {
        try {
            $sessionItem = SessionItem::find($id);
            if (!$sessionItem) return $this->ReturnError('404', 'هذا الطلب غير موجود');
            $sessionItem->delete();

            return $this->ReturnData('session_item', $sessionItem, 'تم حذف الطلب بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }


}
