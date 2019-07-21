# Requirements
* git
* Composer
* Php 7
* Redis
* Mysql 5.7

# Setup
```bash
git clone https://github.com/mstmvd/digikala-sms
cd digikala-sms
composer install
```
Create a database in mysql and run:

```bash
mysql -u<user> -p<pass> <database> < digikala.sql
```

# Run

```bash
php -S localhost:8000
```


