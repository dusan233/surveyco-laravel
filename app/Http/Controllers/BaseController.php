<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response as ResponseCodes;
use Illuminate\Support\Facades\Response;

abstract class BaseController extends Controller
{

    protected function resourceResponse(
        string $resource,
        Collection|LengthAwarePaginator|Paginator|Model $data,
        int $statusCode = ResponseCodes::HTTP_OK,
        array $meta = [],
        array $headers = [],
        array $errors = [],
    ): JsonResponse {
        if ($data instanceof Collection || $data instanceof Paginator) {
            $additional = array_filter([
                'meta' => $meta ?? null,
                'errors' => $errors ?? null,
            ]);
            $response = ($resource::collection($data)->additional($additional))
                ->response()
                ->setStatusCode($statusCode);
        } else {
            $response = (new $resource($data))
                ->response()
                ->setStatusCode($statusCode);
        }

        foreach ($headers as $header => $value) {
            $response->header($header, $value);
        }

        return $response;
    }

    protected function noContentResponse(int $status = ResponseCodes::HTTP_NO_CONTENT): LaravelResponse
    {

        return Response::noContent($status);
    }

    protected function deletedResponse(): LaravelResponse
    {
        return Response::noContent();
    }

    protected function notFoundResponse(): LaravelResponse
    {
        return Response::noContent(ResponseCodes::HTTP_NOT_FOUND);
    }
}
