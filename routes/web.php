<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\SchemaController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ConnectionController;
use App\Http\Controllers\Admin\SyncJobController;
use App\Http\Controllers\Admin\ReplicationController;

Route::redirect('/', '/admin/login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['admin.auth'])->group(function () {
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

        Route::get('/sync-jobs', [SyncJobController::class, 'index'])->name('sync-jobs.index');
        Route::post('/sync-jobs/run', [SyncJobController::class, 'run'])->name('sync-jobs.run');

        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup/create', [BackupController::class, 'create'])->name('backup.create');
        Route::post('/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');

        Route::get('/health', [HealthController::class, 'index'])->name('health.index');
        Route::post('/health/check', [HealthController::class, 'runCheck'])->name('health.check');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/update', [SettingController::class, 'update'])->name('settings.update');
    });
});