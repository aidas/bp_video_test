BP Video test
========================

This is a working app prototype fetching videos from provided YouTube channel(s).

It is built on Symfony 3.4 PHP framework and relies on it's command line interface.

Project utilises `google/apiclient` dependency for communication with Google's API.

## Requirements
- A working LAMP box with PHP 5.5.9 or later
- PHP libcurl package

## Instalation
- Clone the project
- Run `composer install` in it's root directory
- Run `mysql -e "source schema.sql"` (assuming you have root access to the MySQL; if not, ammend the command with the correct user credentials)

## Usage
Videos are fetched running the following command:

`php bin/console fetch:videos {CHANNEL_ID}`, e.g.:

`php bin/console fetch:videos UCnciA_RZVjq9DMvx1KB625Q`

You can also provide multiple channel IDs separated by comma, without the space:

`php bin/console fetch:videos UCnciA_RZVjq9DMvx1KB625Q,UCydKucK3zAWRuHKbB4nJjtw`

The idea is to have a **cron job** for each channel or a set of channels to run at the different times.
Crontab examples fetching videos from 2 different channels 4 times an hour at the different times:

- `0,15,30,45 * * * * apache php /pathtoproject/bin/console fetch:videos UCnciA_RZVjq9DMvx1KB625Q` 
- `2,17,32,47 * * * * apache php /pathtoproject/bin/console fetch:videos UCydKucK3zAWRuHKbB4nJjtw`

 
@todo - should the number of channels become very long, this approach may not be scalable enough and the channel IDs would need to be stored in the database.
