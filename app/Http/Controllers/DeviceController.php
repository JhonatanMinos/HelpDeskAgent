<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Models\Device;

/**
 * \App\Http\Controllers\DeviceController
 *
 * @description Sus acciones CRUD permanecen como stubs vacíos y el recurso no está registrado en las rutas actuales.
 */
class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeviceRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Device $device): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Device $device): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeviceRequest $request, Device $device): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Device $device): void
    {
        //
    }
}
