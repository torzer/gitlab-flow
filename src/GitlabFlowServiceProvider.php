<?php

namespace Torzer\GitlabFlow;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider to be registered in your config/app.php
 *
 * @author nunomazer
 */
class GitlabFlowServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        $this->publishes([
            __DIR__ . '/config/gitlab-flow.php' => config_path('gitlab-flow.php')
                ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Torzer\GitlabFlow\Console\Commands\GitlabMR::class,
                \Torzer\GitlabFlow\Console\Commands\GitlabAcceptMR::class,
                \Torzer\GitlabFlow\Console\Commands\GitlabRun::class,
            ]);
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register() {
        $this->mergeConfigFrom(
                __DIR__ . '/config/gitlab-flow.php', 'gitlab-flow'
        );
    }

}
