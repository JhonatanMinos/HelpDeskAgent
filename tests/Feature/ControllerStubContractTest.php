<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProjectController;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Device;
use App\Models\Project;
use Illuminate\Support\Facades\Route;

test('unregistered controller stubs retain their null result without changing models', function (string $controllerClass, string $method, array $arguments) {
    $controller = new $controllerClass;
    $models = array_filter($arguments, fn ($argument) => $argument instanceof Device || $argument instanceof Project);
    $before = array_map(fn ($model) => $model->getAttributes(), $models);

    expect($controller->$method(...$arguments))->toBeNull();
    expect(array_map(fn ($model) => $model->getAttributes(), $models))->toBe($before);
    expect(array_map(fn ($model) => $model->exists, $models))->each->toBeFalse();

    $action = $controllerClass.'@'.$method;
    expect(collect(Route::getRoutes())->contains(fn ($route) => $route->getActionName() === $action))->toBeFalse();
})->with([
    'device index' => fn () => [DeviceController::class, 'index', []],
    'device create' => fn () => [DeviceController::class, 'create', []],
    'device store' => fn () => [DeviceController::class, 'store', [new StoreDeviceRequest]],
    'device show' => fn () => [DeviceController::class, 'show', [new Device]],
    'device edit' => fn () => [DeviceController::class, 'edit', [new Device]],
    'device update' => fn () => [DeviceController::class, 'update', [new UpdateDeviceRequest, new Device]],
    'device destroy' => fn () => [DeviceController::class, 'destroy', [new Device]],
    'project edit' => fn () => [ProjectController::class, 'edit', [new Project]],
    'project update' => fn () => [ProjectController::class, 'update', [new UpdateProjectRequest, new Project]],
    'project destroy' => fn () => [ProjectController::class, 'destroy', [new Project]],
]);
