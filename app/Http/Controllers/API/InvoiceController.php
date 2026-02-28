<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Invoice;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;

class InvoiceController extends Controller
{
    use GeneralTrait;

  public function generateInvoice($sessionId ,Request $request)
{
    try
    {
        $session = Session::with(['sessionItems.item', 'table'])->findOrFail($sessionId);

        $itemsTotal = $session->sessionItems->sum(fn($item) => $item->total);
        $sessionTotal = $session->total_amount ?? 0;
        $total = $itemsTotal + $sessionTotal;

        $invoice = Invoice::create([
            'session_id'    => $sessionId,
            'session_total' => $sessionTotal,
            'items_total'   => $itemsTotal,
            'final_total'   => $total,
            'payment_method'=>$request->payment_method ?? 'cash',
            'status'        => 'pending',
        ]);

        // رجّع الفاتورة ومعاها تفاصيل الجلسة و الطلبات
        $invoice->load(['session.sessionItems.item', 'session.table']);

        return $this->ReturnData("invoice", [
            'invoice' => $invoice,
            'session' => $session
        ], "Invoice generated successfully");
    }
    catch(Exception $ex)
    {
        return $this->ReturnError($ex->getCode(),$ex->getMessage());
    }
}


    /** ✅ عرض كل الفواتير */
    public function index()
    {
         try
        {
            $invoices = Invoice::with('session.table.branch')->orderBy('id', 'desc')->paginate(20);
             return $this->ReturnData("invoices", $invoices,'get 20 invoice..');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }

    /** ✅ عرض فاتورة واحدة */
    public function show($id)
    {
         try
        {
             $invoice = Invoice::with('session.sessionItems')->findOrFail($id);
            return $this->ReturnData("invoice", $invoice,'get this invoice..');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }

    /** ✅ تأكيد الدفع */
    public function pay($id)
    {

         try
        {
            $invoice = Invoice::with(['session.sessionItems.item'])->findOrFail($id);
            if ($invoice->status === 'paid') {
            return $this->ReturnError(400, "Invoice already paid");
            }

            $paymentMethod = $request->payment_method ?? 'cash';

            // Update invoice
            $invoice->update([
                'status' => 'paid',
                'payment_method' => $paymentMethod
            ]);
            // $invoice->update(['status' => 'paid']);

                  $invoice->session->update([
                 'status' => 'ended'
            ]);
             $invoice->load('session.sessionItems.item');
            return $this->ReturnData("Invoice paid successfully", [
                'invoice' => $invoice->fresh(),
                'session' => $invoice->session,
                'items'   => $invoice->session->sessionItems
            ], "success");

        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }

    /** ❌ إلغاء الفاتورة */
    public function cancel($id)
    {
         try
        {
            $invoice = Invoice::findOrFail($id);
            $invoice->delete();

            return $this->ReturnSuccess('200',"Invoice cancelled");
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }

    }
}
