<?php

namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use GeneralTrait;

    public function register(Request $request)
    {
        try {
            $rules = [
                'name' => 'string|required',
                'username' => 'string|required|unique:users',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
                'com_password' => 'required|min:6',
                'permissions' => 'nullable|array',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            if ($request->password != $request->com_password) {
                return $this->ReturnError(400, 'من فضلك تأكد من كلمة المرور');
            }

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'permissions' => is_string($request->permissions)? json_decode($request->permissions, true): $request->permissions,
            ]);


            $token = JWTAuth::fromUser($user);

            $data = [
                'token' => $token,
                'user' => $user,
            ];

            return $this->ReturnData('user', $data, 'تم حفظ المستخدم بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }


     public function login(Request $request)
    {
        try {
            $rules = [
                'identifier' => 'required|string',
                'password' => 'required|string',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }


            $user = User::where('email', $request->identifier)
                ->orWhere('username', $request->identifier)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->ReturnError(401, 'Invalid credentials');
            }

            if (isset($user->status) && $user->status !== true) {
            return $this->ReturnError(403, 'تم تعطيل حسابك الرجاء التواصل مع الادمن');
            }

            $token = JWTAuth::fromUser($user);

            $data = [
                'token' => $token,
                'user' => $user,
            ];

            return $this->ReturnData('user', $data, 'تم تسجيل الدخول بنجاح');
        } catch (Exception $ex) {
            return $this->ReturnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function logout(){
        JWTAuth::invalidate(JWTAuth::getToken());
        return $this->ReturnSuccess('200','تم تسجيل الخروج بنجاح');
    }

    public function me(){
        $me=auth()->user();
        return $this->ReturnData('me',$me,'Hi,..');
    }

    public function resetUserPasswordByAdmin(Request $request)
    {
        try
        {
            $authuser=auth()->user();
            if(!$authuser || !isset($authuser->permissions)){
                return $this->ReturnError('error','Unauthorized');
            }

            $permissions= is_string($authuser->permissions)?json_decode($authuser->permissions,true):$authuser->permissions;

            $isAdmin=collect($permissions)->contains(function($perm){
                return isset($perm['role']) && $perm['role'] === 'admin';
            });

            if(!$isAdmin){
                return $this->ReturnError('Error','لا يوجد لديك صلاحيه الاستخدام');
            }

        $rules = [
            'user_id' => 'required|exists:users,id',
            'new_password' => 'required|min:6|confirmed',
            ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $user = User::find($request->user_id);
        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->ReturnSuccess('200',"تم تحديث كلمة المرور بنجاح للمستخدم :{$user->username}");

        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function delete(Request $request)
    {
        try
        {
            $authuser=auth()->user();
            if(!$authuser || !isset($authuser->permissions)){
                return $this->ReturnError('error','Unauthorized');
            }

            $permissions= is_string($authuser->permissions)?json_decode($authuser->permissions,true):$authuser->permissions;

            $isAdmin=collect($permissions)->contains(function($perm){
                return isset($perm['role']) && $perm['role'] === 'admin';
            });

            if(!$isAdmin){
                return $this->ReturnError('Error','لا يوجد لديك صلاحيه الاستخدام');
            }

        $rules = [
            'user_id' => 'required|exists:users,id',
            ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $user = User::find($request->user_id);
        $user->delete();

        return $this->ReturnSuccess('200',"تم مسح المستخدم بنجاح");

        }
        catch(Exception $ex)
        {
            return $this->ReturnError($ex->getCode(),$ex->getMessage());
        }
    }

    public function showall()
    {
       $users = User::latest()->get();
        return $this->ReturnData('users',$users,'done');
    }

    public function showpag()
    {
       $users = User::latest()->paginate(20);
        return $this->ReturnData('users',$users,'done');
    }

    public function toggleUserStatus(Request $request)
    {
    try {
        $authuser = auth()->user();
        if(!$authuser || !isset($authuser->permissions)){
            return $this->ReturnError('error','Unauthorized');
        }

        $permissions = is_string($authuser->permissions) ? json_decode($authuser->permissions, true) : $authuser->permissions;

        $isAdmin = collect($permissions)->contains(function($perm){
            return isset($perm['role']) && $perm['role'] === 'admin';
        });

        if (!$isAdmin) {
            return $this->ReturnError('Error','لا يوجد لديك صلاحيه الاستخدام');
        }

        $rules = [
            'user_id' => 'required|exists:users,id',
            'active' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $user = User::find($request->user_id);
        $user->active = $request->active;
        $user->save();

        $msg = $request->active === true ? "تم تفعيل المستخدم بنجاح" : "تم تعطيل المستخدم بنجاح";

        return $this->ReturnSuccess('200', $msg);

    } catch (Exception $ex) {
        return $this->ReturnError($ex->getCode(), $ex->getMessage());
    }
}

}
