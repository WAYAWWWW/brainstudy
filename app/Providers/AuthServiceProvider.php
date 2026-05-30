<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\ExamSet;
use App\Policies\ExamSetPolicy;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Models\StudentExam;
use App\Policies\StudentExamPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ExamSet::class => ExamSetPolicy::class,
        User::class => UserPolicy::class,
        StudentExam::class => StudentExamPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
