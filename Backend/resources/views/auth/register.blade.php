<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DemandLead</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <!-- Logo & Welcome -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl mb-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Start your free trial</h1>
            <p class="text-gray-600">Discover rising demand, capture leads, close deals</p>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <p class="text-sm text-red-700 font-medium">Please correct the errors below:</p>
                    <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf

                <!-- Organization Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Organization Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Organization Name -->
                        <div class="md:col-span-2">
                            <label for="organization_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Organization Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                id="organization_name" 
                                type="text" 
                                name="organization_name" 
                                value="{{ old('organization_name') }}"
                                required 
                                autofocus
                                autocomplete="organization"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('organization_name') ? 'border-red-300' : '' }}"
                                placeholder="Acme Corporation">
                            @error('organization_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Industry -->
                        <div>
                            <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">
                                Industry <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="industry" 
                                name="industry"
                                required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('industry') ? 'border-red-300' : '' }}">
                                <option value="">Select Industry</option>
                                <option value="technology" {{ old('industry') == 'technology' ? 'selected' : '' }}>Technology</option>
                                <option value="healthcare" {{ old('industry') == 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                                <option value="finance" {{ old('industry') == 'finance' ? 'selected' : '' }}>Finance</option>
                                <option value="retail" {{ old('industry') == 'retail' ? 'selected' : '' }}>Retail</option>
                                <option value="manufacturing" {{ old('industry') == 'manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                                <option value="education" {{ old('industry') == 'education' ? 'selected' : '' }}>Education</option>
                                <option value="real_estate" {{ old('industry') == 'real_estate' ? 'selected' : '' }}>Real Estate</option>
                                <option value="consulting" {{ old('industry') == 'consulting' ? 'selected' : '' }}>Consulting</option>
                                <option value="marketing" {{ old('industry') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                <option value="other" {{ old('industry') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('industry')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Country -->
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                Country <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="country" 
                                name="country"
                                required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('country') ? 'border-red-300' : '' }}">
                                <option value="">Select Country</option>
                                <option value="KE" {{ old('country') == 'KE' ? 'selected' : '' }}>🇰🇪 Kenya</option>
                                <option value="TZ" {{ old('country') == 'TZ' ? 'selected' : '' }}>🇹🇿 Tanzania</option>
                                <option value="UG" {{ old('country') == 'UG' ? 'selected' : '' }}>🇺🇬 Uganda</option>
                                <option value="RW" {{ old('country') == 'RW' ? 'selected' : '' }}>🇷🇼 Rwanda</option>
                                <option value="ZA" {{ old('country') == 'ZA' ? 'selected' : '' }}>🇿🇦 South Africa</option>
                                <option value="NG" {{ old('country') == 'NG' ? 'selected' : '' }}>🇳🇬 Nigeria</option>
                                <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>🇺🇸 United States</option>
                                <option value="GB" {{ old('country') == 'GB' ? 'selected' : '' }}>🇬🇧 United Kingdom</option>
                                <option value="CA" {{ old('country') == 'CA' ? 'selected' : '' }}>🇨🇦 Canada</option>
                                <option value="AU" {{ old('country') == 'AU' ? 'selected' : '' }}>🇦🇺 Australia</option>
                                <option value="IN" {{ old('country') == 'IN' ? 'selected' : '' }}>🇮🇳 India</option>
                                <option value="SG" {{ old('country') == 'SG' ? 'selected' : '' }}>🇸🇬 Singapore</option>
                                <option value="AE" {{ old('country') == 'AE' ? 'selected' : '' }}>🇦🇪 UAE</option>
                                <option value="other" {{ old('country') == 'other' ? 'selected' : '' }}>🌍 Other</option>
                            </select>
                            @error('country')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200"></div>

                <!-- Personal Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Your Account Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                id="name" 
                                type="text" 
                                name="name" 
                                value="{{ old('name') }}"
                                required
                                autocomplete="name"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('name') ? 'border-red-300' : '' }}"
                                placeholder="John Doe">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}"
                                required
                                autocomplete="username"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('email') ? 'border-red-300' : '' }}"
                                placeholder="john@company.com">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <input 
                                id="password" 
                                type="password" 
                                name="password"
                                required
                                autocomplete="new-password"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('password') ? 'border-red-300' : '' }}"
                                placeholder="Min. 8 characters">
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters</p>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm Password <span class="text-red-500">*</span>
                            </label>
                            <input 
                                id="password_confirmation" 
                                type="password" 
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Repeat password">
                        </div>
                    </div>
                </div>

                <!-- Terms & Submit -->
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input 
                                id="terms" 
                                name="terms" 
                                type="checkbox" 
                                required
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="text-gray-600">
                                I agree to the <a href="#" class="text-blue-600 hover:text-blue-500 font-medium">Terms of Service</a> and <a href="#" class="text-blue-600 hover:text-blue-500 font-medium">Privacy Policy</a>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <span>Create Account</span>
                        <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500 transition">
                        Sign in
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                © {{ date('Y') }} DemandLead. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
