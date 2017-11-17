# Script to watch the input directory of the BagIt Indexer
# (https://github.com/mjordan/bagit_indexer) for new, changed,
# renamed, and deleted Bags.
#
# To use this script, you must first install http://pythonhosted.org/watchdog/.
# 
# Usage: python bagit_watcher.py /path/to/input/dir 

import os
import sys
import time  
from subprocess import call
from watchdog.observers import Observer  
from watchdog.events import PatternMatchingEventHandler 

# You will need to change this.
bagit_indexer_path = "/home/vagrant/bagit_indexer/bagit_indexer.php"

class WatchHandler(PatternMatchingEventHandler):
    patterns = ["*.zip", "*.tgz", "*.tar.gz", "*.7z"]
    ignore_directories = True

    # @todo: Log actions.
    def on_created(self, event):
        call(["php", bagit_indexer_path, "-i", os.path.abspath(event.src_path)])

    # def on_modified(self, event):
        # Execute the indexer

    # def on_deleted(self, event):
        # Execute the indexer

    # Covers moving and renaming. We should confirm that we are dealing
    # with the same serialized Bag file by comparing its checksum on disk
    # with the one stored in ElasticSearch.
    # def on_moved(self, event):
        # Execute the indexer

if __name__ == '__main__':
    args = sys.argv[1:]
    observer = Observer()
    observer.schedule(WatchHandler(), path=args[0] if args else '.')
    observer.start()

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()

    observer.join()
