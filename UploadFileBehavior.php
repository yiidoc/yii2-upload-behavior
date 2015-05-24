<?php
namespace yii\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\web\UploadedFile;
use Yii;

class UploadFileBehavior extends Behavior
{
    public $attribute = '';

    /**
     * @var string|callable
     */
    public $uploadDir = '@webroot/uploads';

    /**
     * @var string The unique file name;
     */
    private $_fileName;
    /**
     * @var null|UploadedFile Get instance class UploadFile
     */
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
        if ($this->uploadDir instanceof \Closure) {
            $this->uploadDir = call_user_func($this->uploadDir);
        } else {
            $this->uploadDir = Yii::getAlias($this->uploadDir);
        }
    }

    public function beforeValidate()
    {
        $this->setUploadFile();
        $this->owner->setAttribute($this->attribute, $this->getUploadFile());
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

    protected function saveFile()
    {
        if ($this->getUploadFile() instanceof UploadedFile) {
            if (!file_exists($this->uploadDir)) {
                FileHelper::createDirectory($this->uploadDir);
            }
            if ($this->getUploadFile()->saveAs($this->getFilePath($this->getFileName()))) {
                $this->owner->setAttribute($this->attribute, $this->getFileName());
                return true;
            }
        }
        return false;
    }

    protected function deleteFile()
    {
        if (!$this->owner->getOldAttribute($this->attribute) && file_exists($this->getFilePath($this->owner->getOldAttribute($this->attribute)))) {
            @unlink($this->getFilePath($this->owner->getOldAttribute($this->attribute)));
        }
    }

    public function getFileName()
    {
        if (!$this->_fileName) {
            $this->_fileName = Inflector::slug($this->getUploadFile()->baseName) . strtolower(Yii::$app->security->generateRandomString(13)) . '-' . '.' . $this->getUploadFile()->extension;
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
     * Get Instance UploadFile
     */
    public function setUploadFile()
    {
        if (Yii::$app->request->isPost) {
            $this->_uploadFile = UploadedFile::getInstance($this->owner, $this->attribute);
        }
    }

    /**
     * @return null|UploadedFile
     */
    public function getUploadFile()
    {
        return $this->_uploadFile;
    }
}