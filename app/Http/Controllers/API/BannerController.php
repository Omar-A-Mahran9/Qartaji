<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Repositories\BannerRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Get all banners
     */
    public function index()
    {
        $banners = BannerRepository::query()->whereNull('shop_id')->active()->get();
        return $this->json('all banners', [
            'banners' => BannerResource::collection($banners),
        ]);
    }

    public function store(BannerRequest $request): JsonResponse
    {
        // Call storeByRequest to process the API request
        $banner = BannerRepository::storeByRequest($request);

        return response()->json([
            'message' => 'Banner created successfully!',
            'banner' => $banner
        ], 201);
    }

    public function update(BannerRequest $request, Banner $banner): JsonResponse
    {
        // Update the banner using the repository method
        BannerRepository::updateByRequest($request, $banner);

        return response()->json([
            'message' => 'Banner updated successfully!',
            'banner' => $banner
        ]);
    }
    public function statusToggle(Banner $banner): JsonResponse
    {
        // Toggle the status
        $banner->update([
            'status' => !$banner->status,
        ]);

        return response()->json([
            'message' => 'Banner status updated successfully!',
            'banner' => [
                'id' => $banner->id,
                'status' => $banner->status ? 'active' : 'inactive',
            ]
        ]);
    }
    public function destroy(Banner $banner): JsonResponse
    {
        // Delete the media file if it exists
        $media = $banner->media;
        if ($media && Storage::exists($media->src)) {
            Storage::delete($media->src);
        }

        // Delete the banner and its associated media
        $banner->delete();
        if ($media) {
            $media->delete();
        }

        return response()->json([
            'message' => 'Banner deleted successfully!',
            'banner_id' => $banner->id
        ], 200);
    }
}
