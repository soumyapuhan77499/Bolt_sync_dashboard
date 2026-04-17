@extends('admin.layouts.app')

@section('title', 'Admin Login - Bolt Sync Admin')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-6 text-white p-5" style="background: linear-gradient(135deg, #0b1b4d 0%, #1f3b8a 100%);">
                        <span class="badge rounded-pill px-3 py-2 mb-4" style="background: rgba(255,255,255,0.12);">
                            BOLT SYNC ADMIN
                        </span>

                        <h1 class="fw-bold mb-4" style="font-size: 3rem; line-height: 1.15;">
                            Control database sync, schema diff, backups and replication from one dashboard.
                        </h1>

                        <p class="fs-5 opacity-75 mb-4">
                            This admin panel is designed to manage Source Supabase and Destination Database connections with a clean operational workflow.
                        </p>
                    </div>

                    <div class="col-md-6 bg-white p-5">
                        <h2 class="fw-bold mb-2">Welcome back</h2>
                        <p class="text-muted mb-4">Login with your admin account to continue.</p>

                        @if(session('error'))
                            <div class="alert alert-danger rounded-3">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="alert alert-success rounded-3">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger rounded-3">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.login.submit') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    value="{{ old('email') }}"
                                    class="form-control form-control-lg rounded-3"
                                    placeholder="Enter admin email"
                                    required
                                >
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-control form-control-lg rounded-3"
                                    placeholder="Enter password"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3">
                                Login to Dashboard
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection