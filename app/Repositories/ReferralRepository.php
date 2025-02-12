<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\Referral;
use App\Models\User;

class ReferralRepository extends Repository
{
    public static function model()
    {
        return Referral::class;
    }

    /**
     * Generate a referral link for a user.
     */
    public function generateReferralLink(User $user)
    {
        return url('?ref=' . $user->referral_code);
    }

    /**
     * Store a referral when a user signs up using a referral code.
     */
    public static function storeReferral($referrerId, $referredUserId, $referralCode)
    {
        return Referral::create([
            'referrer_id' => $referrerId,
            'referred_user_id' => $referredUserId,
            'referral_code' => $referralCode,
            'rewarded' => false
        ]);
    }

    /**
     * Get all referrals made by a user.
     */
    public function getUserReferrals($userId)
    {
        return Referral::where('referrer_id', $userId)->get();
    }

    /**
     * Check if a user was referred by someone.
     */
    public function checkIfUserWasReferred($userId)
    {
        return Referral::where('referred_user_id', $userId)->first();
    }

    /**
     * Mark a referral as rewarded after the referred user completes an order.
     */
    public function markReferralAsRewarded($referredUserId)
    {
        return Referral::where('referred_user_id', $referredUserId)
            ->update(['rewarded' => true]);
    }
}
