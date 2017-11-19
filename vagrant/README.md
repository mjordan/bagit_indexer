# Vagrant build scripts for Elasticsearch 5.4.1

This set of Vagrant scripts will build a virutal machine running:

* Ubuntu trusty64
* Elasticsearch 5.6.4

## System requirements and installation

* [VirtualBox](https://www.virtualbox.org/)
  * Be sure to install a version of VirtualBox that [is compatible with Vagrant](https://www.vagrantup.com/docs/virtualbox/)
* [Vagrant](http://www.vagrantup.com)

To set up the Vagrant box:

* `vagrant up`

## Usage

Elasticsearch is configured to start on boot and will be available at `http://localhost:9200`. To test, run the following `curl` command from your host machine:

`curl 'http://localhost:9200/?pretty'`

If you see a response similar to the following, your Elasticsearch instance is operating as intended:

```json
{
  "name" : "VAQGJIQ",
  "cluster_name" : "elasticsearch",
  "cluster_uuid" : "ecverQDfRp2lPn2wvTAe-Q",
  "version" : {
    "number" : "5.6.4",
    "build_hash" : "8bbedf5",
    "build_date" : "2017-10-31T18:55:38.105Z",
    "build_snapshot" : false,
    "lucene_version" : "6.6.1"
  },
  "tagline" : "You Know, for Search"
}
```

## BagIt Indexer is installed automatically

The setup scripts clone this Git repo and run composer to install the BagIt Indexer. This is just a convenience; if you prefer to run the indexer and search scripts from your host, follow the instructions in the main README.md file.

But, if you want to log into the virtual machine to index and search Bags, from within the vagrant directory, enter the following command:

```vagrant ssh```

After you enter the VM's shell, run 

```cd bagit_indexer```

and you're ready to start indexing. 
