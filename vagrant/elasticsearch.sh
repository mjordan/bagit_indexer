sudo add-apt-repository ppa:openjdk-r/ppa
sudo apt-get update
sudo apt-get install wget openjdk-8-jdk -y
wget -qO - https://packages.elasticsearch.org/GPG-KEY-elasticsearch | sudo apt-key add -

sudo apt-get install build-essential autoconf flex bison libtool python-dev -y

wget https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-5.6.4.deb
sudo dpkg -i elasticsearch-5.6.4.deb
rm /home/vagrant/elasticsearch-5.6.4.deb
sed -i 's/#network.host: 192.168.0.1/network.host: 0.0.0.0/' /etc/elasticsearch/elasticsearch.yml
sudo update-rc.d elasticsearch defaults 95 10

sudo /etc/init.d/elasticsearch start
