<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\TableController;
use App\Http\Controllers\API\BranchController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\SessionController;
use App\Http\Controllers\API\EmployeesController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\SessionItemController;
use App\Http\Controllers\API\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::prefix('auth')->group(function () {
    Route::post('register',[AuthController::class,'register']);
    Route::post('login',[AuthController::class, 'login']);
    Route::get('showall',[AuthController::class, 'showall']);
    Route::get('showpag',[AuthController::class, 'showpag']);


    //////////////////////////////////////////////
    Route::middleware('auth:api')->group(function () {
        Route::post('logout',[AuthController::class, 'logout']);
        Route::get('me',[AuthController::class, 'me']);
        Route::post('resetpassword',[AuthController::class, 'resetUserPasswordByAdmin']);
        Route::post('deleteuser',[AuthController::class, 'delete']);

        Route::post('/toggle-user-status', [AuthController::class, 'toggleUserStatus']);

    });


});
Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeesController::class, 'index']);
    Route::post('store', [EmployeesController::class, 'store']);
    Route::get('/show/{id}', [EmployeesController::class, 'show']);
    Route::post('/update/{id}', [EmployeesController::class, 'update']);
    Route::get('/delete/{id}', [EmployeesController::class, 'destroy']);
    Route::get('/employees/{id}/salary', [EmployeesController::class, 'getMonthlySalary']);
    Route::get('/monthlyReport', [EmployeesController::class, 'monthlyReport']);

});

Route::prefix('attendance')->group(function(){
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('/manual', [AttendanceController::class, 'manualEntry']); // admin
    Route::get('/', [AttendanceController::class, 'index']);
    Route::get('/monthly-report', [AttendanceController::class, 'monthlyReport']);

});

Route::prefix('transaction')->group(function(){
    Route::post('/add', [TransactionController::class, 'add']);
    Route::get('/', [TransactionController::class, 'index']);
    Route::get('/monthly-summary', [TransactionController::class, 'monthlySummary']);
});

Route::prefix('branches')->group(function(){
    Route::get('/',[BranchController::class, 'index']);
    Route::post('/store',[BranchController::class, 'store']);
    Route::get('/show/{id}',[BranchController::class, 'show']);
    Route::post('/update/{id}',[BranchController::class, 'update']);
    Route::get('/destroy/{id}',[BranchController::class, 'destroy']);

});


Route::prefix('tables')->group(function(){
    Route::get('/',[TableController::class, 'index']);
    Route::post('/store',[TableController::class, 'store']);
    Route::get('/show/{id}',[TableController::class, 'show']);
    Route::post('/update/{id}',[TableController::class, 'update']);
    Route::get('/destroy/{id}',[TableController::class, 'destroy']);

});

Route::prefix('booking')->group(function(){
    Route::get('/',[BookingController::class, 'index']);
    Route::post('/store',[BookingController::class, 'store']);
    Route::get('/show/{id}',[BookingController::class, 'show']);
    Route::post('/update/{id}',[BookingController::class, 'update']);
    Route::get('/destroy/{id}',[BookingController::class, 'destroy']);

});


Route::prefix('items')->group(function(){
    Route::get('/',[ItemController::class, 'index']);
    Route::post('/store',[ItemController::class, 'store']);
    Route::get('/show/{id}',[ItemController::class, 'show']);
    Route::post('/update/{id}',[ItemController::class, 'update']);
    Route::get('/destroy/{id}',[ItemController::class, 'destroy']);

});

Route::prefix('session-items')->group(function(){
    Route::get('/',[SessionItemController::class, 'index']);
    Route::post('/store',[SessionItemController::class, 'store']);
    // Route::get('/show/{id}',[SessionItemController::class, 'show']);
    Route::post('/update/{id}',[SessionItemController::class, 'update']);
    Route::get('/destroy/{id}',[SessionItemController::class, 'destroy']);

});

Route::prefix('sessions')->group(function(){
    Route::get('/', [SessionController::class, 'index']);
    Route::get('/show/{id}', [SessionController::class, 'show']);
    Route::post('/store', [SessionController::class, 'store']);
    Route::post('/pause/{id}', [SessionController::class, 'pause']);
    Route::post('/resume/{id}', [SessionController::class, 'resume']);
    Route::post('/end/{id}', [SessionController::class, 'end']);
    Route::get('/{id}/details', [SessionController::class, 'details']);


});


Route::prefix('invoices')->group(function(){
    // Invoice routes
    Route::get('/', [InvoiceController::class, 'index']);
    Route::get('/{id}', [InvoiceController::class, 'show']);
    Route::post('/sessions/{id}/invoice', [InvoiceController::class, 'generateInvoice']);
    Route::post('/{id}/pay', [InvoiceController::class, 'pay']);
    Route::post('/{id}', [InvoiceController::class, 'cancel']);

});

Route::prefix('orders')->group(function(){
    Route::post('/takeaway', [OrderController::class,'createTakeaway']);
    Route::post('/{order}/items', [OrderController::class,'addItem']);
    Route::post('/items/{id}', [OrderController::class,'updateItem']);
    Route::get('/items/{id}', [OrderController::class,'removeItem']);
    Route::post('/{order}/pay', [OrderController::class,'payOrder']);
    Route::get('/', [OrderController::class,'index']);
    Route::get('/{order}', [OrderController::class,'show']);
});

