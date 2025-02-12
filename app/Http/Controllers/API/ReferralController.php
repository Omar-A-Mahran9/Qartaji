<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\ReferralRepository;
use Illuminate\Support\Facades\Request;

class ReferralController extends Controller
{
    protected $referralRepo;

    public function __construct(ReferralRepository $referralRepo)
    {
        $this->referralRepo = $referralRepo;
    }

    // Generate Referral Link
    public function generateReferralLink()
    {
        $user = auth()->user();
        return response()->json([
            'referral_link' => $this->referralRepo->generateReferralLink($user)
        ]);
    }

    // Store a Referral when a new user registers
    public function storeReferral(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string|exists:users,referral_code',
            'referred_user_id' => 'required|integer|exists:users,id'
        ]);

        $referrer = User::where('referral_code', $request->referral_code)->first();
        $this->referralRepo->storeReferral($referrer->id, $request->referred_user_id, $request->referral_code);

        return response()->json(['message' => 'Referral stored successfully']);
    }

    // Get All Referrals for a User
    public function getUserReferrals()
    {
        $user = auth()->user();
        return response()->json([
            'referrals' => $this->referralRepo->getUserReferrals($user->id)
        ]);
    }
}
