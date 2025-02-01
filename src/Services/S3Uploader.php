<?php

namespace App\Service;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class S3Uploader
{
  private $s3Client;
  private $bucket;
  private $imageBucketFolder;

  public function __construct(
    string $awsKey,
    string $awsSecret,
    string $region,
    string $bucket,
    string $imageBucketFolder,
    private SluggerInterface $slugger
  ) {
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
    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFileName = strtolower($this->slugger->slug($originalFilename));
    $key = $this->imageBucketFolder . '/' . $safeFileName . '-' . uniqid() . '.' . $file->guessExtension();

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
