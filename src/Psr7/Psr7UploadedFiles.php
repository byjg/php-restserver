<?php

namespace ByJG\RestServer\Psr7;

use ByJG\RestServer\UploadedFiles;
use InvalidArgumentException;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * UploadedFiles over the uploaded files of a PSR-7 request instead of $_FILES, for a request
 * handled through Server::withRequestObject(). The files are read from the request only when
 * an upload is used.
 *
 * Only top-level fields, as with $_FILES: a nested field (name="docs[]") is not found.
 */
class Psr7UploadedFiles extends UploadedFiles
{
    /** @var array<string, UploadedFileInterface>|null */
    private ?array $files = null;

    public function __construct(private readonly ServerRequestInterface $request)
    {
    }

    #[Override]
    public function count(): int
    {
        return count($this->files());
    }

    /**
     * @return string[]
     */
    #[Override]
    public function getKeys(): array
    {
        return array_keys($this->files());
    }

    #[Override]
    public function getErrorCode(string $key): int|null
    {
        return $this->getFileByKey($key)->getError();
    }

    #[Override]
    public function getUploadedFile(string $key): string|false
    {
        return (string)$this->getFileByKey($key)->getStream();
    }

    #[Override]
    public function getFileName(string $key): string|null
    {
        return $this->getFileByKey($key)->getClientFilename();
    }

    #[Override]
    public function getFileType(string $key): int|string|null
    {
        return $this->getFileByKey($key)->getClientMediaType();
    }

    #[Override]
    public function saveTo(string $key, string $destinationPath, string $newName = ""): void
    {
        $this->getFileByKey($key)->moveTo($destinationPath . '/' . $this->targetFileName($key, $newName));
    }

    /**
     * Nothing to do: the runtime that created the PSR-7 uploaded file removes its temporary file.
     */
    #[Override]
    public function clearTemp(string $key): void
    {
        $this->getFileByKey($key);
    }

    #[Override]
    public function getFileSize(string $key): int|null
    {
        return $this->getFileByKey($key)->getSize();
    }

    /**
     * @return array<string, UploadedFileInterface>
     */
    private function files(): array
    {
        if ($this->files === null) {
            $this->files = array_filter(
                $this->request->getUploadedFiles(),
                static fn(mixed $file): bool => $file instanceof UploadedFileInterface
            );
        }
        return $this->files;
    }

    private function getFileByKey(string $key): UploadedFileInterface
    {
        $files = $this->files();
        if (!isset($files[$key])) {
            throw new InvalidArgumentException("The upload '$key' does not exists");
        }

        return $files[$key];
    }
}
