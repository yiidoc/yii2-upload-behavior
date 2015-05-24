Yii2 Upload File Behavior
=========================
Yii2 Upload File Behavior

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist yiidoc/yii2-upload-behavior "*"
```

or add

```
"yiidoc/yii2-upload-behavior": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\behaviors\UploadFileBehavior',
                'attribute' => 'picture',
                'uploadDir' => '@webroot/uploads/user/pictures'
            ]
        ];
    }
```