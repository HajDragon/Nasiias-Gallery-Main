# Prompt: Replace Account Nav Item with Avatar Dropdown

**Date:** 2026-11-12

## Prompt

Replace the existing "Account" text navigation item in the main header/navbar with a user avatar dropdown menu using existing shadcn/ui components, providing authenticated users access to Settings and Logout actions.

## Response

Done. Replaced the "Account" text nav item with an avatar dropdown (shadcn DropdownMenu + Avatar) that shows:

- Settings (links to /settings/profile)
- Log out (POSTs to logout route)

When not logged in, it shows the original "Account" link to /login

## Files Modified

- `resources/js/components/Header.tsx` — Replaced static Account Link with DropdownMenu trigger using Avatar, AvatarImage, AvatarFallback, DropdownMenuContent, DropdownMenuLabel, DropdownMenuItem, DropdownMenuSeparator; added Settings/LogOut icons from lucide-react; uses useInitials hook for fallback

---

# Prompt: Add Email Verification Warning to Account Deletion

**Date:** 2026-11-12

## Prompt

Add a warning in the account deletion box checking if the user's account is verified. If not verified, show a warning that they need to verify their email in order to delete their account, gray out the delete button in this state, and add a link to resend verification email.

## Response

Done. Updated the DeleteUser component to:

- Check `user.email_verified_at` to determine verification status
- Show amber warning banner with "Email not verified" message and link to resend verification email when unverified
- Disable the delete button (both trigger and confirm) when unverified with `disabled` and `aria-disabled` attributes
- Disable password input in confirmation dialog when unverified
- Pass `user` prop from Profile page to DeleteUser component

## Files Modified

- `resources/js/components/delete-user.tsx` — Added verification check, warning banner with Link to resend verification, disabled states on button trigger, dialog confirm button, and password input
- `resources/js/pages/settings/profile.tsx` — Pass `user={auth.user}` prop to DeleteUser component

---

# Prompt: Remove Email Verification Requirement for Appearance Settings

**Date:** 2026-11-12

## Prompt

Remove the email verification requirement (`verified` middleware) for the Appearance settings tab (`/settings/appearance`). Users should be able to access appearance settings without verifying their email address.

## Response

Done. Moved the Appearance route out of the `verified` middleware group into its own `auth` only middleware group. The route now only requires authentication, not email verification.

## Files Modified

- `routes/settings.php` — Moved `Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit')` from the `auth, verified` middleware group to a separate `auth` middleware group

---

# Prompt: Add Ukrainian Language Support with Locale Switching

**Date:** 2026-11-13

## Prompt

Add Ukrainian (`uk`) language support to the Laravel/Inertia/React application while keeping English (`en`) as the default and fallback locale.

Requirements:

- Provide professional, natural-sounding Ukrainian translations rather than machine-generated placeholders, covering the public pages, authentication screens, settings pages, two-factor authentication and passkey UI, validation messages, authentication messages, password reset messages and pagination.
- Add a language-selection control to the profile settings so authenticated users can switch the interface language.
- Persist the selected locale per authenticated user and retain it for guests through the session, so the choice survives navigation and future visits.
- Share the translation dictionaries and available locales with the frontend through Inertia instead of introducing an additional i18n dependency.
- Ensure all existing interface strings and flash messages are localized, and that missing keys fall back to English.
- Cover the locale behavior with feature tests and verify the implementation with the project's type-check, formatting, build and test commands.

## Response

Done. Implemented end-to-end Ukrainian localization with per-user locale persistence.

- Backend: added a `users.locale` migration, a `SetLocale` middleware, a `LocaleController` and a `POST /locale/{locale}` route. The active locale resolves from the authenticated user, then the session, then the `locale` cookie.
- Shared the translation dictionaries, fallback dictionary and available locales with Inertia, and added a `useTranslation()` hook with English fallback.
- Added complete `lang/uk/ui.php` dictionaries and official Laravel-Lang Ukrainian translations for validation, authentication, passwords and pagination.
- Localized the public site, authentication pages, settings pages, two-factor authentication and passkey components, shared layout chrome and shadcn UI primitives.
- Added an EN/UA language switcher to the public header and a language selector to profile settings; the selected locale is validated and persisted with the profile update.
- Added `tests/Feature/LocaleTest.php` covering locale switching, persistence and translation sharing.

