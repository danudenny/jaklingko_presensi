@extends('modules.admin.layouts.main')

@section('title', 'Profile')
@section('header', 'Profile')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="space-y-6">
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
            </div>
            
            <div class="p-6">
                @include('modules.admin.profile.partials.update-profile-information-form')
            </div>
        </div>
        
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Update Password</h3>
            </div>
            
            <div class="p-6">
                @include('modules.admin.profile.partials.update-password-form')
            </div>
        </div>
        
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Delete Account</h3>
            </div>
            
            <div class="p-6">
                @include('modules.admin.profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection 