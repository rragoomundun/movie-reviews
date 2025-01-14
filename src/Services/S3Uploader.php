<?php

namespace App\Service;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3Uploader
{
  private $s3Client;
  private $bucket;
  private $imageBucketFolder;

  public function __construct(string $awsKey, string $awsSecret, string $region, string $bucket, string $imageBucketFolder)
  {
    $this->s3Client = new S3Client([
      'version' => 'latest',
      'region' => $region,
      'credentials' => [
        'key'    => $awsKey,
        'secret' => $awsSecret,
      ],
    ]);

    $this->bucket = $bucket;
    $this->imageBucketFolder = $imageBucketFolder;
  }

  public function upload(UploadedFile $file): string
  {
    $key = $this->imageBucketFolder . '/' . uniqid() . '-' . $file->getClientOriginalName();

    try {
      $result = $this->s3Client->putObject([
        'Bucket' => $this->bucket,
        'Key'    => $key,
        'SourceFile' => $file->getPathname(),
        'ACL'    => 'public-read', // or 'private'
      ]);

      return $result['ObjectURL']; // Return the URL of the uploaded file
    } catch (AwsException $e) {
      // Handle the error
      throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
    }
  }
}
