<?php

namespace Xentixar\WorkflowManager\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Xentixar\WorkflowManager\Contracts\WorkflowsContract;

final class Helper
{
    /**
     * Get available models in app that implement the Workflows interface.
     *
     * @return array<string, string> $models
     */
    public static function getAvailableModels(): array
    {
        $models = [];
        $modelsPath = app_path('Models');
        $modelFiles = File::allFiles($modelsPath);

        foreach ($modelFiles as $modelFile) {
            $class = 'App\\Models\\'.Str::replaceLast('.php', '', $modelFile->getFilename());

            if (! class_exists($class)) {
                continue;
            }

            try {
                $implementedInterfaces = class_implements($class);
                $morphClass = (new $class)->getMorphClass(); // @phpstan-ignore-line

                if (in_array(WorkflowsContract::class, $implementedInterfaces)) {
                    $models[$morphClass] = class_basename($class);
                }
            } catch (Throwable $e) {
                Log::error('Error while checking model interfaces: '.$e->getMessage());

                continue;
            }
        }

        return $models;
    }

    /**
     * Get available states from enum class
     *
     * @return array<string, string>
     */
    public static function getStatesFromEnum(string $enumClass): array
    {
        if (! class_exists($enumClass)) {
            return [];
        }

        $states = [];

        foreach ($enumClass::cases() as $state) {
            if (method_exists($state, 'getLabel')) {
                $label = $state->getLabel();
            }

            $states[$state->value] = $label ?? $state->value;
        }

        return $states;
    }
}
