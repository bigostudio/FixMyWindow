<?php

namespace App\Providers;

use App\Repositories\Eloquent\EloquentCustomerRepository;
use App\Repositories\Eloquent\EloquentEmailVerificationTokenRepository;
use App\Repositories\Eloquent\EloquentEnquiryRepository;
use App\Repositories\Eloquent\EloquentOtpRepository;
use App\Repositories\Eloquent\EloquentProjectAssignmentRepository;
use App\Repositories\Eloquent\EloquentSurveyRepository;
use App\Repositories\Eloquent\EloquentProjectTimelineRepository;
use App\Repositories\Eloquent\EloquentRefreshTokenRepository;
use App\Repositories\Eloquent\EloquentServiceRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Repositories\Interfaces\EmailVerificationTokenRepositoryInterface;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Repositories\Interfaces\OtpRepositoryInterface;
use App\Repositories\Interfaces\ProjectAssignmentRepositoryInterface;
use App\Repositories\Interfaces\SurveyRepositoryInterface;
use App\Repositories\Interfaces\ProjectTimelineRepositoryInterface;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Repositories\Interfaces\ServiceRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, EloquentCustomerRepository::class);
        $this->app->bind(EmailVerificationTokenRepositoryInterface::class, EloquentEmailVerificationTokenRepository::class);
        $this->app->bind(OtpRepositoryInterface::class, EloquentOtpRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RefreshTokenRepositoryInterface::class, EloquentRefreshTokenRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, EloquentServiceRepository::class);
        $this->app->bind(EnquiryRepositoryInterface::class, EloquentEnquiryRepository::class);
        $this->app->bind(ProjectAssignmentRepositoryInterface::class, EloquentProjectAssignmentRepository::class);
        $this->app->bind(SurveyRepositoryInterface::class, EloquentSurveyRepository::class);
        $this->app->bind(ProjectTimelineRepositoryInterface::class, EloquentProjectTimelineRepository::class);
    }

    public function boot(): void {}
}
