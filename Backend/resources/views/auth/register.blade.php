<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Organization Name -->
        <div>
            <x-input-label for="organization_name" :value="__('Organization Name')" />
            <x-text-input id="organization_name" class="block mt-1 w-full" type="text" name="organization_name" :value="old('organization_name')" required autofocus autocomplete="organization" />
            <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
        </div>

        <!-- Industry -->
        <div class="mt-4">
            <x-input-label for="industry" :value="__('Industry')" />
            <select id="industry" name="industry" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
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
            <x-input-error :messages="$errors->get('industry')" class="mt-2" />
        </div>

        <!-- Country -->
        <div class="mt-4">
            <x-input-label for="country" :value="__('Country')" />
            <select id="country" name="country" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="">Select Country</option>
                <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>United States</option>
                <option value="CA" {{ old('country') == 'CA' ? 'selected' : '' }}>Canada</option>
                <option value="GB" {{ old('country') == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                <option value="AU" {{ old('country') == 'AU' ? 'selected' : '' }}>Australia</option>
                <option value="DE" {{ old('country') == 'DE' ? 'selected' : '' }}>Germany</option>
                <option value="FR" {{ old('country') == 'FR' ? 'selected' : '' }}>France</option>
                <option value="IN" {{ old('country') == 'IN' ? 'selected' : '' }}>India</option>
                <option value="SG" {{ old('country') == 'SG' ? 'selected' : '' }}>Singapore</option>
                <option value="AE" {{ old('country') == 'AE' ? 'selected' : '' }}>United Arab Emirates</option>
                <option value="other" {{ old('country') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            <x-input-error :messages="$errors->get('country')" class="mt-2" />
        </div>

        <hr class="my-6">

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Your Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
