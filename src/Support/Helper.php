<?php

namespace Xentixar\WorkflowManager\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class Helper
{
    /**
     * Get available models in app that implement the Workflows interface.
     * 
     * @return array $models
     */
    public static function getAvailableModels(): array
    {
        $models = [];
        $modelsPath = app_path('Models');
        $modelFiles = File::allFiles($modelsPath);

        foreach ($modelFiles as $modelFile) {
            $class = 'App\\Models\\' . Str::replaceLast('.php', '', $modelFile->getFilename());

            if (!class_exists($class)) {
                continue;
            }

            $implementedInterfaces = class_implements($class);

            if (in_array(\Xentixar\WorkflowManager\Contracts\Workflows::class, $implementedInterfaces)) {
                $models[$class] = $class;
            }
        }

        return $models;
    }
}