## Files Modified

- `database/migrations/2026_11_13_000000_add_locale_to_users_table.php` — Added the nullable `users.locale` column
- `app/Models/User.php` — Added `locale` to the model's property documentation and fillable attributes
- `app/Http/Middleware/SetLocale.php` — Resolves the active locale from the user, session or cookie
- `app/Http/Controllers/LocaleController.php` — Handles locale switching for guests and authenticated users
- `bootstrap/app.php` — Registered the `SetLocale` middleware in the web stack
- `config/app.php` — Added the list of available locales
- `app/Http/Middleware/HandleInertiaRequests.php` — Shared translations, fallback translations, the current locale and available locales with Inertia
- `app/Concerns/ProfileValidationRules.php` — Added locale validation rules
- `app/Http/Controllers/Settings/ProfileController.php` — Persists the profile locale to the session and `locale` cookie
- `routes/web.php` — Added the `POST /locale/{locale}` route
- `lang/en/ui.php`, `lang/uk/ui.php` — Complete English and Ukrainian UI dictionaries with matching keys
- `lang/uk/validation.php`, `lang/uk/auth.php`, `lang/uk/passwords.php`, `lang/uk/pagination.php` — Ukrainian framework-level translations
- `lang/en/app.php`, `lang/uk/app.php` — Localized flash messages
- `resources/js/lib/i18n.ts` — Added the `useTranslation()` hook with key replacement and English fallback
- `resources/js/components/language-switcher.tsx` — Added the EN/UA language switcher component
- `resources/js/components/Header.tsx` — Integrated the language switcher into the public header
- `resources/js/pages/settings/profile.tsx` — Added the profile language selector
- `resources/js/types/global.d.ts`, `resources/js/types/auth.ts` — Added locale and translation typings
- `resources/js/pages/*`, `resources/js/components/*`, `resources/js/layouts/*` — Replaced hard-coded copy with translation keys across public, authentication and settings screens
- `tests/Feature/LocaleTest.php` — Added feature coverage for locale switching, persistence and shared translations

---

# Prompt: Integrate Sky Toggle React Component with shadcn and Tailwind

**Date:** 2026-11-16

## Prompt

You are given a task to integrate an existing React component in the codebase

The codebase should support:

- shadcn project structure
- Tailwind CSS
- Typescript

If it doesn't, provide instructions on how to setup project via shadcn CLI, install Tailwind or Typescript.

Determine the default path for components and styles.
If default path for components is not /components/ui, provide instructions on why it's important to create this folder
Copy-paste this component to /components/ui folder:

