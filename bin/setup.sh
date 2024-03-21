
if [ ! -f composer.json ]; then
    printf "Please make sure to run this script from the root directory of this repo."
    exit 1
fi

printf "Please make sure to run the setup script for the ${Blue}Bmoov Admin${Nc} project before running this one.\n"

while true; do
    read -p "Did you already run the backend setup script? [Y/N]: " yn
    case $yn in
        [Yy]* ) break;;
        [Nn]* ) exit;;
        * ) printf "${Red}Please answer ${Nc}yes${Red} or ${Nc}no${Red}.${Nc}\n";;
    esac
done

#############################################################
############# COMMANDS ######################################
#############################################################
rm -rf vendor
composer install
rm -rf storage/keys/*
cp .env.development .env
php artisan key:generate
php artisan tenants:migrate
php artisan tenants:run passport:install
