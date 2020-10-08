<?php

namespace App\Models;

use App\Http\Traits\TraitsModel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use TraitsModel;

    /**
     * @var array
     */
    public static $sex = [
        -1 => '保密',
        0 => '男',
        1 => '女'
    ];

    /**
     * @var array
     */
    public static $status = [
        0 => '禁用',
        1 => '正常'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account', 'username', 'password', 'tel', 'role_names', 'email', 'sex', 'status', 'created_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Description:
     * User: Vijay
     * Date: 2019/7/28
     * Time: 15:31
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|string
     */
    public static function create(array $attributes = [])
    {
        try {
            DB::beginTransaction();
            Validator::make(
                $attributes, [
                    'username' => 'required|string|max:255',
                    'account' => 'required|max:100|unique:admins',
                    'password' => 'required|string|min:5',
                ]
            )->validate();
            $attributes['password'] = Hash::make($attributes['password']);
            $res = static::query()->create($attributes);
            if (isset($attributes['role_names']) && !empty($attributes['role_names'])) {
                $res->assignRole($attributes['role_names']);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }

    /**
     * Description:
     * User: Vijay
     * Date: 2019/6/10
     * Time: 22:12
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        try {
            DB::beginTransaction();
            if (isset($attributes['password'])) {
                Validator::make(
                    $attributes, [
                        'password' => 'required|string|min:5',
                    ]
                )->validate();
                $attributes['password'] = Hash::make($attributes['password']);
            }
            if (isset($attributes['account'])) {
                Validator::make(
                    $attributes, [
                        'account' => Rule::unique('admins')->where(function ($query) {
                            return $query->where('id', '!=', $this->id);
                        })
                    ]
                )->validate();
            }
            if (isset($attributes['role_names']) && !empty($attributes['role_names']) && $this->role_names != $attributes['role_names']) {
                if (!empty($this->role_names)) {
                    $role_names = explode(',', $this->role_names);
                    foreach ($role_names as $remove_role) {
                        $this->removeRole($remove_role);
                    }
                }
                $this->assignRole($attributes['role_names']);
            }
            parent::update($attributes, $options); // TODO: Change the autogenerated stub
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Description:
     * User: Vijay
     * Date: 2019/7/29
     * Time: 22:59
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function setmypass(array $attributes = [], array $options = [])
    {
        if ($attributes['oldPassword'] === $attributes['password']) {
            $this->error = '新密码与旧密码相同';
            return false;
        }
        if ($attributes['repassword'] !== $attributes['password']) {
            $this->error = '两次密码不一样';
            return false;
        }
        if (!Hash::check($attributes['oldPassword'], $this->password)) {
            $this->error = '原密码错误';
            return false;
        }
        try {
            Validator::make(
                $attributes, [
                    'password' => 'required|string|min:5',
                ]
            )->validate();
            $attributes['password'] = Hash::make($attributes['password']);
            parent::update($attributes, $options); // TODO: Change the autogenerated stub
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error = $e->getMessage();
            return false;
        }
    }
}