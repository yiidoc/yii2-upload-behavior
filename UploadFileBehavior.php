<?php
namespace yii\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\web\UploadedFile;
use Yii;

/**
 * Class UploadFileBehavior
 * @package yii\behaviors
 *
 * @property UploadedFile|null $uploadFile
 */
class UploadFileBehavior extends Behavior
{
    /**
     * @var ActiveRecord
     */
    public $owner;
    /**
     * @var string
     */
    public $attribute = '';

    /**
     * @var string|callable
     */
    public $uploadDir = '@webroot/uploads';

    /**
     * @var string The unique file name;
     */
    private $_fileName;
    private $_uploadFile;

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
        ];
    }

    public function init()
    {
        parent::init();
        if ($this->uploadDir instanceof \Closure) {
            $this->uploadDir = call_user_func($this->uploadDir);
        } else {
            $this->uploadDir = Yii::getAlias($this->uploadDir);
        }
    }

    public function beforeValidate()
    {
        $this->setUploadFile();
        if ($this->uploadFile && $this->uploadFile instanceof UploadedFile) {
            $this->owner->setAttribute($this->attribute, $this->uploadFile);
        }
    }

    public function beforeInsert()
    {
        $this->saveFile();
    }

    public function beforeUpdate()
    {
        if ($this->saveFile()) {
            $this->deleteFile();
        }
    }

    public function beforeDelete()
    {
        $this->deleteFile();
    }

    /**
     * @return bool
     * @throws \yii\base\Exception
     */
    protected function saveFile()
    {
        if ($this->uploadFile && $this->uploadFile instanceof UploadedFile) {
            if (!file_exists($this->uploadDir)) {
                FileHelper::createDirectory($this->uploadDir, '0777');
            }
            if ($this->uploadFile->saveAs($this->getFilePath($this->getFileName()))) {
                $this->owner->setAttribute($this->attribute, $this->getFileName());
                return true;
            }
        } else {
            $this->owner->setAttribute($this->attribute, $this->owner->getOldAttribute($this->attribute));
        }
        return false;
    }

    protected function deleteFile()
    {
        if ($this->owner->getOldAttribute($this->attribute) && file_exists($this->getFilePath($this->owner->getOldAttribute($this->attribute)))) {
            unlink($this->getFilePath($this->owner->getOldAttribute($this->attribute)));
        }
    }

    public function getFileName()
    {
        if (!$this->_fileName) {
            $this->_fileName = Inflector::slug($this->uploadFile->baseName) . '-' . strtolower(Yii::$app->security->generateRandomString(13)) . '.' . $this->uploadFile->extension;
        }
        return $this->_fileName;
    }

    /**
     * @param $fileName string
     * @return string
     */
    public function getFilePath($fileName)
    {
        return rtrim($this->uploadDir, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Get Instance UploadFile if
     */
    public function setUploadFile()
    {
        if (Yii::$app->request->isPost) {
            $this->_uploadFile = UploadedFile::getInstance($this->owner, $this->attribute);
        }
    }

    /**
     * @return UploadedFile|null
     */
    public function getUploadFile()
    {
        return $this->_uploadFile;
    }
}