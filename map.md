project/
├── api.php              ← نقطه ورودی POST
├── worker.php           ← ورکر کرون
├── bootstrap.php        ← راه‌انداز مشترک
├── config.php           ← تنظیمات
├── src/
│   ├── Logger.php       ← لاگر حرفه‌ای
│   ├── Database.php     ← لایه دیتابیس
│   └── Queue.php        ← صف فایل‌محور
├── storage/
│   ├── queue/           ← فایل‌های در انتظار پردازش
│   ├── processing/      ← فایل‌های در حال پردازش
│   └── failed/          ← فایل‌های ناموفق
└── logs/                ← لاگ‌های روزانه