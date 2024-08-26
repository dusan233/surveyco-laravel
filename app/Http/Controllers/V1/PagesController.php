<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\CopyPageRequest;
use App\Http\Requests\V1\MovePageRequest;
use App\Http\Resources\V1\SurveyPageResource;
use App\Models\SurveyPage;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Services\Handlers\SurveyPage\CopySurveyPageHandler;
use App\Services\Handlers\SurveyPage\DeleteSurveyPageHandler;
use App\Services\Handlers\SurveyPage\DTO\CopySurveyPageDTO;
use App\Services\Handlers\SurveyPage\DTO\MoveSurveyPageDTO;
use App\Services\Handlers\SurveyPage\MoveSurveyPageHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PagesController extends BaseController
{
    private SurveyPageRepositoryInterface $surveyPageRepository;
    private CopySurveyPageHandler $copySurveyPageHandler;
    private DeleteSurveyPageHandler $deleteSurveyPageHandler;
    private MoveSurveyPageHandler $moveSurveyPageHandler;

    public function __construct(
        SurveyPageRepositoryInterface $surveyPageRepository,
        CopySurveyPageHandler $copySurveyPageHandler,
        DeleteSurveyPageHandler $deleteSurveyPageHandler,
        MoveSurveyPageHandler $moveSurveyPageHandler,
    ) {
        $this->surveyPageRepository = $surveyPageRepository;
        $this->copySurveyPageHandler = $copySurveyPageHandler;
        $this->deleteSurveyPageHandler = $deleteSurveyPageHandler;
        $this->moveSurveyPageHandler = $moveSurveyPageHandler;
    }
    public function destroy(Request $request, string $page_id)
    {
        $surveyPage = $this->surveyPageRepository->findById($page_id);

        if ($request->user()->cannot("delete", [SurveyPage::class, $surveyPage])) {
            throw new UnauthorizedException();
        }

        $this->deleteSurveyPageHandler->handle($page_id);

        return $this->deletedResponse();
    }

    public function copy(CopyPageRequest $request, $source_page_id)
    {
        $surveyPage = $this->surveyPageRepository->findById($source_page_id);

        if ($request->user()->cannot("copy", [SurveyPage::class, $surveyPage])) {
            throw new UnauthorizedException();
        }

        $copyPageData = $request->validated();

        $newPage = $this->copySurveyPageHandler->handle(new CopySurveyPageDTO(
            $surveyPage->survey_id,
            $source_page_id,
            $copyPageData["targetPageId"],
            $copyPageData["position"]
        ));

        return $this->resourceResponse(SurveyPageResource::class, $newPage, Response::HTTP_CREATED);
    }


    public function move(MovePageRequest $request, $source_page_id)
    {
        $surveyPage = $this->surveyPageRepository->findById($source_page_id);

        if ($request->user()->cannot("move", [SurveyPage::class, $surveyPage])) {
            throw new UnauthorizedException();
        }

        $movePageData = $request->validated();

        $updatedPage = $this->moveSurveyPageHandler->handle(new MoveSurveyPageDTO(
            $surveyPage->survey_id,
            $source_page_id,
            $movePageData["targetPageId"],
            $movePageData["position"],
        ));

        return $this->resourceResponse(SurveyPageResource::class, $updatedPage);
    }
}
