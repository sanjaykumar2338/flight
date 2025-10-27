# Deployment Checklist

1. **Clone & install dependencies**
   - `composer install --no-dev --optimize-autoloader`
   - `npm install && npm run build`

2. **Configure environment**
   - Copy `.env.example` to `.env` and set:
     - `APP_URL` to the public domain.
     - Database credentials (`DB_*`).
     - TravelNDC credentials or leave `TRAVELNDC_MODE=demo` for canned responses.
     - Paystack keys (`PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`, `PAYSTACK_WEBHOOK_SECRET`) and set `PAYSTACK_MODE` to `sandbox` or `live`.
   - Generate the application key: `php artisan key:generate`.

3. **Run database migrations & seeders**
   - `php artisan migrate --seed`
   - Ensure the Paystack webhook URL points to `https://your-domain.com/webhooks/paystack`.

4. **Optimize for production**
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`

5. **Queues & webhooks**
   - If using queued jobs, configure the queue worker and supervisor.
   - In Paystack dashboard, register the webhook URL and confirm signature secret matches `PAYSTACK_WEBHOOK_SECRET`.

6. **Verification**
   - Visit `/admin/bookings` and `/admin/payments` to confirm visibility.
   - Trigger a test checkout (with `PAYSTACK_MODE=demo` you can use the demo simulation buttons).
