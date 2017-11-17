# Update Ubuntu
apt-get -y update
apt-get -y upgrade

# General useful stuff
apt-get -y install openssh-server git vim wget curl

# So we can use the bagit_watcher.py script on the VM.
sudo apt-get install pip
pip install watchdog

# PHP stuff
apt-get -y install php5-dev php5-xsl php5-curl php5-cli php-pear
pear install Archive_Tar

# Install composer
curl -Ss https://getcomposer.org/installer | php
mv composer.phar /usr/bin/composer

# Clone this repo... woah, meta.
git clone https://github.com/mjordan/bagit_indexer.git /home/vagrant