```tsx
sky - toggle.tsx;
import React from 'react';
import styled from 'styled-components';

const Switch = () => {
    return (
        <StyledWrapper>
            <label className="theme-switch">
                <input type="checkbox" className="theme-switch__checkbox" />
                <div className="theme-switch__container">
                    <div className="theme-switch__clouds" />
                    <div className="theme-switch__stars-container">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 144 55"
                            fill="none"
                        >
                            <path
                                fillRule="evenodd"
                                clipRule="evenodd"
                                d="M135.831 3.00688C135.055 3.85027 134.111 4.29946 133 4.35447C134.111 4.40947 135.055 4.85867 135.831 5.71123C136.607 6.55462 136.996 7.56303 136.996 8.72727C136.996 7.95722 137.172 7.25134 137.525 6.59129C137.886 5.93124 138.372 5.39954 138.98 5.00535C139.598 4.60199 140.268 4.39114 141 4.35447C139.88 4.2903 138.936 3.85027 138.16 3.00688C137.384 2.16348 136.996 1.16425 136.996 0C136.996 1.16425 136.607 2.16348 135.831 3.00688ZM31 23.3545C32.1114 23.2995 33.0551 22.8503 33.8313 22.0069C34.6075 21.1635 34.9956 20.1642 34.9956 19C34.9956 20.1642 35.3837 21.1635 36.1599 22.0069C36.9361 22.8503 37.8798 23.2903 39 23.3545C38.2679 23.3911 37.5976 23.602 36.9802 24.0053C36.3716 24.3995 35.8864 24.9312 35.5248 25.5913C35.172 26.2513 34.9956 26.9572 34.9956 27.7273C34.9956 26.563 34.6075 25.5546 33.8313 24.7112C33.0551 23.8587 32.1114 23.4095 31 23.3545ZM0 36.3545C1.11136 36.2995 2.05513 35.8503 2.83131 35.0069C3.6075 34.1635 3.99559 33.1642 3.99559 32C3.99559 33.1642 4.38368 34.1635 5.15987 35.0069C5.93605 35.8503 6.87982 36.2903 8 36.3545C7.26792 36.3911 6.59757 36.602 5.98015 37.0053C5.37155 37.3995 4.88644 37.9312 4.52481 38.5913C4.172 39.2513 3.99559 39.9572 3.99559 40.7273C3.99559 39.563 3.6075 38.5546 2.83131 37.7112C2.05513 36.8587 1.11136 36.4095 0 36.3545ZM56.8313 24.0069C56.0551 24.8503 55.1114 25.2995 54 25.3545C55.1114 25.4095 56.0551 25.8587 56.8313 26.7112C57.6075 27.5546 57.9956 28.563 57.9956 29.7273C57.9956 28.9572 58.172 28.2513 58.5248 27.5913C58.8864 26.9312 59.3716 26.3995 59.9802 26.0053C60.5976 25.602 61.2679 25.3911 62 25.3545C60.8798 25.2903 59.9361 24.8503 59.1599 24.0069C58.3837 23.1635 57.9956 22.1642 57.9956 21C57.9956 22.1642 57.6075 23.1635 56.8313 24.0069ZM81 25.3545C82.1114 25.2995 83.0551 24.8503 83.8313 24.0069C84.6075 23.1635 84.9956 22.1642 84.9956 21C84.9956 22.1642 85.3837 23.1635 86.1599 24.0069C86.9361 24.8503 87.8798 25.2903 89 25.3545C88.2679 25.3911 87.5976 25.602 86.9802 26.0053C86.3716 26.3995 85.8864 26.9312 85.5248 27.5913C85.172 28.2513 84.9956 28.9572 84.9956 29.7273C84.9956 28.563 84.6075 27.5546 83.8313 26.7112C83.0551 25.8587 82.1114 25.4095 81 25.3545ZM136 36.3545C137.111 36.2995 138.055 35.8503 138.831 35.0069C139.607 34.1635 139.996 33.1642 139.996 32C139.996 33.1642 140.384 34.1635 141.16 35.0069C141.936 35.8503 142.88 36.2903 144 36.3545C143.268 36.3911 142.598 36.602 141.98 37.0053C141.372 37.3995 140.886 37.9312 140.525 38.5913C140.172 39.2513 139.996 39.9572 139.996 40.7273C139.996 39.563 139.607 38.5546 138.831 37.7112C138.055 36.8587 137.111 36.4095 136 36.3545ZM101.831 49.0069C101.055 49.8503 100.111 50.2995 99 50.3545C100.111 50.4095 101.055 50.8587 101.831 51.7112C102.607 52.5546 102.996 53.563 102.996 54.7273C102.996 53.9572 103.172 53.2513 103.525 52.5913C103.886 51.9312 104.372 51.3995 104.98 51.0053C105.598 50.602 106.268 50.3911 107 50.3545C105.88 50.2903 104.936 49.8503 104.16 49.0069C103.384 48.1635 102.996 47.1642 102.996 46C102.996 47.1642 102.607 48.1635 101.831 49.0069Z"
                                fill="currentColor"
                            />
                        </svg>
                    </div>
                    <div className="theme-switch__circle-container">
                        <div className="theme-switch__sun-moon-container">
                            <div className="theme-switch__moon">
                                <div className="theme-switch__spot" />
                                <div className="theme-switch__spot" />
                                <div className="theme-switch__spot" />
                            </div>
                        </div>
                    </div>
                </div>
            </label>
        </StyledWrapper>
    );
};

const StyledWrapper = styled.div`
    .theme-switch {
        --toggle-size: 30px;
        /* the size is adjusted using font-size,
       this is not transform scale,
       so you can choose any size */
        --container-width: 5.625em;
        --container-height: 2.5em;
        --container-radius: 6.25em;
        /* radius 0 - minecraft mode :) */
        --container-light-bg: #3d7eae;
        --container-night-bg: #1d1f2c;
        --circle-container-diameter: 3.375em;
        --sun-moon-diameter: 2.125em;
        --sun-bg: #ecca2f;
        --moon-bg: #c4c9d1;
        --spot-color: #959db1;
        --circle-container-offset: calc(
            (var(--circle-container-diameter) - var(--container-height)) / 2 *
                -1
        );
        --stars-color: #fff;
        --clouds-color: #f3fdff;
        --back-clouds-color: #aacadf;
        --transition: 0.5s cubic-bezier(0, -0.02, 0.4, 1.25);
        --circle-transition: 0.3s cubic-bezier(0, -0.02, 0.35, 1.17);
    }

    .theme-switch,
    .theme-switch *,
    .theme-switch *::before,
    .theme-switch *::after {
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-size: var(--toggle-size);
    }

    .theme-switch__container {
        width: var(--container-width);
        height: var(--container-height);
        background-color: var(--container-light-bg);
        border-radius: var(--container-radius);
        overflow: hidden;
        cursor: pointer;
        -webkit-box-shadow:
            0em -0.062em 0.062em rgba(0, 0, 0, 0.25),
            0em 0.062em 0.125em rgba(255, 255, 255, 0.94);
        box-shadow:
            0em -0.062em 0.062em rgba(0, 0, 0, 0.25),
            0em 0.062em 0.125em rgba(255, 255, 255, 0.94);
        -webkit-transition: var(--transition);
        -o-transition: var(--transition);
        transition: var(--transition);
        position: relative;
    }

    .theme-switch__container::before {
        content: '';
        position: absolute;
        z-index: 1;
        inset: 0;
        -webkit-box-shadow:
            0em 0.05em 0.187em rgba(0, 0, 0, 0.25) inset,
            0em 0.05em 0.187em rgba(0, 0, 0, 0.25) inset;
        box-shadow:
            0em 0.05em 0.187em rgba(0, 0, 0, 0.25) inset,
            0em 0.05em 0.187em rgba(0, 0, 0, 0.25) inset;
        border-radius: var(--container-radius);
    }

    .theme-switch__checkbox {
        display: none;
    }

    .theme-switch__circle-container {
        width: var(--circle-container-diameter);
        height: var(--circle-container-diameter);
        background-color: rgba(255, 255, 255, 0.1);
        position: absolute;
        left: var(--circle-container-offset);
        top: var(--circle-container-offset);
        border-radius: var(--container-radius);
        -webkit-box-shadow:
            inset 0 0 0 3.375em rgba(255, 255, 255, 0.1),
            inset 0 0 0 3.375em rgba(255, 255, 255, 0.1),
            0 0 0 0.625em rgba(255, 255, 255, 0.1),
            0 0 0 1.25em rgba(255, 255, 255, 0.1);
        box-shadow:
            inset 0 0 0 3.375em rgba(255, 255, 255, 0.1),
            inset 0 0 0 3.375em rgba(255, 255, 255, 0.1),
            0 0 0 0.625em rgba(255, 255, 255, 0.1),
            0 0 0 1.25em rgba(255, 255, 255, 0.1);
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-transition: var(--circle-transition);
        -o-transition: var(--circle-transition);
        transition: var(--circle-transition);
        pointer-events: none;
    }

    .theme-switch__sun-moon-container {
        pointer-events: auto;
        position: relative;
        z-index: 2;
        width: var(--sun-moon-diameter);
        height: var(--sun-moon-diameter);
        margin: auto;
        border-radius: var(--container-radius);
        background-color: var(--sun-bg);
        -webkit-box-shadow:
            0.062em 0.062em 0.062em 0em rgba(254, 255, 239, 0.61) inset,
            0em -0.062em 0.062em 0em #a1872a inset;
        box-shadow:
            0.062em 0.062em 0.062em 0em rgba(254, 255, 239, 0.61) inset,
            0em -0.062em 0.062em 0em #a1872a inset;
        -webkit-filter: drop-shadow(0.062em 0.125em 0.125em rgba(0, 0, 0, 0.25))
            drop-shadow(0em 0.062em 0.125em rgba(0, 0, 0, 0.25));
        filter: drop-shadow(0.062em 0.125em 0.125em rgba(0, 0, 0, 0.25))
            drop-shadow(0em 0.062em 0.125em rgba(0, 0, 0, 0.25));
        overflow: hidden;
        -webkit-transition: var(--transition);
        -o-transition: var(--transition);
        transition: var(--transition);
    }

    .theme-switch__moon {
        -webkit-transform: translateX(100%);
        -ms-transform: translateX(100%);
        transform: translateX(100%);
        width: 100%;
        height: 100%;
        background-color: var(--moon-bg);
        border-radius: inherit;
        -webkit-box-shadow:
            0.062em 0.062em 0.062em 0em rgba(254, 255, 239, 0.61) inset,
            0em -0.062em 0.062em 0em #969696 inset;
        box-shadow:
            0.062em 0.062em 0.062em 0em rgba(254, 255, 239, 0.61) inset,
            0em -0.062em 0.062em 0em #969696 inset;
        -webkit-transition: var(--transition);
        -o-transition: var(--transition);
        transition: var(--transition);
        position: relative;
    }

    .theme-switch__spot {
        position: absolute;
        top: 0.75em;
        left: 0.312em;
        width: 0.75em;
        height: 0.75em;
        border-radius: var(--container-radius);
        background-color: var(--spot-color);
        -webkit-box-shadow: 0em 0.0312em 0.062em rgba(0, 0, 0, 0.25) inset;
        box-shadow: 0em 0.0312em 0.062em rgba(0, 0, 0, 0.25) inset;
    }

    .theme-switch__spot:nth-of-type(2) {
        width: 0.375em;
        height: 0.375em;
        top: 0.937em;
        left: 1.375em;
    }

    .theme-switch__spot:nth-last-of-type(3) {
        width: 0.25em;
        height: 0.25em;
        top: 0.312em;
        left: 0.812em;
    }

    .theme-switch__clouds {
        width: 1.25em;
        height: 1.25em;
        background-color: var(--clouds-color);
        border-radius: var(--container-radius);
        position: absolute;
        bottom: -0.625em;
        left: 0.312em;
        -webkit-box-shadow:
            0.937em 0.312em var(--clouds-color),
            -0.312em -0.312em var(--back-clouds-color),
            1.437em 0.375em var(--clouds-color),
            0.5em -0.125em var(--back-clouds-color),
            2.187em 0 var(--clouds-color),
            1.25em -0.062em var(--back-clouds-color),
            2.937em 0.312em var(--clouds-color),
            2em -0.312em var(--back-clouds-color),
            3.625em -0.062em var(--clouds-color),
            2.625em 0em var(--back-clouds-color),
            4.5em -0.312em var(--clouds-color),
            3.375em -0.437em var(--back-clouds-color),
            4.625em -1.75em 0 0.437em var(--clouds-color),
            4em -0.625em var(--back-clouds-color),
            4.125em -2.125em 0 0.437em var(--back-clouds-color);
        box-shadow:
            0.937em 0.312em var(--clouds-color),
            -0.312em -0.312em var(--back-clouds-color),
            1.437em 0.375em var(--clouds-color),
            0.5em -0.125em var(--back-clouds-color),
            2.187em 0 var(--clouds-color),
            1.25em -0.062em var(--back-clouds-color),
            2.937em 0.312em var(--clouds-color),
            2em -0.312em var(--back-clouds-color),
            3.625em -0.062em var(--clouds-color),
            2.625em 0em var(--back-clouds-color),
            4.5em -0.312em var(--clouds-color),
            3.375em -0.437em var(--back-clouds-color),
            4.625em -1.75em 0 0.437em var(--clouds-color),
            4em -0.625em var(--back-clouds-color),
            4.125em -2.125em 0 0.437em var(--back-clouds-color);
        -webkit-transition: 0.5s cubic-bezier(0, -0.02, 0.4, 1.25);
        -o-transition: 0.5s cubic-bezier(0, -0.02, 0.4, 1.25);
        transition: 0.5s cubic-bezier(0, -0.02, 0.4, 1.25);
    }

    .theme-switch__stars-container {
        position: absolute;
        color: var(--stars-color);
        top: -100%;
        left: 0.312em;
        width: 2.75em;
        height: auto;
        -webkit-transition: var(--transition);
        -o-transition: var(--transition);
        transition: var(--transition);
    }

    /* actions */

    .theme-switch__checkbox:checked + .theme-switch__container {
        background-color: var(--container-night-bg);
    }

    .theme-switch__checkbox:checked
        + .theme-switch__container
        .theme-switch__circle-container {
        left: calc(
            100% - var(--circle-container-offset) -
                var(--circle-container-diameter)
        );
    }

    .theme-switch__checkbox:checked
        + .theme-switch__container
        .theme-switch__circle-container:hover {
        left: calc(
            100% - var(--circle-container-offset) -
                var(--circle-container-diameter) - 0.187em
        );
    }

    .theme-switch__circle-container:hover {
        left: calc(var(--circle-container-offset) + 0.187em);
    }

    .theme-switch__checkbox:checked
        + .theme-switch__container
        .theme-switch__moon {
        -webkit-transform: translate(0);
        -ms-transform: translate(0);
        transform: translate(0);
    }

    .theme-switch__checkbox:checked
        + .theme-switch__container
        .theme-switch__clouds {
        bottom: -4.062em;
    }

    .theme-switch__checkbox:checked
        + .theme-switch__container
        .theme-switch__stars-container {
        top: 50%;
        -webkit-transform: translateY(-50%);
        -ms-transform: translateY(-50%);
        transform: translateY(-50%);
    }
