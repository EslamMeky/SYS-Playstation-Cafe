<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Item;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use GeneralTrait;

     public function createTakeaway(Request $request)
    {

         try
        {
            $rules = [
            'branch_id' => 'nullable|exists:branches,id',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->returnValidationError(400, $validator);
        }

        $order = Order::create([
            'branch_id' => $request->branch_id,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'type' => 'takeaway',
            'status' => 'pending',
            'total_price' => 0
        ]);

        return $this->ReturnData('order', $order, 'Takeaway order created');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }


    public function addItem(Request $request, $orderId)
    {
        try {
            $rules = [
                'item_id' => 'required|exists:items,id',
                'quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string'
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return $this->returnValidationError(400, $validator);

            $order = Order::findOrFail($orderId);
            if ($order->status !== 'pending') {
                return $this->ReturnError(400, 'Cannot add items to non-pending order');
            }

            $item = Item::findOrFail($request->item_id);
            $price = $item->price;
            $total = round($price * $request->quantity, 2);

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'item_id' => $item->id,
                'quantity' => $request->quantity,
                'price' => $price,
                'total' => $total,
                'notes' => $request->notes
            ]);

            // إعادة حساب إجمالي الطلب
            $orderTotal = OrderItem::where('order_id', $order->id)->sum('total');
            $order->update(['total_price' => $orderTotal]);

            return $this->ReturnData('order_item', $orderItem, 'Item added to order', ['order_total' => $orderTotal]);
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode() ?: 500, $ex->getMessage());
        }
    }


    public function updateItem(Request $request, $id)
    {
        try {
            $orderItem = OrderItem::findOrFail($id);
            $order = $orderItem->order;
            if ($order->status !== 'pending') return $this->ReturnError(400, 'Cannot edit this item');

            $quantity = $request->quantity ?? $orderItem->quantity;
            $price = $request->price ?? $orderItem->price;
            $total = round($quantity * $price, 2);

            $orderItem->update(['quantity' => $quantity, 'price' => $price, 'total' => $total]);

            // إعادة حساب إجمالي الطلب
            $order->update(['total_price' => OrderItem::where('order_id', $order->id)->sum('total')]);

            return $this->ReturnData('order_item', $orderItem, 'Item updated', ['order_total' => $order->total_price]);
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode() ?: 500, $ex->getMessage());
        }
    }

     public function removeItem($id)
    {
        try {
            $orderItem = OrderItem::findOrFail($id);
            $order = $orderItem->order;
            if ($order->status !== 'pending') return $this->ReturnError(400, 'Cannot delete item');

            $orderItem->delete();
            $order->update(['total_price' => OrderItem::where('order_id', $order->id)->sum('total')]);

            return $this->ReturnSuccess(200, 'Item removed and order total updated');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode() ?: 500, $ex->getMessage());
        }
    }

    public function payOrder(Request $request, $orderId)
    {
        DB::beginTransaction();
        try {
            $order = Order::with('items.item')->findOrFail($orderId);
            if ($order->status === 'paid') return $this->ReturnError(400, 'Order already paid');

            $paymentMethod = $request->payment_method ?? 'cash';

            // create invoice (reuse invoices table)
            $invoice = Invoice::create([
                'session_id' => null, // no session
                'session_total' => 0,
                'items_total' => $order->total_price,
                'final_total' => $order->total_price,
                'payment_method' => $paymentMethod,
                'status' => 'paid'
            ]);

            // update order
            $order->update(['status' => 'paid', 'payment_method' => $paymentMethod]);

            DB::commit();

            // return invoice + order + items
            $invoice->load('session'); // session null but okay
            return $this->ReturnData('invoice', [
                'invoice' => $invoice,
                'order' => $order->load('items.item')
            ], 'Order paid and invoice created');
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->ReturnError($ex->getCode() ?: 500, $ex->getMessage());
        }
    }


    public function show($id)
    {
        $order = Order::with('items.item','branch')->findOrFail($id);
        return $this->ReturnData('order', $order, 'Order details');
    }

     public function index(Request $request)
    {
        $q = Order::with('items.item','branch')->orderBy('created_at','desc');

        if ($request->branch_id) $q->where('branch_id',$request->branch_id);
        if ($request->status) $q->where('status',$request->status);
        if ($request->from) $q->whereDate('created_at','>=',$request->from);
        if ($request->to) $q->whereDate('created_at','<=',$request->to);

        $list = $q->paginate(20);
        return $this->ReturnData('orders',$list,'Orders list');
    }

}
