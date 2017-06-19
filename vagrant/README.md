# Vagrant build scripts for Elasticsearch 5.4.1

This set of Vagrant scripts will build a virutal machine running:

* Ubuntu trusty64
* Elasticsearch 5.4.1

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
  "name" : "jjpGgS3",
  "cluster_name" : "elasticsearch",
  "cluster_uuid" : "ro5cIb3dTCOOvyrXcIO2TQ",
  "version" : {
    "number" : "5.4.1",
    "build_hash" : "2cfe0df",
    "build_date" : "2017-05-29T16:05:51.443Z",
    "build_snapshot" : false,
    "lucene_version" : "6.5.1"
  },
  "tagline" : "You Know, for Search"
}
```
