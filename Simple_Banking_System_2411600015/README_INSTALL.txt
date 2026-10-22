Extract this ZIP INTO your Simple Banking System project folder (the one with 'artisan').
Choose "Replace" for the modified files. .env, vendor and database.sqlite are not included.

  php artisan migrate
  php artisan db:seed --class=BankSeeder
  php artisan optimize:clear
  php artisan test
  php artisan serve

Demo logins (password: password): admin@bank.test, alice@bank.test, bob@bank.test
