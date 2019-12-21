<?php

namespace App\Libraries;

use Illuminate\Http\UploadedFile;

class UploadImage
{
    public $name;
    private $storageImage;
    private $fileSize;
    private $imageConfig;

    /**
     * UploadImage constructor.
     */
    public function __construct()
    {
        $this->storageImage = \Storage::disk('public');
        $this->imageConfig = config('image_config');
    }

    /**
     * Upload image to store bucket.
     *
     * Recommend to check return value when using this function
     * And remember to save full path into DB, not only the name
     *
     * @param UploadedFile $img     Image
     * @param string       $imgType Kind of image
     *
     * @return $this
     */
    public function upload($img, $imgType)
    {
        $config = $this->getConfig($imgType);
        $ext = $img->getClientOriginalExtension();
        $this->name = generateUuid() . '.' . $ext;
        $image = \Image::make($img);
        try {
            foreach ($config as $value) {
                if (!empty($value['size']) && is_array($value['size'])) {
                    //Crop available only if resize attribute is true
                    $crop = isset($value['crop']) ? $value['crop'] : false;
                    $image = $this->formatImage($image, $value['size'], $crop);
                }
                $pathUpload = $value['path'] . $this->name;
                //Upload to Image
                $this->storageImage->put(
                    $pathUpload,
                    $image->stream()->__toString(),
                    'public'
                );
            }
        } catch (\Exception $e) {
            $this->delete($this->name, $imgType);
            throw new \RuntimeException('Upload failed:' . $e->getMessage());
        }
        return $this;
    }

    /**
     * Get config of image kind
     *
     * @param int  $imgType       Type of image
     * @param bool $withWholeType Get whole type config
     *
     * @return array An array of kind of the image. Ex: Original, thumbnail,...
     */
    public function getConfig($imgType, $withWholeType = false)
    {
        $typeConfigs = $this->imageConfig['type'];
        foreach ($typeConfigs as $type => $value) {
            if ($value['mode'] == $imgType) {
                return $withWholeType ? [$typeConfigs[$type]['kind'], $typeConfigs[$type]] : $typeConfigs[$type]['kind'];
            }
        }
        throw new \RuntimeException;
    }

    /**
     * Format image
     *
     * Crop and resize image
     *
     * @param \Intervention\Image\Image $image Image
     * @param array                     $size  Size
     * @param bool                      $crop  Is crop?
     *
     * @return \Intervention\Image\Image
     */
    private function formatImage($image, $size, $crop)
    {
        if ($crop) {
            //Do crop
            $width = $crop[0];
            $height = $crop[1];
            $image->crop($width, $height);
        }
        // resize the image to a width of 300 and constrain aspect ratio (auto height)
        $image->resize($size[0], null, function ($constraint) {
            $constraint->aspectRatio();
        });
        return $image;
    }

    /**
     * Delete images on store
     *
     * @param string $name    Image name
     * @param string $imgType Image type
     *
     * @return void
     */
    public function delete($name, $imgType)
    {
        $fullPaths = [];
        $config = $this->getConfig($imgType);
        foreach ($config as $value) {
            $fullPaths[] = $value['path'] . $name;
        }
        $this->storageImage->delete($fullPaths);
        return;
    }

    /**
     * Get image's properties
     *
     * @param int         $imgType Image's type
     * @param null|string $imgName Image's name
     * @param bool        $detail  Is get full detail?
     *
     * @return array|null
     */
    public function getImgProps($imgType, $imgName = null, $detail = false)
    {
        $imgName = $imgName ? $imgName : $this->name;
        if (!$imgName) {
            return null;
        }
        $name = ['name' => $imgName];
        $url = $this->getUrlImage($imgType, $imgName);
        if (!$detail) {
            return array_merge($name, $url);
        }
        $detail = $this->getImgDetails($imgName, $imgType);
        return array_merge($name, ['url' => $url], $detail);
    }

    /**
     * Get image's url
     *
     * @param int    $imgType Image's type (Meal, profile,...)
     * @param string $name    Image's name
     *
     * @return string
     */
    public function getUrlImage($imgType, $name)
    {
        if (empty($imgType) && $imgType != 0 || !$name) {
            return '';
        }
        $kindConfig = $this->getConfig($imgType);
        $data = [];
        foreach (array_keys($kindConfig) as $imgKind) {
            $imgPath = $this->getImgPath($name, $imgKind, $kindConfig);
            $data[$imgKind] = $this->getImgAddress($imgPath);
        }
        return $data;
    }

    /**
     * Get image's path
     *
     * @param string $name       Image's name
     * @param int    $imageKind  Image's kind (Original, thumbnail)
     * @param array  $kindConfig Kind config
     *
     * @return string
     */
    protected function getImgPath($name, $imageKind, $kindConfig)
    {
        return $kindConfig[$imageKind]['path'] . $name;
    }

    /**
     * Get image address
     *
     * @param string $imgPath Image's path
     *
     * @return string
     */
    private function getImgAddress($imgPath)
    {
        return sprintf($this->imageConfig['base_url'], $imgPath);
    }

    /**
     * Get detail of image. Eg: Width, heigh, size,..
     *
     * @param int    $imgType Image's type
     * @param string $imgName Image's name
     *
     * @return array
     */
    public function getImgDetails($imgType, $imgName)
    {
        $detail = [];
        $kindConfig = $this->getConfig($imgType);
        foreach (array_keys($kindConfig) as $imgKind) {
            $imgPath = $this->getImgPath($imgName, $imgKind, $kindConfig);
            if ($this->storageImage->exists($imgPath)) {
                $this->fileSize[$imgKind] = $this->storageImage->size($imgPath);
            }
            $image = \Image::make($this->getImgAddress($imgPath));
            $detail[$imgKind] = $this->getDetail($image, $imgKind);
        }
        return $detail;
    }

    /**
     * Set image properties
     *
     * @param \Intervention\Image\Image $image     Intervention Image
     * @param int                       $imageKind Kind of image
     *
     * @return array
     */
    private function getDetail($image, $imageKind)
    {
        //This is because $image->fileSize() doesn't work with file from store
        $fileSize = $this->fileSize[$imageKind] ? $this->fileSize[$imageKind] : $image->filesize(
        );
        return [
            'size'        => $fileSize,
            'height'      => $image->getHeight(),
            'width'       => $image->getWidth(),
            'contentType' => $image->mime(),
        ];
    }
}
