<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreImageRequest;
use Symfony\Component\HttpFoundation\Response;

class SurveyImagesController extends Controller
{
    public function store(StoreImageRequest $request)
    {
        $data = $request->validated();

        $file = $request->file('image');
        $path = $file->store('images', 'public');

        $url = env("APP_URL") . "/storage" . "/" . $path;

        return response()->json([
            "fileType" => $file->getMimeType(),
            "url" => $url
        ], Response::HTTP_CREATED);
    }
}
