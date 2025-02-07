<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\VerifyManage;

class VerifyManageRepository extends Repository
{
    public static function model()
    {
        return VerifyManage::class;
    }

    /**
     * @param  Request  $request
     */
    public static function updateOrCreateByRequest($request): VerifyManage
    {
        return self::query()->updateOrCreate(
            ['id' => 1],
            [
                'register_otp' => $request->register_otp ? 1 : 0,
                'register_otp_type' => $request->register_otp_type ?? 'phone',
                'forgot_otp_type' => $request->forgot_otp_type ?? 'phone',
            ]
        );
    }
}
