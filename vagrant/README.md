# Vagrant build scripts for Elasticsearch 5.4.1

This set of Vagrant scripts will build a virutal machine running:

* Ubuntu trusty64
* Elasticsearch 6.0.0

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
  "name" : "iogFL4d",
  "cluster_name" : "elasticsearch",
  "cluster_uuid" : "A2nFjdVVQmmcfXUJnLat6A",
  "version" : {
    "number" : "6.0.0",
    "build_hash" : "8f0685b",
    "build_date" : "2017-11-10T18:41:22.859Z",
    "build_snapshot" : false,
    "lucene_version" : "7.0.1",
    "minimum_wire_compatibility_version" : "5.6.0",
    "minimum_index_compatibility_version" : "5.0.0"
  },
  "tagline" : "You Know, for Search"
}
```
