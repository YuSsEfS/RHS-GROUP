# RHS Group Mobile App

React Native mobile version for the RHS Group admin, employee, and client dashboards.

## What Is Included

- Login connected to the Laravel Sanctum API (`/api/login`, `/api/me`, `/api/logout`).
- Role-aware dashboard for admin, employee, and client users.
- Messages, notifications, meetings, RH resources, recruitment requests, matching progress, candidate score explanations, user management, profile, and multi-step request forms.
- RHS red, white, blush, and deep navy theme matching the web app.
- Bottom navigation, modal sheets, cards, animated counters, progress bars, touch animations, and responsive mobile layouts.

## API Target

Android emulator uses:

```txt
http://10.0.2.2:8000/api
```

For a physical phone, update `API_BASE_URL` in `src/api.ts` to your PC LAN IP, for example:

```txt
http://192.168.1.20:8000/api
```

## Run With Android Studio

1. Install dependencies:

```bash
npm install
```

2. Generate the native Android project:

```bash
npm run prebuild:android
```

3. Open the generated `android` folder in Android Studio.

4. Start Laravel locally:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

5. Run the Android app from Android Studio, or use:

```bash
npm run android
```

