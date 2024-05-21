<?php
namespace Artemis\Repository;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;

trait UploadTrait
{
    /**
     * upload image
     * @param array $data
     * @return array
     */
    public function upload(array $data): array
    {
        $file          = $data['file'];
        $width         = $data['width'] ?? 0;
        $height        = $data['height'] ?? 0;

        $imageName = $this->generateNewFileName($file);

        try {
            if ($file->getClientOriginalExtension() == 'svg') {
                $file->move(storage_path($this->model->uploadPath), $imageName);
                return $this->uploadSuccess($imageName);
            }

            if ($width && $height) {
                Image::make($file->getRealPath())->fit($width, $height)->save($this->getUploadImagePath($imageName));
            } else {
                Image::make($file->getRealPath())->save($this->getUploadImagePath($imageName));
            }
            return $this->uploadSuccess($imageName);
        } catch (\Exception $e) {
            return $this->uploadFail($e);
        } catch (\Throwable $t) {
            return $this->uploadFail($t);
        }
    }

    /**
     * @param $file
     * @return string
     */
    public function generateNewFileName($file): string
    {
        $strSecret   = '!@#$%^&*()_+QBGFTNKU' . time() . rand(111111, 999999);
        $filenameMd5 = md5($file . $strSecret);
        return date('Y_m_d') . '_' . $filenameMd5 . '.' . $file->getClientOriginalExtension();
    }

    /**
     * get image path
     * @param  String $img
     * @return String
     */
    public function getImagePath($img): string
    {
        return app('url')->asset($this->model->imgPath . '/' . $img);
    }

    /**
     * get path upload image
     * @param string $img
     * @return string
     */
    public function getUploadImagePath($img): string
    {
        if (!File::isDirectory(storage_path($this->model->uploadPath))) {
            File::makeDirectory(storage_path($this->model->uploadPath), 0777, true, true);
        }
        return storage_path($this->model->uploadPath . '/' . $img);
    }

    /**
     * upload success response
     * @param $name
     * @return array
     */
    protected function uploadSuccess($name): array
    {
        return [
            'code'    => 1,
            'message' => 'success',
            'data'    => [
                'name' => $name,
                'path' => $this->getImagePath($name)
            ]
        ];
    }

    /**
     * upload fail response
     * @param $e
     * @return array
     */
    protected function uploadFail($e): array
    {
        return [
            'code'    => 0,
            'message' => 'fail',
            'data'    => $e->getMessage()
        ];
    }

    /**
     * @param $image
     * @return void
     */
    public function removeImage($image): void
    {
        @unlink($this->getUploadImagePath($image));
    }
}
