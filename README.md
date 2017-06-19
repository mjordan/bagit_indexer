# BagIt Indexer

A proof-of-concept tool for extracting data from Bags and indexing it in Elasticsearch.

## System requirements and installation

* PHP 5.5.0 or higher.
* [Composer](https://getcomposer.org)
* An [Elasticsearch server](https://www.elastic.co/products/elasticsearch) version 5.x or higher.
  * Scripts in the 'vagrant' directory will help you set up an Elasticsearch instance for testing.

To install the Bagit Indexer:

* Clone the Git repo
* `cd bagit_indexer`
* `php composer.phar install` (or equivalent on your system, e.g., `./composer install`)

## Usage

This tool extracts data from Bags and pushes the data into Elasticsearch.

Run `php bagit_indexer.php --help` to get help info:

```
--help
     Show the help page for this command.

-e/--elasticsearch_url <argument>
     URL (including port number) of your Elasticsearch endpoint. Default is "http://localhost:9200".

-x/--elasticsearch_index <argument>
     Elasticsearch index. Default is "bags".

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
   "_index":"bags",
   "_type":"bag",
   "_id":"bag_02",
   "_score":1.0,
   "_source":{
      "source_path":"/home/mark/Documents/hacking/bagit/bagit_indexer/sample_bags",
      "bagit_version":{
         "major":0,
         "minor":96
      },
      "fetch":{
         "fileName":"fetch.txt",
         "data":[

         ],
         "fileEncoding":"UTF-8"
      },
      "bag-info":{
         "External-Description":"Contains some stuff we want to put into cold storage, and that is very important.",
         "Bagging-Date":"2017-06-18",
         "Internal-Sender-Identifier":"Bag_02",
         "Source-Organization":"Bags R Us",
         "Contact-Email":"contact@bagrus.com"
      },
      "data_files":[
         "data/anothertextfile.txt",
         "data/atextfile-2.txt",
         "data/data_2.dat",
         "data/subdir/data_3.dat"
      ],
      "manifest":{
         "fileName":"manifest-sha1.txt",
         "hashEncoding":"sha1",
         "fileEncoding":"UTF-8",
         "data":{
            "data/anothertextfile.txt":"eb2614a66a1d34a6d007139864a1a9679c9b96aa",
            "data/atextfile-2.txt":"eb2614a66a1d34a6d007139864a1a9679c9b96aa",
            "data/data_2.dat":"3f8aef7161402b58c261c4a9778c27203e276593",
            "data/subdir/data_3.dat":"3f8aef7161402b58c261c4a9778c27203e276593"
         }
      }
   }
}
```

## Sample queries

The `bagit_searcher.php` script allows you to perform simple queries against the indexed data. The following types of queries are possible using this script:

* 'description', which queries the contents of the `bag-info.txt` 'External-Description' tag
* 'date', which queries the contents of the `bag-info.txt` 'Bagging-Date' tag
* 'org', which queries the contents of the `bag-info.txt` 'Source-Organization' tag
* 'filename', which queries filepaths of files in the Bag's `/data` directory
* 'source_path', which queries filepaths of the Bag's source path, which is the value provided to `bagit_indexer.php`'s `-input` option when the index was populated

For example, to search for the phrase "cold storage" in the description, run the command:

```php bagit_searcher.php -q "description:cold storage"```

which will return the following results (note that quotes are required because of the space):

```
Your query found 2 hits: 
bag_01
bag_02
```

To search for Bags that have a Bagging-Date of "2016-02-28", run this command:

```php bagit_searcher.php -q date:2016-02-28```

which will return the following result:

```
Your query found 1 hits: 
bag_03
```


Here are the values from the `bag-info.txt` files for the sample Bags, in case you want to try some searches of your own:

* bag_01
  * External-Description: Contains some stuff we want to put into cold storage.
  * Bagging-Date	: 2017-06-18
  * Internal-Sender-Identifier: Bag_01
  * Source-Organization: Bags R Us
  * Contact-Email: contact@bagrus.com
  * Files
    * data/anotherkindoffile.dat
    * data/anothertextfile.txt
    * data/atextfile.txt
* bag_01002
  * External-Description: The content we said we would send you.
  * Bagging-Date	: 2017-06-18
  * Internal-Sender-Identifier: bag_01002
  * Source-Organization: Acme Bags
  * Contact-Email: info@acmebags.com
  * Files
    * data/anothertextfile.txt
    * data/atextfile-09910.txt
    * data/important.xxx
* bag_02
  * External-Description: Contains some stuff we want to put into cold storage, and that is very important.
  * Bagging-Date	: 2017-06-18
  * Internal-Sender-Identifier: Bag_02
  * Source-Organization: Bags R Us
  * Contact-Email: contact@bagrus.com
  * Files
    * data/anothertextfile.txt
    * data/atextfile-2.txt
    * data/data_2.dat
    * data/subdir/data_3.dat
* bag_03
  * External-Description: A simple bag.
  * Bagging-Date	: 2016-02-28
  * Internal-Sender-Identifier: bag_03
  * Source-Organization: Acme Bags
  * Contact-Email: info@acmebags.com
  * Files
    * data/atextfile.txt
    * data/master.tif
    * data/metadata.xml
* bag_z2098-4
  * External-Description: The content we said we would send you.
  * Bagging-Date	: 2017-06-18
  * Internal-Sender-Identifier: bag_z2098-4
  * Source-Organization: Acme Bags
  * Contact-Email: info@acmebags.com
  * Files
    * data/1/acontentfile.txt
    * data/2/acontentfile.txt
    * data/3/acontentfile.txt

## License

GPLv3

## To do

* Add logging of indexing and errors
* Get 'filename' and 'source_path' queries, and date ranges, to work
