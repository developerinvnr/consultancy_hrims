<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class S3Service
{
    /**
     * Upload a file to S3 with proper path structure
     */
    public function uploadFile($file, $path, $visibility = 'public')
    {
        try {
            // Ensure path has proper formatting
            $path = trim($path, '/');

            // Generate a unique filename if not provided
            if (is_string($file)) {
                // If $file is a string (file path), get the contents
                $fileContent = file_get_contents($file);
                $filename = basename($path);
                Storage::disk('s3')->put($path, $fileContent, $visibility);
            } else {
                // If $file is an UploadedFile instance
                $filename = basename($path);
                Storage::disk('s3')->put($path, file_get_contents($file), $visibility);
            }

            $url = Storage::disk('s3')->url($path);

            Log::info('File uploaded to S3', [
                'path' => $path,
                'filename' => $filename,
                'url' => $url
            ]);

            return [
                'success' => true,
                'url' => $url,
                'key' => $path,
                'filename' => $filename
            ];
        } catch (\Exception $e) {
            Log::error('S3 upload failed', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload requisition document with proper structure including Consultancy folder
     */
    public function uploadRequisitionDocument($file, $requisitionType, $documentType, $customPath = null)
    {
        $timestamp = now()->timestamp;
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // Generate safe filename
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $filename = "{$safeName}_{$timestamp}.{$extension}";

        // Determine the path
        if ($customPath) {
            $path = trim($customPath, '/') . '/' . $filename;
        } else {
            // Structure: Consultancy/Requisitions/{Type}/{DocumentType}/{filename}
            $path = "Consultancy/Requisitions/{$requisitionType}/{$documentType}/{$filename}";
        }

        return $this->uploadFile($file, $path);
    }

    /**
     * Generate S3 path for requisition documents including Consultancy folder
     */
    public function generateRequisitionPath($requisitionType, $documentType, $filename)
    {
        $requisitionType = ucfirst($requisitionType); // Contractual, TFA, CB
        $documentType = strtolower($documentType); // pan_card, bank_document, resume, aadhaar_card, etc.

        return "Consultancy/Requisitions/{$requisitionType}/{$documentType}/{$filename}";
    }

    /**
     * Get File URL from S3
     */
    public function getFileUrl($filePath)
    {
        try {
            return Storage::disk('s3')->url($filePath);
        } catch (\Exception $e) {
            Log::error('Failed to get S3 URL', [
                'error' => $e->getMessage(),
                'path' => $filePath
            ]);
            return null;
        }
    }

    /**
     * Delete file from S3
     */
    public function deleteFile($filePath)
    {
        try {
            if (Storage::disk('s3')->exists($filePath)) {
                Storage::disk('s3')->delete($filePath);
                Log::info('File deleted from S3', ['path' => $filePath]);
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'File not found'];
        } catch (\Exception $e) {
            Log::error('Failed to delete file from S3', [
                'error' => $e->getMessage(),
                'path' => $filePath
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if file exists in S3
     */
    public function fileExists($filePath)
    {
        return Storage::disk('s3')->exists($filePath);
    }

    /**
     * Generate pre-signed URL for temporary access (if needed)
     */
    public function generatePresignedUrl($filePath, $expiryMinutes = 60)
    {
        try {
            $client = Storage::disk('s3')->getDriver()->getAdapter()->getClient();
            $command = $client->getCommand('GetObject', [
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $filePath
            ]);

            $request = $client->createPresignedRequest($command, "+{$expiryMinutes} minutes");
            return (string) $request->getUri();
        } catch (\Exception $e) {
            Log::error('Failed to generate presigned URL', [
                'error' => $e->getMessage(),
                'path' => $filePath
            ]);
            return null;
        }
    }

    /**
     * Helper method to build full S3 path for display
     */
    public function getFullS3Path($relativePath)
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');

        return "s3://{$bucket}/{$relativePath}";
    }

    /**
     * Download file from S3
     */
    public function downloadFile(string $filePath, string $fileName = null)
    {
        try {
            if (!Storage::disk('s3')->exists($filePath)) {
                abort(404, 'File not found');
            }

            $fileName = $fileName ?? basename($filePath);

            // This forces browser to download instead of opening
            return Storage::disk('s3')->download($filePath, $fileName);
        } catch (\Exception $e) {
            Log::error('S3 download failed', [
                'error' => $e->getMessage(),
                'path'  => $filePath
            ]);

            abort(500, 'Unable to download file');
        }
    }
}
