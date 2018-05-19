Add extension to Page (or any DataObject)

```yml
Page:
  extensions:
    - ImageWithStyleDataExtension
```

Add field to DataObject:


Use

```php


class ImageWithStyleDataExtension extends DataExtension
{

    protected function AddSelectImageList($fields, $tabName, $methodName, $folderName = '')
}

```

to add field to CMS.
