assetic-filters
======================

# AutoprefixerFilter

See [autoprefixer](https://github.com/ai/autoprefixer)

```php
use ZaCoZa\Assetic\Filter\AutoprefixerFilter;

$browsers = array('> 1%', 'last 2 versions', 'Firefox ESR', 'Opera 12.1');

new AutoprefixerFilter('/usr/bin/autoprefixer', $browsers);
```

# LessFilter

Assetic is not able do detect change in @import files wen you use Less. Here comes the fix!

This filter take a third parameter which is an array of file paths. The files provided this way will be `touch()` by the filter to change their access time. So Assetic will detect a change and will rebuild the asset.

```php
    
use ZaCoZa\Assetic\Filter\LessFilter;
    
// The Main Less File with your @import files
$myMainLessFile = __DIR__.'/assets/styles/main.less'
    
new LessFilter('/usr/local/bin/node',                // The path to the node binary
               array('/usr/local/lib/node_modules'), // An array of node paths
               array($myMainLessFile)                // MAGIC: An array of file paths you want to touch()
              );
```


