echo "Start docker compose"
docker-compose up -d
tput setaf 3; echo "SQLite testing"; tput sgr0
vendor/bin/phpunit
tput setaf 3; echo "POSTGRESQL testing"; tput sgr0
vendor/bin/phpunit --configuration phpunit.pgsql.xml
echo "End"
docker-compose down
