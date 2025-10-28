<?php

namespace App\Http\Controllers\API;

use Exception;
use Carbon\Carbon;
use App\Models\Table;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    use GeneralTrait;

    public function index(Request $request)
    {
         try
        {
        $q = Booking::with(['branch','table']);

        if ($request->branch_id) $q->where('branch_id', $request->branch_id);
        if ($request->table_id) $q->where('table_id', $request->table_id);
        if ($request->status) $q->where('status', $request->status);
        if ($request->from) $q->where('start_time', '>=', $request->from);
        if ($request->to) $q->where('end_time', '<=', $request->to);

        $list = $q->orderBy('start_time','desc')->paginate(20);
        return $this->ReturnData('booking',$list,'get 20 bokking');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function store(Request $request)
    {
       $rules = [
            'branch_id'=>'required|exists:branches,id',
            'table_id'=>'required|exists:tables,id',
            'customer_name'=>'nullable|string',
            'customer_phone'=>'nullable|string',
            'start_time'=>'required|date',
            'end_time'=>'required|date|after:start_time',
            'status'=>'nullable|in:pending,confirmed'
        ];
         $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
        // check table belongs to branch
        $table = Table::find($request->table_id);
        if (!$table || $table->branch_id != $request->branch_id) {
            return $this->ReturnError('error','Table does not belong to the specified branch');
        }

        // conflict check: overlapping bookings (exclude cancelled/completed)
        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);

        $conflict = Booking::where('table_id', $request->table_id)
            ->whereNotIn('status', ['cancelled','completed'])
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end->subSecond()])
                  ->orWhereBetween('end_time', [$start->addSecond(), $end])
                  ->orWhere(function($q2) use ($start,$end){
                      $q2->where('start_time','<',$start)->where('end_time','>',$end);
                  });
            })->exists();

        if ($conflict) {
            return $this->ReturnError('error','Time conflict: table already booked for this period');

        }

        DB::beginTransaction();
        try {
            // price snapshot
            $pricePerHour = (float) ($table->price_per_hour ?? 0);
            $minHours = (float) ($table->min_hours ?? 0);
            $durationHours = round(($end->diffInMinutes($start) / 60), 2);
            if ($minHours > 0 && $durationHours < $minHours) $durationHours = $minHours;

            $totalPrice = round($pricePerHour * $durationHours, 2);

            $booking = Booking::create([
                'branch_id' => $request->branch_id,
                'table_id' => $request->table_id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'status' => $request->status ?? 'pending',
                'start_time' => $start->toDateTimeString(),
                'end_time' => $end->toDateTimeString(),
                'price_per_hour' => $pricePerHour,
                'total_hours' => $durationHours,
                'total_price' => $totalPrice,
                'notes' => $request->notes ?? null,
            ]);

            // if booking is confirmed, mark table as reserved
            if (($request->status ?? 'pending') === 'confirmed') {
                $table->status = 'reserved';
                $table->save();
            }

            DB::commit();
            return $this->ReturnData('booking',$booking,'تم الحجز بنجاح');
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function show($id)
    {
         try
        {
            $b = Booking::with(['branch','table'])->find($id);
            if (!$b) return $this->ReturnError('error','لا يوجد ذلك الحجز');
            return $this->ReturnData('booking',$b,'get this booking');
        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $b = Booking::find($id);
        if (!$b) return $this->ReturnError('error','لا يوجد ذلك الحجز');

         $rules = [
                'status'=>'nullable|in:pending,confirmed,in_progress,completed,cancelled',
                'start_time'=>'nullable|date',
                'end_time'=>'nullable|date|after:start_time',
                'customer_name'=>'nullable|string',
                'customer_phone'=>'nullable|string',
                'notes'=>'nullable|string',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
        DB::beginTransaction();
        try {
            // if times changed -> conflict check
            if ($request->start_time || $request->end_time) {
                $start = Carbon::parse($request->start_time ?? $b->start_time);
                $end = Carbon::parse($request->end_time ?? $b->end_time);

                $conflict = Booking::where('table_id', $b->table_id)
                    ->where('id','<>',$b->id)
                    ->whereNotIn('status', ['cancelled','completed'])
                    ->where(function($q) use ($start, $end) {
                        $q->whereBetween('start_time', [$start, $end->subSecond()])
                          ->orWhereBetween('end_time', [$start->addSecond(), $end])
                          ->orWhere(function($q2) use ($start,$end){
                              $q2->where('start_time','<',$start)->where('end_time','>',$end);
                          });
                    })->exists();

                if ($conflict) {
                    return response()->json(['status'=>false,'message'=>'Time conflict with another booking'],409);
                }

                // recalc price snapshot
                $table = $b->table;
                $pricePerHour = (float) ($table->price_per_hour ?? 0);
                $minHours = (float) ($table->min_hours ?? 0);
                $durationHours = round((Carbon::parse($end)->diffInMinutes(Carbon::parse($start)) / 60), 2);
                if ($minHours > 0 && $durationHours < $minHours) $durationHours = $minHours;
                $totalPrice = round($pricePerHour * $durationHours, 2);

                $b->price_per_hour = $pricePerHour;
                $b->total_hours = $durationHours;
                $b->total_price = $totalPrice;
                $b->start_time = $start->toDateTimeString();
                $b->end_time = $end->toDateTimeString();
            }

            // status change handling (update table.status accordingly)
            if ($request->status) {
                $old = $b->status;
                $new = $request->status;
                $b->status = $new;

                $table = $b->table;
                if ($new === 'confirmed') {
                    $table->status = 'reserved';
                    $table->save();
                } elseif ($new === 'in_progress') {
                    $table->status = 'busy';
                    $table->save();
                } elseif (in_array($new, ['cancelled','completed'])) {
                    // if no other active bookings for this table at this time -> free
                    $other = Booking::where('table_id',$b->table_id)
                        ->whereNotIn('status',['cancelled','completed'])
                        ->where('id','<>',$b->id)
                        ->exists();
                    if (!$other) {
                        $table->status = 'free';
                        $table->save();
                    }
                }
            }

            // update other fields
            foreach (['customer_name','customer_phone','notes'] as $f) {
                if ($request->has($f)) $b->$f = $request->input($f);
            }

            $b->save();
            DB::commit();
            return $this->ReturnData('booking',$b,'تم التحديث بنجاح');
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }


     public function destroy($id)
    {
        $b = Booking::find($id);
        if (!$b) return $this->ReturnError('error','لا يوجد ذلك الحجز');

        DB::beginTransaction();
        try {
            $table = $b->table;
            $b->delete();

            // free table if no other active bookings
            $other = Booking::where('table_id',$table->id)
                ->whereNotIn('status',['cancelled','completed'])
                ->exists();
            if (!$other) {
                $table->status = 'free';
                $table->save();
            }

            DB::commit();
            return $this->ReturnSuccess('200','تم مسح الحجز و تحويل الحاله بنجاح');
        } catch (Exception $ex) {
            DB::rollBack();
          return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

}
