#!/bin/bash

# crontab -e
# cd /var/www/aeroportdequebec.com && bash tools/cron.sh > /dev/null 2>&1

#drush sset system.cron_lock $(date +"%s")
#if [[ $(drush sget system.cron_lock) && $(drush scr tools/crondiff.php) == "ok" ]]
#then
#   echo "not running"
#else
#   echo "doing it"
#fi

if [[ $(drush sget system.cron_lock) && $(drush scr tools/crondiff.php) == "ok" ]]
then
    echo "$(date +%Y-%m-%d\ %H:%M:%S) Cron is already running"

    drush scr tools/log.php --channel='cron' --message='Cron is already running.'
else
    echo "$(date +%Y-%m-%d\ %H:%M:%S) Cron is not running"

    drush scr tools/log.php --channel='cron' --message='Cron is not running.'

    drush scr tools/log.php --channel='cron' --message='Locking cron.'

    # Create lock on database
    drush sset system.cron_lock $(date +"%s")

    drush scr tools/log.php --channel='cron' --message='Cron is locked.'

    drush scr tools/log.php --channel='cron' --message='Migrations are starting.'

    # Reset migrations in case they got stuck
    drush mrs arrivals
    drush mrs departures

    drush scr tools/log.php --channel='cron' --message='Migrations are resetted.'

    drush scr tools/log.php --channel='cron' --message='Cron tasks are starting.'

    drush cron

    drush scr tools/log.php --channel='cron' --message='Cron tasks are complete.'

    drush scr tools/log.php --channel='cron' --message='Warming the cache.'

    drush scr tools/log.php --channel='cron' --message='Warming home page cache.'

    curl --max-time 30 https://www.aeroportdequebec.com/fr > /dev/null 2>&1
    curl --max-time 30 https://www.aeroportdequebec.com/en > /dev/null 2>&1

    drush scr tools/log.php --channel='cron' --message='Warming FR schedules cache.'

    curl --max-time 30 https://www.aeroportdequebec.com/fr/vols-et-destinations/horaire-des-vols/arrivees > /dev/null 2>&1
    curl --max-time 30 https://www.aeroportdequebec.com/fr/vols-et-destinations/horaire-des-vols/departs > /dev/null 2>&1
    curl --max-time 30 https://www.aeroportdequebec.com/fr/vols-et-destinations/horaire-des-vols/arrivees-demain > /dev/null 2>&1
    curl --max-time 30 https://www.aeroportdequebec.com/fr/vols-et-destinations/horaire-des-vols/departs-demain > /dev/null 2>&1

    drush scr tools/log.php --channel='cron' --message='Warming EN schedules cache.'

    curl --max-time 30 https://www.aeroportdequebec.com/en/flights-and-destinations/flight-schedules/arrivals > /dev/null 2>&1
    curl --max-time 30 https://www.aeroportdequebec.com/en/flights-and-destinations/flight-schedules/departures > /dev/null 2>&1
    curl --max-time 30 https://www.aeroportdequebec.com/en/flights-and-destinations/flight-schedules/arrivals-tomorrow > /dev/null 2>&1
    curl --max-time 30 https://www.aeroportdequebec.com/en/flights-and-destinations/flight-schedules/departures-tomorrow > /dev/null 2>&1

    drush scr tools/log.php --channel='cron' --message='Warming destinations served cache.'

    curl --max-time 30 https://www.aeroportdequebec.com/fr/vols-et-destinations/destinations-desservies > /dev/null 2>&1
    curl --max-time 30 https://www.aeroportdequebec.com/en/flights-and-destinations/destinations-served > /dev/null 2>&1

    drush scr tools/log.php --channel='cron' --message='Cached warmed.'

    drush scr tools/log.php --channel='cron' --message='Unlocking cron.'

    # Release lock
    drush sdel system.cron_lock

    drush scr tools/log.php --channel='cron' --message='Cron is unlocked.'
fi