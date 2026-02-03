<?php

namespace Xentixar\WorkflowManager\Support;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;
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

    /**
     * Get fillable and common attribute names for a model class (for rule condition field dropdown).
     * Includes direct attributes and one-level relationship paths (e.g. user.name, budget.amount).
     *
     * @param  class-string  $modelClass  FQCN or morph class of the model
     * @return array<string, string> Map of field name => label
     */
    public static function getFillableFieldsForModel(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $instance = new $modelClass;
            $result = [];

            $own = array_values(array_unique(array_merge(
                $instance->getFillable(),
                ['id', 'created_at', 'updated_at']
            )));
            foreach ($own as $key) {
                $result[$key] = self::fieldLabel($key);
            }

            foreach (self::getRelationshipFieldsForModel($modelClass) as $path => $label) {
                $result[$path] = $label;
            }

            return $result;
        } catch (Throwable $e) {
            Log::error('Error while getting fillable fields: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get one-level relationship paths (relation.attribute) for a model class.
     *
     * @param  class-string  $modelClass
     * @return array<string, string> Map of path => label
     */
    public static function getRelationshipFieldsForModel(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $instance = new $modelClass;
            $result = [];
            $relationNames = self::getRelationNames($instance);

            foreach ($relationNames as $relationName) {
                try {
                    $relation = $instance->{$relationName}();
                    if (! $relation instanceof Relation) {
                        continue;
                    }
                    $related = $relation->getRelated();
                    $relatedFillable = array_merge(
                        $related->getFillable(),
                        ['id', 'created_at', 'updated_at']
                    );
                    foreach (array_unique($relatedFillable) as $attr) {
                        $path = $relationName.'.'.$attr;
                        $result[$path] = self::fieldLabel($relationName).' → '.self::fieldLabel($attr);
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }

            return $result;
        } catch (Throwable $e) {
            Log::error('Error while getting relationship fields: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Discover relation method names on the model via reflection (no required params, returns Relation).
     *
     * @return array<int, string>
     */
    private static function getRelationNames(object $instance): array
    {
        $names = [];
        $ref = new ReflectionClass($instance);

        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }
            $name = $method->getName();
            if (str_starts_with($name, 'get') && str_ends_with($name, 'Attribute')) {
                continue;
            }
            try {
                $return = $method->invoke($instance);
                if ($return instanceof Relation) {
                    $names[] = $name;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return $names;
    }

    private static function fieldLabel(string $key): string
    {
        return str_replace('_', ' ', ucfirst($key));
    }
}
