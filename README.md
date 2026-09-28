# মেডিসেবা — Laravel + MySQL (cPanel)

ডাক্তার চেম্বার ও পেশেন্ট ম্যানেজমেন্ট। পাবলিক ওয়েবসাইট + স্টাফ প্যানেল। PHP 8.2+, Laravel 12, MySQL/MariaDB। cPanel shared hosting-এ চালানো যায়।

## কী করে
- রোগী ওয়েবসাইট থেকে সিরিয়াল নেয়; স্লট চেম্বার শুরু থেকে ১৫–২০ মিনিট
- বুকিং হলে রোগীর মোবাইলে সময়সহ SMS
- দেরি হলে অটো রি-শিডিউল + নতুন সময় SMS
- ফোন দিয়ে রোগী চেনা, হিস্ট্রি ও প্রেসক্রিপশন
- স্টাফ প্যানেল: কিউ, প্রেসক্রিপশন, সময়সূচি, ওয়াক-ইন
- লগইন ২ ধাপ: SMS OTP অথবা Google Authenticator
- ৩ রোল: সুপার অ্যাডমিন, অ্যাডমিন, ম্যানেজার

## রোল
| রোল | কাজ |
|---|---|
| Super Admin | সব + ইউজার ম্যানেজ |
| Admin | কিউ, প্রেসক্রিপশন, সময়সূচি, সেটিংস, SMS |
| Manager | কিউ, ওয়াক-ইন, রোগী দেখা |

## cPanel-এ হোস্ট (বাধ্যতামূলক)

1. PHP 8.2+ সিলেক্ট করুন (cPanel → MultiPHP Manager)। এক্সটেনশন: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`।
2. MySQL Database তৈরি: ডাটাবেস, ইউজার, পাসওয়ার্ড; ইউজারকে ডাটাবেসে ALL PRIVILEGES দিন।
3. প্রজেক্ট ফাইল `public_html` (বা সাবডোমেইন ফোল্ডার) এ আপলোড করুন। রুট `.htaccess` ট্রাফিক `public/` এ পাঠায়।
4. `storage` ও `bootstrap/cache` পারমিশন 0755 (প্রয়োজনে 0775)।
5. `.env.example` কপি করে `.env` বানান:

```
APP_NAME="মেডিসেবা ক্লিনিক"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Asia/Dhaka
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass

SESSION_DRIVER=file
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

6. SSH বা Terminal (না থাকলে লোকালে করে আপলোড):

```
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

7. ডিফল্ট লগইন (সিডের পর **পাসওয়ার্ড বদলান**):
- Super Admin: `01700000001` / `super@mediseba.local` · `ChangeMe#Super1`
- Admin: `01700000002` / `admin@mediseba.local` · `ChangeMe#Admin1`
- Manager: `01700000003` / `manager@mediseba.local` · `ChangeMe#Manager1`
- স্টাফ URL: `/staff/login`

8. SMS: স্টাফ → এসএমএস → HTTP API (BulkSMSBD ইত্যাদি)। আগে “শুধু লগ” মোডে টেস্ট করুন।

9. Cron (প্রতি ৫ মিনিট) — রিমাইন্ডার SMS:

```
php /home/USER/public_html/artisan clinic:remind
```

অথবা URL (এসএমএস পেজের cron key):

```
https://yourdomain.com/cron/remind?key=YOUR_CRON_KEY
```

## ২এফএ
লগইনের পর মোবাইলে OTP যায় (SMS গেটওয়ে চালু থাকলে)। অথবা স্টাফ → ২এফএ থেকে Google Authenticator চালু করুন।

## নিরাপত্তা (ম্যান্ডেটরি)
- CSRF সব ফর্মে
- পাসওয়ার্ড bcrypt (১২ রাউন্ড)
- রেট লিমিট লগইন/বুকিং/OTP
- অ্যাকাউন্ট লক (৫ বার ভুল)
- সেশন এনক্রিপ্ট + রোল মিডলওয়্যার
- প্রেসক্রিপশন শুধু স্টাফ বা ওই রোগী
- সিকিউরিটি হেডার, `APP_DEBUG=false` প্রোডাকশনে

Composer লোকালে চালিয়ে `vendor/` সহ আপলোড করতে পারেন যদি cPanel-এ Composer না থাকে।
