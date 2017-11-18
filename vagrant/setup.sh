# Update Ubuntu
apt-get -y update
apt-get -y upgrade

# General useful stuff
apt-get -y install openssh-server git vim wget curl

# So we can use the bagit_watcher.py script on the VM.
sudo apt-get install pip
pip install watchdog

# PHP
apt-get -y install php5-dev php5-xsl php5-curl php5-cli php-pear
pear install Archive_Tar

cd /home/vagrant

# Install composer
cd /tmp
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-progress
mv composer.phar /usr/local/bin/composer

# Clone this repo... woah, meta.
git clone https://github.com/mjordan/bagit_indexer.git
