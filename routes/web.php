<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ConnectionController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\SchemaController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SyncJobController;
use App\Http\Controllers\Admin\ReplicationController;
use App\Http\Controllers\Admin\DataCompareController;

    Route::redirect('/', '/admin/login');


    Route::prefix('data-compare')->name('data-compare.')->group(function () {
        Route::get('/', [DataCompareController::class, 'index'])->name('index');
        Route::post('/run', [DataCompareController::class, 'run'])->name('run');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/connections', [ConnectionController::class, 'index'])->name('connections.index');
        Route::post('/connections/test-source', [ConnectionController::class, 'testSource'])->name('connections.test-source');
        Route::post('/connections/test-destination', [ConnectionController::class, 'testDestination'])->name('connections.test-destination');
        Route::post('/connections/save', [ConnectionController::class, 'save'])->name('connections.save');

        Route::get('/schema', [SchemaController::class, 'index'])->name('schema.index');
        Route::post('/schema/snapshot', [SchemaController::class, 'snapshot'])->name('schema.snapshot');
        Route::post('/schema/diff', [SchemaController::class, 'diff'])->name('schema.diff');
        Route::post('/schema/apply', [SchemaController::class, 'apply'])->name('schema.apply');

        Route::get('/replication', [ReplicationController::class, 'index'])->name('replication.index');
        Route::post('/replication/store', [ReplicationController::class, 'store'])->name('replication.store');
        Route::post('/replication/start', [ReplicationController::class, 'start'])->name('replication.start');
        Route::post('/replication/stop', [ReplicationController::class, 'stop'])->name('replication.stop');
        Route::get('/replication/status', [ReplicationController::class, 'status'])->name('replication.status');

        Route::prefix('sync-jobs')->name('sync-jobs.')->group(function () {
            Route::get('/', [SyncJobController::class, 'index'])->name('index');
            Route::post('/save-mapping', [SyncJobController::class, 'saveMapping'])->name('save-mapping');
            Route::post('/run', [SyncJobController::class, 'run'])->name('run');
        });

        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::get('/health', [HealthController::class, 'index'])->name('health.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/save', [SettingController::class, 'save'])->name('settings.save');
            });
});