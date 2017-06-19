# BagIt Indexer

A proof-of-concept tool for extracting data from Bags and indexing it in Elasticsearch.

## System requirements and installation

* PHP 5.5.0 or higher.
* [Composer](https://getcomposer.org)
* An [Elasticsearch server](https://www.elastic.co/products/elasticsearch) version 5.x or higher.

To install the Bagit Indexer:

* Clone the Git repo
* `cd bagit_indexer`
* `php composer.phar install` (or equivalent on your system, e.g., `./composer install`)

## Usage

This tool extracts data from Bags and pushes the data into Elasticsearch.

Run `php bagit_indexer.php --help` to get help info:

```
-e/--elasticsearch_url <argument>
     URL (including port number) of your Elasticsearch endpoint. Default is "http://localhost:9200".


-x/--elasticsearch_index <argument>
     Elasticsearch index. Default is "bags".


--help
     Show the help page for this command.


-i/--input <argument>
     Required. Absolute or relative path to a directory containing Bags. Trailing slash is optional.
```

Basically, all you need is some Bags (serialized or loose) in your input directory. Running the tool like this:

```
php bagit_indexer.php -i sample_bags
====================================================================================================> 100%
Done. 5 Bags added to http://localhost:9200/bags
```
indexes each Bag in your Elasticsearch instance, resulting in an Elasticsarch document for each Bag like this:

```json
      {
        "_index" : "bags",
        "_type" : "bag",
        "_id" : "bag_z2098-4",
        "_score" : 1.0,
        "_source" : {
          "source_path" : "/home/mark/Documents/hacking/bagit/bagit_indexer/sample_bags",
          "bagit_version" : {
            "major" : 0,
            "minor" : 96
          },
          "fetch" : {
            "fileName" : "fetch.txt",
            "data" : [ ],
            "fileEncoding" : "UTF-8"
          },
          "bag-info" : {
            "External-Description" : "The content we said we would send you.",
            "Bagging-Date" : "2017-06-18",
            "Internal-Sender-Identifier" : "bag_z2098-4",
            "Source-Organization" : "Acme Bags",
            "Contact-Email" : "info@acmebags.com"
          },
          "manifest" : {
            "fileName" : "manifest-sha1.txt",
            "hashEncoding" : "sha1",
            "fileEncoding" : "UTF-8",
            "data" : {
              "data/1/acontentfile.txt" : "a934ca8815f92c1930159df75168847a109d18ac",
              "data/2/acontentfile.txt" : "a934ca8815f92c1930159df75168847a109d18ac",
              "data/3/acontentfile.txt" : "a934ca8815f92c1930159df75168847a109d18ac"
            }
          }
        }
      }
```

## Sample queries

* Find Bags with a Bagging-Date tag value of `2017-06-17`
  * `curl -v  'http://localhost:9200/bags/_search?q=bag-info.Bagging-Date:2017-06-17'`
* Find Bags with a Contact-Name tag value of `Mark Jordan'
  * `curl -v  'http://localhost:9200/bags/_search?q=bag-info.Contact-name:Mark+Jordan'`


## License

GPLv3

## To do

* Provide more example queries
* Add logging of indexing and errors
