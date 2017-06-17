# BagIt Indexer

A simple tool for extracting data from Bags for indexing in Solr, Elasticsearch, etc.

## System requirements and installation

* PHP 5.5.0 or higher.
* [Composer](https://getcomposer.org)


To install the Bagit Indexer:
* Clone the Git repo
* `cd bagit_indexer
* `php composer.phar install` (or equivalent on your system, e.g., `./composer install`)

## Usage

`php bagit_indexer.php --help`

```
--help
     Show the help page for this command.


-i/--input <argument>
     Required. Absolute or relative path to a directory containing Bags. Trailing slash is optional.


-o/--output <argument>
     Required. Absolute or relative path to the directory where the JSON documents will be saved. Trailing slash is optional.
```

Output is a JSON file like this for each bag in the input directory:


```json
{
   "id":"Bag-islandora_116",
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
      "Contact-Name":"Mark Jordan",
      "Contact-Email":"example@example.com",
      "Bagging-Date":"2017-06-17"
   },
   "manifest":{
      "fileName":"manifest-sha1.txt",
      "hashEncoding":"sha1",
      "fileEncoding":"UTF-8",
      "data":{
         "data\/DC.xml":"36960c410252a4d74315968fc52fdd706f9675b5",
         "data\/FULL_TEXT.txt":"da39a3ee5e6b4b0d3255bfef95601890afd80709",
         "data\/MODS.xml":"64ec3bb75d992e1541e14ff3df27eb1b62124a1f",
         "data\/OBJ.pdf":"24b1614dbc530d950839b9ce7d2157b3dbedff17",
         "data\/PREVIEW.jpg":"6098bac5e03ea76a30769bbe9c25dae1dcf391f6",
         "data\/RELS-EXT.rdf":"2682121168fe147bd090d30ef882dbef6a240df6",
         "data\/TECHMD.xml":"02304bb38317d1ec1cdd4bc53da1525f2ec79a2f",
         "data\/TN.jpg":"e0334b8affc1d93e5349d77a3d51344f25c2ac62"
      }
   }
}
```

## License

GPLv3

## To do

* Provide sample queries in Solr.
* Test with bags that contain subdirectories in /data.
