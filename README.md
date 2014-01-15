assetic-lessfilter-fix
======================

# Why?

Assetic is not able do detect change in @import files wen you use Less.

# How?

This filter take a third parameter which is an array of paths. The files provided this way will be `touch` by the filter to change their access time.

    `use ZaCoZa\AsseticFix\Filter\LessFilter;
    
    // The Main Less File with your @import files
    $myMainLessFile = __DIR__.'/assets/styles/main.less'
    
    $lessFilter = new LessFilter('/usr/local/bin/node', 
                                  array('/usr/local/lib/node_modules'), 
                                  array($myMainLessFile)
                                );`