`;

export default Switch;

demo.tsx;
import Switch from '@/components/ui/sky-toggle';

export default function DemoOne() {
    return <Switch />;
}
```

Install NPM dependencies:

````bash
styled-components
``` add this to the right side of the lang toggle and use php storm mcp

## Response

Done. Integrated the `sky-toggle` React component into the codebase and added it to the right side of the language toggle in the header.

- Verified that the project already supports shadcn UI structure via `components.json`, Tailwind CSS v4 via `@tailwindcss/vite`, and TypeScript via `tsconfig.json`.
- Determined the default path for UI components is `resources/js/components/ui/` (aliased as `@/components/ui`) and styles is `resources/css/app.css`. Explained the architectural necessity of having a dedicated `/components/ui` folder for headless primitive separation, CLI scaffolding compatibility, and design system encapsulation.
- Installed `styled-components` dependency via npm.
- Created `resources/js/components/ui/sky-toggle.tsx` and `resources/js/components/ui/demo.tsx` (as well as mirrored at root `components/ui/`). Enhanced `Switch` with standard input props and a customizable `--sky-toggle-size` variable so it scales cleanly within navigation chrome.
- Integrated `Switch` into `resources/js/components/Header.tsx` directly to the right of `<LanguageSwitcher />`, wired to `useAppearance` to control and reflect the application's light/dark mode.
- Validated with `tsc --noEmit` and `vp build` (both passing with 0 errors).

## Files Modified / Created

- `package.json`, `package-lock.json` — Added `styled-components` dependency
- `resources/js/components/ui/sky-toggle.tsx` — Created the `Switch` sky-toggle component
- `resources/js/components/ui/demo.tsx` — Created demo component
- `components/ui/sky-toggle.tsx`, `components/ui/demo.tsx` — Mirrored components at root `components/ui`
- `resources/js/components/Header.tsx` — Added `Switch` to the right side of `LanguageSwitcher` and connected it to `useAppearance`
````

#response:

### 1. Codebase Verification

The codebase was analyzed using PhpStorm MCP tools and already supports all three prerequisites:

• shadcn Project Structure: Configured via components.json with @/components/ui alias and new-york style.
• Tailwind CSS: Tailwind CSS v4 (tailwindcss, @tailwindcss/vite) is installed in package.json and configured in resources/css/app.css.
• TypeScript: TypeScript 5.7+ is active with compiler configuration defined in tsconfig.json.

│ Note
│ If setting up shadcn, Tailwind, or TypeScript from scratch in a fresh project:
│
│ 1. Initialize TypeScript: npm install -D typescript @types/react @types/react-dom @types/node and create a tsconfig.json.
│ 2. Install Tailwind CSS: npm install tailwindcss @tailwindcss/vite and configure the Vite plugin.
│ 3. Initialize shadcn CLI: Run npx shadcn@latest init to generate components.json and configure component aliases.
──────

### 2. Default Paths for Components & Styles

From components.json and tsconfig.json:

Resource │ Path in Codebase │ Alias
────────────────────────────────────────────────────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────┼───────────────────────────────────────────────────────────────────────────────
UI Primitives │ resources/js/components/ui/ │ @/components/ui
Composite Components │ resources/js/components/ │ @/components
Styles │ resources/css/app.css │ —

#### Why the Default Path is resources/js/components/ui and Why this Folder is Essential

In standard Next.js or Vite projects, shadcn defaults to root /components/ui. However, in a Laravel + Inertia + Vite project, client-side assets reside under resources/js/. The path alias @/* in tsconfig.json maps ./resources/js/*, making
@/components/ui point directly to resources/js/components/ui.

Why having a dedicated /components/ui folder is important:

1. Separation of Concerns: Distinguishes generic, headless base UI building blocks (buttons, dialogs, inputs, switches) from domain/feature-specific components (e.g., Header, FilterBar, ContactFooter).
2. shadcn CLI Compatibility: When executing npx shadcn@latest add <component>, the CLI reads components.json and automatically puts the generated component into the configured ui folder. Without this convention, automated additions fail or
   clutter application logic.
3. Consistent Import Contracts: Enables uniform imports such as import Switch from "@/components/ui/sky-toggle" across all pages and layouts.
   ──────

### 3. Installed Dependencies

styled-components was installed via PhpStorm terminal execution:

    npm i styled-components

──────

### 4. Components Created

Created using PhpStorm MCP's create_new_file:

1. resources/js/components/ui/sky-toggle.tsx: The Switch component styled with styled-components, enhanced with scalable CSS variables (--sky-toggle-size) and standard input props (checked, onChange, className).
2. resources/js/components/ui/demo.tsx: The demo component rendering <Switch />.
3. Also mirrored at root components/ui/sky-toggle.tsx and components/ui/demo.tsx for directory coverage.
   ──────

### 5. Integration into Header (Right Side of Language Toggle)

Integrated directly into resources/js/components/Header.tsx:157-170 to the right of <LanguageSwitcher />. It is hooked into the application's useAppearance hook to control dark/light mode:

    <LanguageSwitcher className="sm:ml-1" />
    <Switch
        checked={resolvedAppearance === 'dark'}
        onChange={() =>
            updateAppearance(
                resolvedAppearance === 'dark' ? 'light' : 'dark',
            )
        }
        aria-label="Toggle theme"
        className="shrink-0 flex items-center sm:ml-1 [--sky-toggle-size:10px] sm:[--sky-toggle-size:11px]"
    />

──────

### 6. Verification

• npm run types:check (tsc --noEmit): Passed with 0 errors.
• npm run build (vp build): Build completed successfully.
