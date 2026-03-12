@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Settings</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf
                        
                        @foreach($groupedSettings as $group => $settings)
                            <div class="mb-5">
                                <h4 class="mb-3">{{ ucfirst($group) }} Settings</h4>
                                <div class="row">
                                    @foreach($settings as $key => $setting)
                                        <div class="col-md-6 mb-3">
                                            <label for="setting-{{ $key }}" class="form-label">
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                                            </label>
                                            
                                            @if($setting['type'] === 'boolean')
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="settings[{{ $key }}][key]" value="{{ $key }}">
                                                    <input type="hidden" name="settings[{{ $key }}][type]" value="{{ $setting['type'] }}">
                                                    <input 
                                                        type="checkbox" 
                                                        class="form-check-input" 
                                                        id="setting-{{ $key }}"
                                                        name="settings[{{ $key }}][value]"
                                                        value="1"
                                                        {{ $setting['typed_value'] ? 'checked' : '' }}
                                                    >
                                                    <label class="form-check-label" for="setting-{{ $key }}">
                                                        {{ $setting['typed_value'] ? 'Enabled' : 'Disabled' }}
                                                    </label>
                                                </div>
                                            @elseif($setting['type'] === 'json')
                                                <input type="hidden" name="settings[{{ $key }}][key]" value="{{ $key }}">
                                                <input type="hidden" name="settings[{{ $key }}][type]" value="{{ $setting['type'] }}">
                                                <textarea 
                                                    class="form-control" 
                                                    id="setting-{{ $key }}"
                                                    name="settings[{{ $key }}][value]"
                                                    rows="3"
                                                    placeholder="Enter JSON data"
                                                >{{ json_encode($setting['typed_value'], JSON_PRETTY_PRINT) }}</textarea>
                                                <small class="form-text text-muted">JSON format expected</small>
                                            @elseif($setting['type'] === 'integer')
                                                <input type="hidden" name="settings[{{ $key }}][key]" value="{{ $key }}">
                                                <input type="hidden" name="settings[{{ $key }}][type]" value="{{ $setting['type'] }}">
                                                <input 
                                                    type="number" 
                                                    class="form-control" 
                                                    id="setting-{{ $key }}"
                                                    name="settings[{{ $key }}][value]"
                                                    value="{{ $setting['typed_value'] }}"
                                                    step="1"
                                                >
                                            @elseif($setting['type'] === 'float')
                                                <input type="hidden" name="settings[{{ $key }}][key]" value="{{ $key }}">
                                                <input type="hidden" name="settings[{{ $key }}][type]" value="{{ $setting['type'] }}">
                                                <input 
                                                    type="number" 
                                                    class="form-control" 
                                                    id="setting-{{ $key }}"
                                                    name="settings[{{ $key }}][value]"
                                                    value="{{ $setting['typed_value'] }}"
                                                    step="0.01"
                                                >
                                            @else
                                                <input type="hidden" name="settings[{{ $key }}][key]" value="{{ $key }}">
                                                <input type="hidden" name="settings[{{ $key }}][type]" value="{{ $setting['type'] }}">
                                                <input 
                                                    type="text" 
                                                    class="form-control" 
                                                    id="setting-{{ $key }}"
                                                    name="settings[{{ $key }}][value]"
                                                    value="{{ $setting['typed_value'] }}"
                                                    maxlength="1000"
                                                >
                                            @endif
                                            
                                            @if($setting['is_public'])
                                                <span class="badge bg-info">Public</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
