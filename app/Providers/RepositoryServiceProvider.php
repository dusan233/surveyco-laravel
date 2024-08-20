<?php

namespace App\Providers;

use App\Repositories\Eloquent\QuestionRepository;
use App\Repositories\Eloquent\SurveyCollectorRepository;
use App\Repositories\Eloquent\SurveyPageRepository;
use App\Repositories\Eloquent\SurveyRepository;
use App\Repositories\Eloquent\SurveyResponseRepository;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\SurveyCollectorRepositoryInterface;
use App\Repositories\Interfaces\SurveyPageRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Repositories\Interfaces\SurveyResponseRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    private static array $interfaceToConcreteMap = [
        SurveyRepositoryInterface::class => SurveyRepository::class,
        SurveyPageRepositoryInterface::class => SurveyPageRepository::class,
        SurveyCollectorRepositoryInterface::class => SurveyCollectorRepository::class,
        SurveyResponseRepositoryInterface::class => SurveyResponseRepository::class,
        QuestionRepositoryInterface::class => QuestionRepository::class,
    ];
    /**
     * Register services.
     */
    public function register(): void
    {
        foreach (self::$interfaceToConcreteMap as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
